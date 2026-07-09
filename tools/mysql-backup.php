<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

$backupDir = base_path('storage/backups');
if (!is_dir($backupDir) && !mkdir($backupDir, 0775, true) && !is_dir($backupDir)) {
    throw new RuntimeException('Cannot create backup directory: ' . $backupDir);
}

$database = (string) config('db_database');
$host = (string) config('db_host');
$port = (string) config('db_port');
$username = (string) config('db_username');
$password = (string) config('db_password');
$timestamp = date('Ymd_His');
$target = $backupDir . DIRECTORY_SEPARATOR . 'gaphoosh_' . $timestamp . '.sql.gz';
$tmpDefaults = tempnam(sys_get_temp_dir(), 'gaphoosh-mysql-');
if ($tmpDefaults === false) {
    throw new RuntimeException('Cannot create temporary MySQL defaults file.');
}

file_put_contents($tmpDefaults, "[client]\nuser={$username}\npassword={$password}\nhost={$host}\nport={$port}\n");
chmod($tmpDefaults, 0600);

$cmd = sprintf(
    'mysqldump --defaults-extra-file=%s --single-transaction --quick --routines --triggers --events --default-character-set=utf8mb4 %s | gzip -9 > %s',
    escapeshellarg($tmpDefaults),
    escapeshellarg($database),
    escapeshellarg($target)
);

$status = 'success';
$error = null;
exec($cmd . ' 2>&1', $output, $exitCode);
@unlink($tmpDefaults);

if ($exitCode !== 0 || !is_file($target) || filesize($target) < 100) {
    $status = 'failed';
    $error = trim(implode("\n", $output));
    @unlink($target);
}

try {
    $db = new App\Database();
    $db->insert(
        'INSERT INTO backup_logs (file_path, file_size, status, error_message, created_at) VALUES (:file_path, :file_size, :status, :error_message, NOW())',
        [
            'file_path' => $status === 'success' ? $target : 'none',
            'file_size' => $status === 'success' ? (int) filesize($target) : 0,
            'status' => $status,
            'error_message' => $error,
        ]
    );
} catch (Throwable $e) {
    app_log('backup-log-failed', ['error' => $e->getMessage(), 'backup_status' => $status]);
}

$retentionDays = (int) env_value('BACKUP_RETENTION_DAYS', 7);
if ($retentionDays > 0) {
    foreach (glob($backupDir . DIRECTORY_SEPARATOR . 'gaphoosh_*.sql.gz') ?: [] as $file) {
        if (is_file($file) && filemtime($file) < time() - ($retentionDays * 86400)) {
            @unlink($file);
        }
    }
}

if ($status !== 'success') {
    fwrite(STDERR, "Backup failed: {$error}\n");
    exit(1);
}

echo "Backup created: {$target}\n";
