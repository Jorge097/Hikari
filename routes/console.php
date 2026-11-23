<?php

use App\Jobs\GenerateMonthlySalesReport;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cierre mensual: último día a las 23:59 (hora de México)
Schedule::call(function () {
    $now = now('America/Mexico_City');
    GenerateMonthlySalesReport::dispatch($now->year, $now->month);
})
    ->name('cierre-mensual-ventas')
    ->lastDayOfMonth('23:59')
    ->timezone('America/Mexico_City');

// Regenerar a mano: php artisan reports:monthly 2026 11
Artisan::command('reports:monthly {year} {month} {--no-email}', function (int $year, int $month) {
    GenerateMonthlySalesReport::dispatchSync($year, $month, ! $this->option('no-email'));
    $this->info("Reporte {$month}/{$year} generado.");
})->purpose('Genera (o regenera) el cierre mensual de ventas');
