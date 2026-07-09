<?php

declare(strict_types=1);

function env_value(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($value === false || $value === null) {
        return $default;
    }
    if (!is_string($value)) {
        return $value;
    }
    $trimmed = trim($value);
    if ($trimmed === '') {
        return '';
    }
    $lower = strtolower($trimmed);
    return match ($lower) {
        'true', '(true)' => true,
        'false', '(false)' => false,
        'null', '(null)' => null,
        default => preg_match('/^".*"$/', $trimmed) || preg_match("/^'.*'$/", $trimmed)
            ? substr($trimmed, 1, -1)
            : $trimmed,
    };
}

function config(string $key, mixed $default = null): mixed
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/config.php';
    }
    return $config[$key] ?? $default;
}

function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_path(string $path = ''): string
{
    return dirname(__DIR__) . ($path !== '' ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : '');
}

function public_url(string $path = ''): string
{
    $scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $base = $scriptName === '/' || $scriptName === '\\' ? '' : rtrim($scriptName, '/');
    return $base . '/' . ltrim($path, '/');
}

function redirect_to(string $url): never
{
    header('Location: ' . $url, true, 302);
    exit;
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_input(): array
{
    $raw = file_get_contents('php://input') ?: '';
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['_csrf'];
}

function verify_csrf(): void
{
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_csrf'] ?? '';
    if (!is_string($token) || !hash_equals((string) ($_SESSION['_csrf'] ?? ''), $token)) {
        json_response(['error' => 'درخواست نامعتبر است. صفحه را رفرش کنید.'], 419);
    }
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_auth(): array
{
    $user = current_user();
    if (!$user) {
        if (str_starts_with(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/api/')) {
            json_response(['error' => 'ابتدا وارد حساب کاربری شوید.'], 401);
        }
        redirect_to(public_url('login'));
    }
    return $user;
}

function require_admin(): array
{
    $user = require_auth();
    if ((int) ($user['is_admin'] ?? 0) !== 1) {
        if (str_starts_with(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/api/')) {
            json_response(['error' => 'دسترسی مدیریت لازم است.'], 403);
        }
        http_response_code(403);
        echo 'دسترسی مدیریت لازم است.';
        exit;
    }
    return $user;
}

function view(string $name, array $data = []): never
{
    extract($data, EXTR_SKIP);
    $viewFile = base_path('app/views/' . $name . '.php');
    if (!is_file($viewFile)) {
        throw new RuntimeException('View not found: ' . $name);
    }
    require base_path('app/views/layout.php');
    exit;
}

function render_partial(string $viewFile, array $data): void
{
    extract($data, EXTR_SKIP);
    require $viewFile;
}

function normalize_mobile(string $mobile): string
{
    $mobile = trim($mobile);
    $persian = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹','٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
    $latin = ['0','1','2','3','4','5','6','7','8','9','0','1','2','3','4','5','6','7','8','9'];
    $mobile = str_replace($persian, $latin, $mobile);
    $mobile = preg_replace('/[\s\-()]/', '', $mobile) ?? $mobile;
    if (str_starts_with($mobile, '+98')) {
        $mobile = '0' . substr($mobile, 3);
    } elseif (str_starts_with($mobile, '98') && strlen($mobile) === 12) {
        $mobile = '0' . substr($mobile, 2);
    }
    return $mobile;
}

function is_valid_iran_mobile(string $mobile): bool
{
    return (bool) preg_match('/^09\d{9}$/', $mobile);
}

function truncate_fa(string $text, int $length = 60): string
{
    $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    if (mb_strlen($text, 'UTF-8') <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length, 'UTF-8') . '…';
}

function now_sql(): string
{
    return (new DateTimeImmutable('now', new DateTimeZone((string) config('timezone', 'UTC'))))->format('Y-m-d H:i:s');
}

function security_headers(string $nonce = ''): void
{
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    $noncePart = $nonce !== '' ? " 'nonce-{$nonce}'" : '';
    header("Content-Security-Policy: default-src 'self'; script-src 'self'{$noncePart}; style-src 'self'; img-src 'self' data:; font-src 'self'; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
}

function send_sse(string $event, array|string $data): void
{
    if (!headers_sent()) {
        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');
    }
    echo 'event: ' . $event . "\n";
    $payload = is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo 'data: ' . str_replace("\n", "\ndata: ", (string) $payload) . "\n\n";
    @ob_flush();
    flush();
}
