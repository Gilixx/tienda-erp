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

// Vercel obliga a que la función viva en api/, y vercel-php elimina el prefijo
// /api del REQUEST_URI antes de invocar este script (p. ej. /api/inventory/almacenes
// llegaría como /inventory/almacenes), por lo que Laravel no encontraba ninguna
// ruta de routes/api.php y devolvía 404. vercel.json reenvía la ruta original
// completa en el query param __path; aquí reconstruimos el REQUEST_URI para que
// el router de Laravel vea la URL real. (En local no existe __path, así que se ignora.)
if (isset($_GET['__path'])) {
    $path = '/' . ltrim($_GET['__path'], '/');
    unset($_GET['__path'], $_REQUEST['__path']);
    $query = http_build_query($_GET);
    $_SERVER['QUERY_STRING'] = $query;
    $_SERVER['REQUEST_URI'] = $query === '' ? $path : $path . '?' . $query;
}

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$response->send();

$kernel->terminate($request, $response);
