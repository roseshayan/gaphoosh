<?php

declare(strict_types=1);

namespace App;

use PDOException;
use RuntimeException;

final class Auth
{
    public function __construct(private readonly Database $db)
    {
    }

    public function register(string $name, string $mobile, string $email, string $password): array
    {
        $name = trim($name);
        $mobile = normalize_mobile($mobile);
        $email = trim(mb_strtolower($email, 'UTF-8'));

        if (mb_strlen($name, 'UTF-8') < 2) {
            throw new RuntimeException('نام را کامل وارد کنید.');
        }
        if (!is_valid_iran_mobile($mobile)) {
            throw new RuntimeException('شماره موبایل معتبر نیست. نمونه درست: 09123456789');
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('ایمیل معتبر نیست.');
        }
        if (mb_strlen($password, 'UTF-8') < 8) {
            throw new RuntimeException('رمز عبور باید حداقل ۸ کاراکتر باشد.');
        }

        $isAdmin = $this->shouldBeAdmin($mobile) ? 1 : 0;

        try {
            $userId = $this->db->insert(
                'INSERT INTO users (name, mobile, email, password_hash, status, is_admin, created_at, updated_at) VALUES (:name, :mobile, :email, :password_hash, \'active\', :is_admin, NOW(), NOW())',
                [
                    'name' => $name,
                    'mobile' => $mobile,
                    'email' => $email !== '' ? $email : null,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'is_admin' => $isAdmin,
                ]
            );
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new RuntimeException('این شماره موبایل یا ایمیل قبلاً ثبت شده است.');
            }
            throw $e;
        }

        $user = $this->db->fetch('SELECT id, name, mobile, email, is_admin FROM users WHERE id = :id', ['id' => $userId]);
        if (!$user) {
            throw new RuntimeException('خطا در ساخت حساب کاربری.');
        }
        $this->loginSession($user);
        return $user;
    }

    public function login(string $mobile, string $password): array
    {
        $mobile = normalize_mobile($mobile);
        if (!is_valid_iran_mobile($mobile)) {
            throw new RuntimeException('شماره موبایل معتبر نیست.');
        }

        $user = $this->db->fetch('SELECT * FROM users WHERE mobile = :mobile LIMIT 1', ['mobile' => $mobile]);
        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            throw new RuntimeException('شماره موبایل یا رمز عبور اشتباه است.');
        }
        if (($user['status'] ?? 'active') !== 'active') {
            throw new RuntimeException('حساب کاربری شما غیرفعال است.');
        }

        $this->db->execute('UPDATE users SET last_login_at = NOW(), updated_at = NOW() WHERE id = :id', ['id' => (int) $user['id']]);
        $safeUser = [
            'id' => (int) $user['id'],
            'name' => (string) $user['name'],
            'mobile' => (string) $user['mobile'],
            'email' => $user['email'],
            'is_admin' => (int) ($user['is_admin'] ?? 0),
        ];
        $this->loginSession($safeUser);
        return $safeUser;
    }

    public function markAdmin(string $mobile, bool $admin = true): void
    {
        $mobile = normalize_mobile($mobile);
        if (!is_valid_iran_mobile($mobile)) {
            throw new RuntimeException('شماره موبایل معتبر نیست.');
        }
        $updated = $this->db->execute(
            'UPDATE users SET is_admin = :is_admin, updated_at = NOW() WHERE mobile = :mobile',
            ['is_admin' => $admin ? 1 : 0, 'mobile' => $mobile]
        );
        if ($updated < 1) {
            throw new RuntimeException('کاربری با این شماره پیدا نشد.');
        }
    }

    private function shouldBeAdmin(string $mobile): bool
    {
        if ((bool) config('first_registered_user_is_admin', true)) {
            $row = $this->db->fetch('SELECT COUNT(*) AS c FROM users');
            if ((int) ($row['c'] ?? 0) === 0) {
                return true;
            }
        }

        $adminMobile = normalize_mobile((string) config('admin_mobile', ''));
        return $adminMobile !== '' && $mobile === $adminMobile;
    }

    private function loginSession(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => (string) $user['name'],
            'mobile' => (string) $user['mobile'],
            'email' => $user['email'] ?? null,
            'is_admin' => (int) ($user['is_admin'] ?? 0),
        ];
        csrf_token();
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}
