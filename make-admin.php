<?php

declare(strict_types=1);

use App\Auth;
use App\Database;

require __DIR__ . '/app/bootstrap.php';

$mobile = $argv[1] ?? '';
if ($mobile === '') {
    echo "Usage: php make-admin.php 09123456789\n";
    exit(1);
}

$auth = new Auth(new Database());
$auth->markAdmin($mobile, true);
echo "دسترسی مدیریت برای " . normalize_mobile($mobile) . " فعال شد.\n";
