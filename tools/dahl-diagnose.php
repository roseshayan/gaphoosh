<?php

declare(strict_types=1);

use App\DahlClient;

require dirname(__DIR__) . '/app/bootstrap.php';

$model = $argv[1] ?? (string) config('default_model');
$client = new DahlClient();

try {
    $report = $client->diagnostics($model);
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    $ok = ($report['models_public']['ok'] ?? false) && ($report['token_creation']['ok'] ?? false) && ($report['chat_test']['ok'] ?? false);
    exit($ok ? 0 : 1);
} catch (Throwable $e) {
    fwrite(STDERR, 'Dahl diagnostics failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
