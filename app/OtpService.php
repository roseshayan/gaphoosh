<?php

declare(strict_types=1);

namespace App;

use RuntimeException;

final class OtpService
{
    public function __construct(
        private readonly Database $db,
        private readonly MeliPayamakClient $sms,
        private readonly SecurityLogger $logger,
    ) {
    }

    public function send(string $mobile, string $purpose = 'register'): void
    {
        $mobile = normalize_mobile($mobile);
        if (!is_valid_iran_mobile($mobile)) {
            throw new RuntimeException('شماره موبایل معتبر نیست.');
        }

        $response = $this->sms->sendOtp($mobile);
        $code = trim((string) ($response['code'] ?? ''));
        if ($code === '') {
            throw new RuntimeException('کد OTP از سرویس پیامک دریافت نشد.');
        }

        $ttl = max(60, (int) config('otp_ttl_seconds', 120));
        $this->db->execute(
            'UPDATE otp_codes SET consumed_at = NOW() WHERE mobile = :mobile AND purpose = :purpose AND consumed_at IS NULL',
            ['mobile' => $mobile, 'purpose' => $purpose]
        );
        $this->db->insert(
            'INSERT INTO otp_codes (mobile, purpose, code_hash, expires_at, attempts, ip_address, created_at) VALUES (:mobile, :purpose, :code_hash, :expires_at, 0, :ip_address, NOW())',
            [
                'mobile' => $mobile,
                'purpose' => $purpose,
                'code_hash' => password_hash($code, PASSWORD_DEFAULT),
                'expires_at' => date('Y-m-d H:i:s', time() + $ttl),
                'ip_address' => client_ip(),
            ]
        );

        $this->logger->log('otp_sent', null, $mobile, ['purpose' => $purpose, 'provider_status' => $response['status'] ?? null]);
        if ((bool) config('otp_dev_mode', false)) {
            $_SESSION['_dev_otp_code'] = $code;
        }
    }

    public function verify(string $mobile, string $code, string $purpose = 'register'): void
    {
        $mobile = normalize_mobile($mobile);
        $code = trim(to_latin_digits($code));
        if ($code === '') {
            throw new RuntimeException('کد تأیید را وارد کنید.');
        }

        $row = $this->db->fetch(
            'SELECT * FROM otp_codes WHERE mobile = :mobile AND purpose = :purpose AND consumed_at IS NULL ORDER BY id DESC LIMIT 1',
            ['mobile' => $mobile, 'purpose' => $purpose]
        );
        if (!$row) {
            throw new RuntimeException('کد تأیید فعال برای این شماره پیدا نشد.');
        }
        if (strtotime((string) $row['expires_at']) < time()) {
            throw new RuntimeException('کد تأیید منقضی شده است. دوباره کد بگیر.');
        }
        if ((int) $row['attempts'] >= (int) config('otp_max_attempts', 5)) {
            throw new RuntimeException('تعداد تلاش برای این کد زیاد شده است. دوباره کد بگیر.');
        }

        $this->db->execute('UPDATE otp_codes SET attempts = attempts + 1 WHERE id = :id', ['id' => (int) $row['id']]);
        if (!password_verify($code, (string) $row['code_hash'])) {
            $this->logger->log('otp_failed', null, $mobile, ['purpose' => $purpose]);
            throw new RuntimeException('کد تأیید اشتباه است.');
        }

        $this->db->execute('UPDATE otp_codes SET consumed_at = NOW() WHERE id = :id', ['id' => (int) $row['id']]);
        $this->logger->log('otp_verified', null, $mobile, ['purpose' => $purpose]);
    }
}
