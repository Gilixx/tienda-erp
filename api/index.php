<?php

define('LARAVEL_START', microtime(true));

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

// Point storage to /tmp — the only writable directory in Vercel's serverless runtime.
// Sessions and cache use the database driver, so only view compilation and logs land here.
$app->useStoragePath('/tmp/laravel/storage');

foreach ([
    '/tmp/laravel/storage/framework/sessions',
    '/tmp/laravel/storage/framework/cache/data',
    '/tmp/laravel/storage/framework/views',
    '/tmp/laravel/storage/logs',
] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$response->send();

$kernel->terminate($request, $response);
