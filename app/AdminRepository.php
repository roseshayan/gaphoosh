<?php

declare(strict_types=1);

namespace App;

use RuntimeException;

final class AdminRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function dashboard(): array
    {
        return [
            'stats' => $this->stats(),
            'users' => $this->users(),
            'logs' => $this->apiLogs(),
            'conversations' => $this->recentConversations(),
            'security_logs' => $this->securityLogs(),
            'backup_logs' => $this->backupLogs(),
            'token_usage_daily' => $this->tokenUsageDaily(),
        ];
    }

    public function stats(): array
    {
        $users = $this->db->fetch('SELECT COUNT(*) AS total, SUM(status = \'active\') AS active, SUM(status = \'blocked\') AS blocked, SUM(is_admin = 1) AS admins FROM users') ?: [];
        $conversations = $this->db->fetch('SELECT COUNT(*) AS total FROM conversations WHERE deleted_at IS NULL') ?: [];
        $messages = $this->db->fetch('SELECT COUNT(*) AS total, COALESCE(SUM(total_tokens),0) AS tokens FROM messages') ?: [];
        $today = $this->db->fetch('SELECT COUNT(*) AS total FROM messages WHERE role = \'user\' AND created_at >= CURDATE()') ?: [];
        $errors = $this->db->fetch('SELECT COUNT(*) AS total FROM api_logs WHERE success = 0 AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)') ?: [];
        $loginFailures = $this->db->fetch("SELECT COUNT(*) AS total FROM security_logs WHERE event_type IN ('login_failed','otp_failed') AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)") ?: [];
        $lastBackup = $this->db->fetch('SELECT status, created_at FROM backup_logs ORDER BY id DESC LIMIT 1') ?: [];

        return [
            'users_total' => (int) ($users['total'] ?? 0),
            'users_active' => (int) ($users['active'] ?? 0),
            'users_blocked' => (int) ($users['blocked'] ?? 0),
            'admins' => (int) ($users['admins'] ?? 0),
            'conversations_total' => (int) ($conversations['total'] ?? 0),
            'messages_total' => (int) ($messages['total'] ?? 0),
            'tokens_total' => (int) ($messages['tokens'] ?? 0),
            'messages_today' => (int) ($today['total'] ?? 0),
            'api_errors_24h' => (int) ($errors['total'] ?? 0),
            'security_events_24h' => (int) ($loginFailures['total'] ?? 0),
            'last_backup_status' => (string) ($lastBackup['status'] ?? 'none'),
        ];
    }

    public function users(int $limit = 80): array
    {
        return $this->db->fetchAll(
            'SELECT u.id, u.name, u.mobile, u.email, u.status, u.is_admin, u.created_at, u.last_login_at,
                    (SELECT COUNT(*) FROM conversations c WHERE c.user_id = u.id AND c.deleted_at IS NULL) AS conversations_count,
                    (SELECT COUNT(*) FROM messages m WHERE m.user_id = u.id) AS messages_count,
                    (SELECT COALESCE(SUM(m2.total_tokens),0) FROM messages m2 WHERE m2.user_id = u.id) AS total_tokens
             FROM users u
             ORDER BY u.id DESC
             LIMIT ' . max(1, $limit)
        );
    }

    public function apiLogs(int $limit = 80): array
    {
        return $this->db->fetchAll(
            'SELECT l.id, l.user_id, u.name, u.mobile, l.conversation_id, l.model, l.success, l.status_code, l.error_message, l.total_tokens, l.created_at
             FROM api_logs l
             INNER JOIN users u ON u.id = l.user_id
             ORDER BY l.id DESC
             LIMIT ' . max(1, $limit)
        );
    }

    public function recentConversations(int $limit = 80): array
    {
        return $this->db->fetchAll(
            'SELECT c.id, c.title, c.model, c.created_at, c.updated_at, u.name, u.mobile,
                    COUNT(m.id) AS messages_count
             FROM conversations c
             INNER JOIN users u ON u.id = c.user_id
             LEFT JOIN messages m ON m.conversation_id = c.id
             WHERE c.deleted_at IS NULL
             GROUP BY c.id
             ORDER BY c.updated_at DESC, c.id DESC
             LIMIT ' . max(1, $limit)
        );
    }


    public function securityLogs(int $limit = 80): array
    {
        return $this->db->fetchAll(
            'SELECT s.id, s.event_type, s.user_id, u.name, s.mobile, s.ip_address, s.user_agent, s.meta, s.created_at
             FROM security_logs s
             LEFT JOIN users u ON u.id = s.user_id
             ORDER BY s.id DESC
             LIMIT ' . max(1, $limit)
        );
    }

    public function backupLogs(int $limit = 20): array
    {
        return $this->db->fetchAll(
            'SELECT id, file_path, file_size, status, error_message, created_at
             FROM backup_logs
             ORDER BY id DESC
             LIMIT ' . max(1, $limit)
        );
    }

    public function tokenUsageDaily(int $days = 14): array
    {
        return $this->db->fetchAll(
            'SELECT DATE(created_at) AS day, COUNT(*) AS requests, COALESCE(SUM(total_tokens),0) AS total_tokens, SUM(success = 0) AS errors
             FROM api_logs
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ' . max(1, $days) . ' DAY)
             GROUP BY DATE(created_at)
             ORDER BY day DESC'
        );
    }

    public function setUserStatus(int $adminUserId, int $userId, string $status): void
    {
        if (!in_array($status, ['active', 'blocked'], true)) {
            throw new RuntimeException('وضعیت کاربر معتبر نیست.');
        }
        if ($adminUserId === $userId && $status === 'blocked') {
            throw new RuntimeException('نمی‌توانی حساب ادمین خودت را مسدود کنی.');
        }
        $this->db->execute('UPDATE users SET status = :status, updated_at = NOW() WHERE id = :id', ['status' => $status, 'id' => $userId]);
    }

    public function setUserAdmin(int $adminUserId, int $userId, bool $isAdmin): void
    {
        if ($adminUserId === $userId && !$isAdmin) {
            throw new RuntimeException('نمی‌توانی دسترسی ادمین خودت را حذف کنی.');
        }
        $this->db->execute('UPDATE users SET is_admin = :is_admin, updated_at = NOW() WHERE id = :id', ['is_admin' => $isAdmin ? 1 : 0, 'id' => $userId]);
    }

    public function deleteConversation(int $conversationId): void
    {
        $this->db->execute('UPDATE conversations SET deleted_at = NOW(), updated_at = NOW() WHERE id = :id', ['id' => $conversationId]);
    }
}
