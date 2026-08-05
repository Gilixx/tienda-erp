<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Regenera las alertas de stock bajo / punto de reorden periódicamente,
// para que el contador y el tab de Alertas se mantengan al día.
Schedule::command('inventory:verificar-stock')->hourly();
