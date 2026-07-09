<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
$failed = false;
foreach ($files as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }
    $path = $file->getPathname();
    $cmd = 'php -l ' . escapeshellarg($path);
    exec($cmd, $output, $code);
    if ($code !== 0) {
        $failed = true;
        echo implode("\n", $output) . "\n";
    }
    $output = [];
}
if ($failed) {
    exit(1);
}
echo "All PHP files passed syntax check.\n";
