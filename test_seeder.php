<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $seeder = new \Database\Seeders\TestUserSeeder();
    $seeder->run();
    echo "Success\n";
} catch (\Exception $e) {
    file_put_contents('clean_error.txt', $e->getMessage() . "\n" . $e->getTraceAsString());
    echo "Error";
}
