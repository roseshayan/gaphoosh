<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$database = (string) config('db_database');
$dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', config('db_host'), config('db_port'));
$pdo = new PDO($dsn, (string) config('db_username'), (string) config('db_password'), [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$quotedDb = '`' . str_replace('`', '``', $database) . '`';
$pdo->exec('CREATE DATABASE IF NOT EXISTS ' . $quotedDb . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$pdo->exec('USE ' . $quotedDb);

$schema = file_get_contents(__DIR__ . '/database/schema_mysql.sql');
if ($schema === false) {
    throw new RuntimeException('The schema_mysql.sql was not found.');
}
$pdo->exec($schema);

$columnExists = static function (PDO $pdo, string $table, string $column) use ($database): bool {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table AND COLUMN_NAME = :column');
    $stmt->execute(['db' => $database, 'table' => $table, 'column' => $column]);
    return (int) $stmt->fetchColumn() > 0;
};

$indexExists = static function (PDO $pdo, string $table, string $index) use ($database): bool {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table AND INDEX_NAME = :index_name');
    $stmt->execute(['db' => $database, 'table' => $table, 'index_name' => $index]);
    return (int) $stmt->fetchColumn() > 0;
};

if (!$columnExists($pdo, 'users', 'is_admin')) {
    $pdo->exec('ALTER TABLE users ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0 AFTER status');
}
if (!$indexExists($pdo, 'users', 'users_admin_index')) {
    $pdo->exec('ALTER TABLE users ADD INDEX users_admin_index (is_admin)');
}

$adminMobile = normalize_mobile((string) config('admin_mobile', ''));
$adminPassword = (string) config('admin_password', '');
if ($adminMobile !== '' && $adminPassword !== '') {
    if (!is_valid_iran_mobile($adminMobile)) {
        throw new RuntimeException('ADMIN_MOBILE is not valid. Example: 09123456789');
    }
    if (mb_strlen($adminPassword, 'UTF-8') < 8) {
        throw new RuntimeException('ADMIN_PASSWORD must be at least 8 characters long.');
    }
    $stmt = $pdo->prepare('SELECT id FROM users WHERE mobile = :mobile LIMIT 1');
    $stmt->execute(['mobile' => $adminMobile]);
    $existingId = $stmt->fetchColumn();
    if ($existingId) {
        $stmt = $pdo->prepare('UPDATE users SET is_admin = 1, status = \'active\', updated_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $existingId]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO users (name, mobile, email, password_hash, status, is_admin, created_at, updated_at) VALUES (:name, :mobile, NULL, :password_hash, \'active\', 1, NOW(), NOW())');
        $stmt->execute([
            'name' => 'مدیر گپ‌هوش',
            'mobile' => $adminMobile,
            'password_hash' => password_hash($adminPassword, PASSWORD_DEFAULT),
        ]);
    }
}

echo "GapHoosh is ready. MySQL database created/updated.\n";
if ($adminMobile !== '') {
    echo "Administrative access has been set for {$adminMobile}.\n";
}
