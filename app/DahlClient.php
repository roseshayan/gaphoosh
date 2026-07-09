<?php

declare(strict_types=1);

namespace App;

use RuntimeException;

final class DahlClient
{
    public function models(): array
    {
        $payload = $this->publicJsonRequest($this->modelsUrl());
        $models = $payload['data'] ?? [];
        return is_array($models) ? $this->normalizeModels($models) : [];
    }

    public function modelsWithFallback(): array
    {
        try {
            $models = $this->models();
            return $models !== [] ? $models : self::fallbackModels();
        } catch (\Throwable) {
            return self::fallbackModels();
        }
    }

    public static function fallbackModels(): array
    {
        return [
            ['id' => 'MiniMaxAI/MiniMax-M2.7', 'object' => 'model', 'created' => 1677610602, 'owned_by' => 'gonka', 'fallback' => true],
            ['id' => 'moonshotai/Kimi-K2.6', 'object' => 'model', 'created' => 1677610602, 'owned_by' => 'gonka', 'fallback' => true],
            ['id' => 'zai-org/GLM-5.2-FP8', 'object' => 'model', 'created' => 1677610602, 'owned_by' => 'gonka', 'fallback' => true],
        ];
    }

    public function status(string $window = '24h'): array
    {
        return $this->publicJsonRequest($this->statusUrl($window));
    }

