<?php

declare(strict_types=1);

namespace App;

final class SecurityLogger
{
    public function __construct(private readonly Database $db)
    {
    }

    public function log(string $eventType, ?int $userId = null, ?string $mobile = null, array $meta = []): void
    {
        try {
            $this->db->insert(
                'INSERT INTO security_logs (event_type, user_id, mobile, ip_address, user_agent, meta, created_at) VALUES (:event_type, :user_id, :mobile, :ip_address, :user_agent, :meta, NOW())',
                [
                    'event_type' => mb_substr(trim($eventType), 0, 80, 'UTF-8'),
                    'user_id' => $userId,
                    'mobile' => $mobile ? normalize_mobile($mobile) : null,
                    'ip_address' => client_ip(),
                    'user_agent' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500, 'UTF-8'),
                    'meta' => $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                ]
            );
        } catch (\Throwable $e) {
            app_log('security-log-failed', ['error' => $e->getMessage(), 'event_type' => $eventType]);
        }
    }
}
