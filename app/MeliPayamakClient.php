<?php

declare(strict_types=1);

namespace App;

use RuntimeException;

final class MeliPayamakClient
{
    private string $baseUrl = 'https://console.melipayamak.com/api';

    public function sendOtp(string $mobile): array
    {
        $apiKey = (string) config('melipayamak_api_key', '');
        if ($apiKey === '') {
            if ((bool) config('otp_dev_mode', false)) {
                $code = (string) random_int(100000, 999999);
                return ['code' => $code, 'status' => 'dev_mode'];
            }
            throw new RuntimeException('کلید API ملی‌پیامک تنظیم نشده است. MELIPAYAMAK_API_KEY را در .env وارد کن.');
        }

        $mobile = normalize_mobile($mobile);
        $response = $this->request('POST', '/send/otp/' . rawurlencode($apiKey), ['to' => $mobile]);
        $code = trim((string) ($response['code'] ?? ''));
        if ($code === '') {
            throw new RuntimeException('ملی‌پیامک کد OTP برنگرداند: ' . (string) ($response['status'] ?? 'پاسخ نامعتبر'));
        }

        return $response;
    }

    public function credit(): array
    {
        $apiKey = (string) config('melipayamak_api_key', '');
        if ($apiKey === '') {
            throw new RuntimeException('کلید API ملی‌پیامک تنظیم نشده است.');
        }
        return $this->request('GET', '/receive/credit/' . rawurlencode($apiKey));
    }

    private function request(string $method, string $path, array $payload = []): array
    {
        $url = $this->baseUrl . $path;
        $ch = curl_init($url);
        if ($ch === false) {
            throw new RuntimeException('امکان آماده‌سازی درخواست ملی‌پیامک وجود ندارد.');
        }

        $body = $method === 'POST' ? json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'Content-Length: ' . strlen((string) $body),
            ],
            CURLOPT_SSL_VERIFYPEER => (bool) config('melipayamak_ssl_verify', true),
            CURLOPT_SSL_VERIFYHOST => (bool) config('melipayamak_ssl_verify', true) ? 2 : 0,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, (string) $body);
        }

        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $curlError !== '') {
            throw new RuntimeException('خطای اتصال به ملی‌پیامک: ' . $curlError);
        }

        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('پاسخ ملی‌پیامک JSON معتبر نیست. وضعیت HTTP: ' . $status);
        }

        if ($status < 200 || $status >= 300) {
            throw new RuntimeException('خطای ملی‌پیامک با وضعیت ' . $status . ': ' . (string) ($decoded['status'] ?? ''));
        }

        return $decoded;
    }
}