    public function balance(): ?int
    {
        try {
            $payload = $this->request('GET', $this->serviceRoot() . '/tokens/current', null, true);
            return isset($payload['available_tokens']) ? (int) $payload['available_tokens'] : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public function createInferenceToken(bool $force = false): array
    {
        $cached = $this->cachedToken();
        if (!$force && $cached !== null) {
            return $cached;
        }

        $payload = $this->rawJsonRequest('POST', $this->tokenUrl(), null, ['Accept: application/json', 'Content-Length: 0'], false);
        $token = trim((string) ($payload['token'] ?? ''));
        if ($token === '') {
            throw new RuntimeException('Dahl توکن جدید برنگرداند. پاسخ endpoint /tokens نامعتبر بود.', 502);
        }

        $record = [
            'token' => $token,
            'available_tokens' => isset($payload['available_tokens']) ? (int) $payload['available_tokens'] : null,
            'created_at' => time(),
            'source' => 'POST /tokens',
        ];
        $this->writeCachedToken($record);
        return $record;
    }

    public function streamChatCompletion(string $model, array $messages, callable $onDelta, callable $onUsage = null): array
    {
        $body = json_encode([
            'model' => $model,
            'messages' => $messages,
            'stream' => true,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($body === false) {
            throw new RuntimeException('خطا در ساخت درخواست مدل.', 500);
        }

        $token = $this->accessToken();
        $result = $this->performStream($body, $token, $onDelta, $onUsage);

        if ($result['http_code'] >= 400 && $this->shouldRetryWithFreshToken((int) $result['http_code'])) {
            $fresh = $this->createInferenceToken(true);
            $result = $this->performStream($body, (string) $fresh['token'], $onDelta, $onUsage);
            $result['auth_retry'] = true;
        }

        if ($result['curl_error'] !== '') {
            throw new RuntimeException('خطای cURL: ' . $result['curl_error'], 502);
        }

        if ($result['http_code'] >= 400) {
            if ((int) $result['http_code'] === 403 && (bool) config('dahl_stream_fallback', true)) {
                $fallback = $this->chatCompletion($model, $messages, (string) ($result['token_used'] ?? $token));
                $text = (string) ($fallback['content'] ?? '');
                foreach ($this->chunkText($text) as $chunk) {
                    $onDelta($chunk);
                    usleep(12000);
                }
                if ($onUsage && isset($fallback['usage']) && is_array($fallback['usage'])) {
                    $onUsage($fallback['usage']);
                }
                $fallback['fallback_mode'] = 'non_stream_after_403';
                return $fallback;
            }

            throw new RuntimeException($this->apiErrorMessage((int) $result['http_code'], (string) $result['raw_error_body']), (int) $result['http_code']);
        }

        if (trim((string) $result['content']) === '') {
            throw new RuntimeException('پاسخ خالی از مدل دریافت شد.', 502);
        }

        return [
            'content' => (string) $result['content'],
            'usage' => is_array($result['usage']) ? $result['usage'] : [],
            'status_code' => (int) $result['http_code'],
            'fallback_mode' => ($result['auth_retry'] ?? false) ? 'stream_after_fresh_token' : null,
        ];
    }

    public function chatCompletion(string $model, array $messages, ?string $tokenOverride = null): array
    {
        $payload = $this->request('POST', $this->baseUrl() . '/chat/completions', [
            'model' => $model,
            'messages' => $messages,
            'stream' => false,
        ], true, $tokenOverride);

        $content = $payload['choices'][0]['message']['content'] ?? '';
        if (!is_string($content) || trim($content) === '') {
            throw new RuntimeException('پاسخ خالی از مدل دریافت شد.', 502);
        }

        return [
            'content' => $content,
            'usage' => is_array($payload['usage'] ?? null) ? $payload['usage'] : [],
            'status_code' => 200,
            'fallback_mode' => null,
        ];
    }

    public function diagnostics(?string $model = null): array
    {
        $model = $model ?: (string) config('default_model');
        $result = [
            'models_public' => null,
            'status_public' => null,
            'configured_token' => $this->maskToken($this->configuredKey()),
            'auth_mode' => (string) config('dahl_auth_mode', 'auto'),
            'effective_urls' => [
                'models' => $this->modelsUrl(),
                'status' => $this->statusUrl('24h'),
                'tokens' => $this->tokenUrl(),
                'tokens_current' => $this->serviceRoot() . '/tokens/current',
                'chat_completions' => $this->baseUrl() . '/chat/completions',
            ],
            'http_probes' => [
                'models' => $this->httpProbe('GET', $this->modelsUrl()),
                'status' => $this->httpProbe('GET', $this->statusUrl('24h')),
                'tokens_post' => $this->httpProbe('POST', $this->tokenUrl()),
            ],
            'config_hints' => $this->configurationHints(),
            'balance' => null,
            'token_creation' => null,
            'chat_test' => null,
        ];

        try {
            $models = $this->models();
            $result['models_public'] = ['ok' => true, 'count' => count($models), 'models' => array_column($models, 'id')];
        } catch (\Throwable $e) {
            $result['models_public'] = ['ok' => false, 'error' => $e->getMessage(), 'url' => $this->modelsUrl()];
        }

        try {
            $status = $this->status('24h');
            $result['status_public'] = ['ok' => true, 'models' => $status['models'] ?? []];
        } catch (\Throwable $e) {
            $result['status_public'] = ['ok' => false, 'error' => $e->getMessage(), 'url' => $this->statusUrl('24h')];
        }

        try {
            $result['balance'] = ['ok' => true, 'available_tokens' => $this->balance()];
        } catch (\Throwable $e) {
            $result['balance'] = ['ok' => false, 'error' => $e->getMessage()];
        }

        try {
            $token = $this->createInferenceToken(false);
            $result['token_creation'] = [
                'ok' => true,
                'token' => $this->maskToken((string) $token['token']),
                'available_tokens' => $token['available_tokens'],
            ];
        } catch (\Throwable $e) {
            $result['token_creation'] = ['ok' => false, 'error' => $e->getMessage(), 'url' => $this->tokenUrl()];
        }

        try {
            $answer = $this->chatCompletion($model, [
                ['role' => 'user', 'content' => 'در یک جمله فارسی بگو گپ‌هوش آماده است.'],
            ]);
            $result['chat_test'] = [
                'ok' => true,
                'model' => $model,
                'sample' => mb_substr((string) $answer['content'], 0, 160, 'UTF-8'),
            ];
        } catch (\Throwable $e) {
            $result['chat_test'] = ['ok' => false, 'model' => $model, 'error' => $e->getMessage(), 'code' => $e->getCode()];
        }

        return $result;
    }

    private function performStream(string $body, string $token, callable $onDelta, callable $onUsage = null): array
    {
        $fullText = '';
        $usage = [];
        $rawErrorBody = '';
        $buffer = '';

        $ch = curl_init($this->baseUrl() . '/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $this->headers(true, true, $token),
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => (int) config('stream_timeout', 180),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_WRITEFUNCTION => function ($ch, string $chunk) use (&$buffer, &$fullText, &$usage, &$rawErrorBody, $onDelta, $onUsage): int {
                $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
                if ($statusCode >= 400) {
                    $rawErrorBody .= $chunk;
                    return strlen($chunk);
                }

                $buffer .= $chunk;
                while (($pos = strpos($buffer, "\n")) !== false) {
                    $line = rtrim(substr($buffer, 0, $pos), "\r");
                    $buffer = substr($buffer, $pos + 1);

                    if ($line === '' || str_starts_with($line, ':') || !str_starts_with($line, 'data:')) {
                        continue;
                    }

                    $data = trim(substr($line, 5));
                    if ($data === '' || $data === '[DONE]') {
                        continue;
                    }

                    $json = json_decode($data, true);
                    if (!is_array($json)) {
                        continue;
                    }

                    $delta = $json['choices'][0]['delta']['content']
                        ?? $json['choices'][0]['message']['content']
                        ?? '';

                    if (is_string($delta) && $delta !== '') {
                        $fullText .= $delta;
                        $onDelta($delta);
                    }

                    if (isset($json['usage']) && is_array($json['usage'])) {
                        $usage = $json['usage'];
                        if ($onUsage) {
                            $onUsage($usage);
                        }
                    }
                }
                return strlen($chunk);
            },
        ]);

        $ok = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        return [
            'ok' => $ok !== false,
            'curl_error' => $ok === false ? $curlError : '',
            'http_code' => $httpCode,
            'content' => $fullText,
            'usage' => $usage,
            'raw_error_body' => $rawErrorBody,
            'token_used' => $token,
            'auth_retry' => false,
        ];
    }

    private function publicJsonRequest(string $url): array
    {
        // Dahl docs say /v1/models and /v1/status are public. Do NOT send Authorization here.
        // Sending an invalid Bearer token can make an otherwise public route return 403.
        return $this->rawJsonRequest('GET', $url, null, ['Accept: application/json'], true);
    }

    private function request(string $method, string $url, ?array $body = null, bool $auth = true, ?string $tokenOverride = null): array
    {
        try {
            return $this->rawJsonRequest($method, $url, $body, $this->headers($auth, $body !== null, $tokenOverride), true);
        } catch (RuntimeException $e) {
            if ($auth && $this->shouldRetryWithFreshToken((int) $e->getCode())) {
                $fresh = $this->createInferenceToken(true);
                return $this->rawJsonRequest($method, $url, $body, $this->headers(true, $body !== null, (string) $fresh['token']), true);
            }
            throw $e;
        }
    }

    private function rawJsonRequest(string $method, string $url, ?array $body = null, array $headers = [], bool $decodeError = true): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        ]);
        if (strtoupper($method) === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            // Dahl's POST /tokens behaves like `curl -X POST ...` with an explicit empty body.
            // Without CURLOPT_POSTFIELDS some PHP/cURL builds can send a POST shape Dahl rejects with 400.
            if ($body === null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, '');
            }
        }
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('خطای ارتباط: ' . $error, 502);
        }
        $responseText = (string) $response;
        $decoded = json_decode($responseText, true);
        if ($this->isCloudflareChallenge($responseText)) {
            throw new RuntimeException($this->cloudflareMessage($status, $url), $status > 0 ? $status : 403);
        }
        if ($status >= 400) {
            throw new RuntimeException($decodeError ? $this->apiErrorMessage($status, $responseText) : 'خطای Dahl با وضعیت ' . $status, $status);
        }
        if (!is_array($decoded) && trim($responseText) !== '') {
            throw new RuntimeException('پاسخ Dahl JSON نبود. احتمالاً به‌جای API، صفحه HTML یا challenge برگشته است. URL: ' . $url, 502);
        }
        return is_array($decoded) ? $decoded : [];
    }

    private function normalizeModels(array $models): array
    {
        $unique = [];
        foreach ($models as $model) {
            if (!is_array($model) || empty($model['id'])) {
                continue;
            }
            $unique[(string) $model['id']] = $model;
        }
        return array_values($unique);
    }

    private function shouldRetryWithFreshToken(int $status): bool
    {
        return in_array($status, [401, 402, 403], true) && (bool) config('dahl_auto_token_on_auth_error', true);
    }

    private function apiErrorMessage(int $status, string $body): string
    {
        if ($this->isCloudflareChallenge($body)) {
            return $this->cloudflareMessage($status, 'Dahl API');
        }

        $message = 'خطای Dahl با وضعیت ' . $status;
        $decoded = json_decode($body, true);
        if (is_array($decoded)) {
            $apiMessage = $decoded['error']['message'] ?? $decoded['message'] ?? null;
            if (is_string($apiMessage) && $apiMessage !== '') {
                $message .= ': ' . $apiMessage;
            }
        } else {
            $preview = trim(strip_tags(mb_substr($body, 0, 220, 'UTF-8')));
            if ($preview !== '') {
                $message .= ' — پاسخ غیر JSON: ' . preg_replace('/\s+/', ' ', $preview);
            }
        }
        if ($status === 400) {
            $message .= ' — درخواست برای Dahl نامعتبر است؛ پاسخ خام را در php tools/dahl-diagnose.php ببین.';
        } elseif ($status === 401) {
            $message .= ' — هدر Authorization درست نرسیده یا توکن نامعتبر/منقضی است.';
        } elseif ($status === 402) {
            $message .= ' — سهمیه توکن این کلید تمام شده است.';
        } elseif ($status === 403) {
            $message .= ' — دسترسی رد شده است. اگر endpoint عمومی است، احتمالاً Cloudflare/IP جلوی درخواست غیرمرورگری را گرفته؛ اگر endpoint چت/توکن است، URL یا Bearer token inference را بررسی کن.';
        }
        return $message;
    }

    private function httpProbe(string $method, string $url): array
    {
        $ch = curl_init($url);
        $headers = [
            'User-Agent: GapHoosh-Diagnostics/7.0 (+https://gaphoosh.ir)',
            'Accept: application/json, */*;q=0.8',
        ];

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        ]);
        if (strtoupper($method) === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, '');
        }

        $raw = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        if ($raw === false) {
            return [
                'ok' => false,
                'method' => $method,
                'url' => $url,
                'status' => $status,
                'curl_error' => $error,
            ];
        }

        $body = substr((string) $raw, $headerSize);
        $bodyPreview = trim(preg_replace('/\s+/', ' ', strip_tags(mb_substr($body, 0, 450, 'UTF-8'))));
        $json = json_decode($body, true);

        if ($status >= 200 && $status < 300 && is_array($json) && isset($json['token']) && is_string($json['token']) && trim($json['token']) !== '') {
            $this->writeCachedToken([
                'token' => trim($json['token']),
                'available_tokens' => isset($json['available_tokens']) ? (int) $json['available_tokens'] : null,
                'created_at' => time(),
                'source' => 'diagnostics POST /tokens probe',
            ]);
        }

        return [
            'ok' => $status >= 200 && $status < 300 && is_array($json),
            'method' => $method,
            'url' => $url,
            'status' => $status,
            'content_type' => $contentType,
            'cloudflare_challenge' => $this->isCloudflareChallenge($body),
            'json' => is_array($json),
            'body_preview' => $bodyPreview,
        ];
    }

    private function isCloudflareChallenge(string $body): bool
    {
        $body = strtolower($body);
        return str_contains($body, 'challenge-error-text')
            || str_contains($body, 'enable javascript and cookies to continue')
            || str_contains($body, '_cf_chl_opt')
            || str_contains($body, '/cdn-cgi/challenge-platform/')
            || str_contains($body, '__cf_chl_tk');
    }

    private function cloudflareMessage(int $status, string $url): string
    {
        return 'درخواست به Dahl به‌جای JSON، صفحه Cloudflare Challenge برگرداند'
            . ($status > 0 ? '؛ وضعیت HTTP ' . $status : '')
            . '. این یعنی از این IP/محیط، درخواست‌های PHP/cURL به inference.dahl.global نیاز به JavaScript و cookies دارند و برای بک‌اند قابل استفاده نیستند. URL: ' . $url;
    }

    private function headers(bool $auth = true, bool $jsonBody = true, ?string $tokenOverride = null): array
    {
        $headers = [
            'User-Agent: GapHoosh/7.0 (+https://gaphoosh.ir)',
            'Accept: application/json, text/event-stream;q=0.9, */*;q=0.8',
        ];
        if ($jsonBody) {
            $headers[] = 'Content-Type: application/json';
        }
        if ($auth) {
            $headers[] = 'Authorization: Bearer ' . ($tokenOverride ?: $this->accessToken());
        }
        return $headers;
    }

    private function accessToken(): string
    {
        $mode = strtolower(trim((string) config('dahl_auth_mode', 'static')));
        $configured = $this->configuredKey();

        if ($mode === 'auto' || $configured === '') {
            $record = $this->createInferenceToken(false);
            return (string) $record['token'];
        }

        return $configured;
    }

    private function configuredKey(): string
    {
        return trim((string) config('dahl_api_key'));
    }

    private function tokenUrl(): string
    {
        return rtrim((string) config('dahl_token_url', 'https://inference.dahl.global/tokens'), '/');
    }

    private function baseUrl(): string
    {
        $url = rtrim((string) config('dahl_base_url'), '/');
        if ($url === '') {
            return 'https://inference.dahl.global/v1';
        }
        // If user accidentally sets the service root, normalize it to OpenAI-compatible /v1.
        if (!preg_match('#/v1$#', $url)) {
            $host = parse_url($url, PHP_URL_HOST);
            if ($host === 'inference.dahl.global') {
                $url .= '/v1';
            }
        }
        return $url;
    }

    private function modelsUrl(): string
    {
        return $this->baseUrl() . '/models';
    }

    private function statusUrl(string $window): string
    {
        return $this->baseUrl() . '/status?window=' . rawurlencode($window);
    }

    private function configurationHints(): array
    {
        $hints = [];
        $mode = strtolower(trim((string) config('dahl_auth_mode', 'auto')));
        $configured = $this->configuredKey();
        if ($mode === 'static' && $configured !== '') {
            $hints[] = 'اکنون حالت static فعال است؛ فقط زمانی درست است که DAHL_API_KEY واقعاً Bearer token ساخته‌شده از POST /tokens باشد.';
        }
        if ($mode === 'static' && str_starts_with($configured, 'dahl_')) {
            $hints[] = 'کلید با dahl_ شروع می‌شود. اگر این credential حساب است و نه token endpoint /tokens، برای inference جواب نمی‌دهد.';
        }
        if (!str_ends_with($this->baseUrl(), '/v1')) {
            $hints[] = 'DAHL_BASE_URL باید به /v1 ختم شود.';
        }
        if (str_contains($this->tokenUrl(), '/v1/tokens')) {
            $hints[] = 'DAHL_TOKEN_URL اشتباه است؛ endpoint توکن باید root باشد: https://inference.dahl.global/tokens';
        }
        return $hints;
    }

    private function serviceRoot(): string
    {
        return rtrim(preg_replace('#/v1$#', '', $this->baseUrl()) ?? 'https://inference.dahl.global', '/');
    }

    private function cachePath(): string
    {
        return dirname(__DIR__) . '/storage/cache/dahl-token.json';
    }

    private function cachedToken(): ?array
    {
        $path = $this->cachePath();
        if (!is_file($path)) {
            return null;
        }
        $json = json_decode((string) file_get_contents($path), true);
        if (!is_array($json) || empty($json['token'])) {
            return null;
        }
        $ttl = (int) config('dahl_auto_token_ttl_seconds', 604800);
        $createdAt = (int) ($json['created_at'] ?? 0);
        if ($ttl > 0 && $createdAt > 0 && time() - $createdAt > $ttl) {
            return null;
        }
        return $json;
    }

    private function writeCachedToken(array $record): void
    {
        $path = $this->cachePath();
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }

    private function maskToken(string $token): string
    {
        if ($token === '') {
            return '';
        }
        $length = strlen($token);
        if ($length <= 12) {
            return str_repeat('*', $length);
        }
        return substr($token, 0, 6) . '...' . substr($token, -4);
    }

    private function chunkText(string $text): array
    {
        $chunks = [];
        $length = mb_strlen($text, 'UTF-8');
        for ($i = 0; $i < $length; $i += 10) {
            $chunks[] = mb_substr($text, $i, 10, 'UTF-8');
        }
        return $chunks;
    }
}
