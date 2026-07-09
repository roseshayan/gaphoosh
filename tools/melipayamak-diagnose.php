<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

$client = new App\MeliPayamakClient();
$result = [
    'api_key_configured' => (string) config('melipayamak_api_key', '') !== '',
    'otp_dev_mode' => (bool) config('otp_dev_mode', false),
];

try {
    $result['credit'] = ['ok' => true, 'response' => $client->credit()];
} catch (Throwable $e) {
    $result['credit'] = ['ok' => false, 'error' => $e->getMessage()];
}

$mobile = $argv[1] ?? '';
if ($mobile !== '') {
    try {
        $result['otp_test'] = ['ok' => true, 'response' => $client->sendOtp($mobile)];
    } catch (Throwable $e) {
        $result['otp_test'] = ['ok' => false, 'error' => $e->getMessage()];
    }
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
