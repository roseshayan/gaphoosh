<?php

declare(strict_types=1);

namespace App;

use RuntimeException;

final class ChatRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function createConversation(int $userId, string $model, ?string $title = null): int
    {
        $title = $title ?: 'گفتگوی جدید';
        return $this->db->insert(
            'INSERT INTO conversations (user_id, title, model, created_at, updated_at) VALUES (:user_id, :title, :model, NOW(), NOW())',
            ['user_id' => $userId, 'title' => $title, 'model' => $model]
        );
    }

    public function listConversations(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT id, title, model, created_at, updated_at FROM conversations WHERE user_id = :user_id AND deleted_at IS NULL ORDER BY updated_at DESC, id DESC LIMIT 80',
            ['user_id' => $userId]
        );
    }

    public function findOwnedConversation(int $conversationId, int $userId): ?array
    {
        return $this->db->fetch(
            'SELECT * FROM conversations WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL LIMIT 1',
            ['id' => $conversationId, 'user_id' => $userId]
        );
    }

    public function messages(int $conversationId, int $userId): array
    {
        $conversation = $this->findOwnedConversation($conversationId, $userId);
        if (!$conversation) {
            throw new RuntimeException('گفتگو پیدا نشد.');
        }
        return $this->db->fetchAll(
            'SELECT id, role, content, model, total_tokens, created_at FROM messages WHERE conversation_id = :conversation_id ORDER BY id ASC',
            ['conversation_id' => $conversationId]
        );
    }

    public function conversationForModel(int $conversationId, int $limit): array
    {
        $rows = $this->db->fetchAll(
            'SELECT role, content FROM messages WHERE conversation_id = :conversation_id AND role IN (\'user\', \'assistant\') ORDER BY id DESC LIMIT ' . max(1, $limit),
            ['conversation_id' => $conversationId]
        );
        $rows = array_reverse($rows);
        return array_map(static fn (array $row): array => [
            'role' => (string) $row['role'],
            'content' => (string) $row['content'],
        ], $rows);
    }

    public function addMessage(int $conversationId, int $userId, string $role, string $content, ?string $model = null, array $usage = [], ?array $meta = null): int
    {
        return $this->db->insert(
            'INSERT INTO messages (conversation_id, user_id, role, content, model, prompt_tokens, completion_tokens, total_tokens, meta, created_at) VALUES (:conversation_id, :user_id, :role, :content, :model, :prompt_tokens, :completion_tokens, :total_tokens, :meta, NOW())',
            [
                'conversation_id' => $conversationId,
                'user_id' => $userId,
                'role' => $role,
                'content' => $content,
                'model' => $model,
                'prompt_tokens' => (int) ($usage['prompt_tokens'] ?? 0),
                'completion_tokens' => (int) ($usage['completion_tokens'] ?? 0),
                'total_tokens' => (int) ($usage['total_tokens'] ?? 0),
                'meta' => $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            ]
        );
    }

    public function touchConversation(int $conversationId, string $model, ?string $title = null): void
    {
        $params = ['id' => $conversationId, 'model' => $model];
        $sql = 'UPDATE conversations SET model = :model, updated_at = NOW()';
        if ($title !== null && trim($title) !== '') {
            $sql .= ', title = :title';
            $params['title'] = $title;
        }
        $sql .= ' WHERE id = :id';
        $this->db->execute($sql, $params);
    }

    public function deleteConversation(int $conversationId, int $userId): void
    {
        $this->db->execute(
            'UPDATE conversations SET deleted_at = NOW(), updated_at = NOW() WHERE id = :id AND user_id = :user_id',
            ['id' => $conversationId, 'user_id' => $userId]
        );
    }

    public function renameConversation(int $conversationId, int $userId, string $title): void
    {
        $title = trim($title);
        if ($title === '') {
            throw new RuntimeException('عنوان نمی‌تواند خالی باشد.');
        }
        $this->db->execute(
            'UPDATE conversations SET title = :title, updated_at = NOW() WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL',
            ['title' => truncate_fa($title, 80), 'id' => $conversationId, 'user_id' => $userId]
        );
    }

    public function userMessagesToday(int $userId): int
    {
        $row = $this->db->fetch(
            'SELECT COUNT(*) AS count_messages FROM messages WHERE user_id = :user_id AND role = \'user\' AND created_at >= CURDATE()',
            ['user_id' => $userId]
        );
        return (int) ($row['count_messages'] ?? 0);
    }

    public function firstUserMessageCount(int $conversationId): int
    {
        $row = $this->db->fetch(
            'SELECT COUNT(*) AS c FROM messages WHERE conversation_id = :conversation_id AND role = \'user\'',
            ['conversation_id' => $conversationId]
        );
        return (int) ($row['c'] ?? 0);
    }

    public function logApi(int $userId, ?int $conversationId, string $model, bool $success, int $statusCode = 0, ?string $error = null, array $usage = []): void
    {
        $this->db->insert(
            'INSERT INTO api_logs (user_id, conversation_id, model, success, status_code, error_message, total_tokens, created_at) VALUES (:user_id, :conversation_id, :model, :success, :status_code, :error_message, :total_tokens, NOW())',
            [
                'user_id' => $userId,
                'conversation_id' => $conversationId,
                'model' => $model,
                'success' => $success ? 1 : 0,
                'status_code' => $statusCode,
                'error_message' => $error,
                'total_tokens' => (int) ($usage['total_tokens'] ?? 0),
            ]
        );
    }
}
