<?php

use App\Jobs\GenerateMonthlySalesReport;
use App\Models\MonthlySalesReport;
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

// Respaldo: si el cron no corrió a las 23:59, el día 1 a las 00:00 cierra el mes anterior
Schedule::call(function () {
    $previous = now('America/Mexico_City')->subMonthNoOverflow();

    $emailed = MonthlySalesReport::where(['year' => $previous->year, 'month' => $previous->month])
        ->whereNotNull('emailed_at')
        ->exists();

    if (! $emailed) {
        GenerateMonthlySalesReport::dispatch($previous->year, $previous->month);
    }
})
    ->name('cierre-mensual-respaldo')
    ->monthlyOn(1, '00:00')
    ->timezone('America/Mexico_City');

// Neubox compartido: correos y cierres en cola sin proceso permanente
if (config('hikari.queue_via_scheduler')) {
    Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
        ->name('cola')
        ->everyMinute()
        ->withoutOverlapping(5);
}

// Regenerar a mano: php artisan reports:monthly 2026 11
Artisan::command('reports:monthly {year} {month} {--no-email}', function (int $year, int $month) {
    GenerateMonthlySalesReport::dispatchSync($year, $month, ! $this->option('no-email'));
    $this->info("Reporte {$month}/{$year} generado.");
})->purpose('Genera (o regenera) el cierre mensual de ventas');
