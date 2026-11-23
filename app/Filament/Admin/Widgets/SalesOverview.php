<?php

namespace App\Filament\Admin\Widgets;

use App\Services\Reports\DashboardMetrics;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesOverview extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $metrics = new DashboardMetrics($this->pageFilters['period'] ?? null);
        $summary = $metrics->summary();
        $series = $metrics->series();
        $money = fn (int $cents) => '$'.number_format($cents / 100, 2);

        return [
            Stat::make('Ventas', $money($summary['gross']))
                ->description('Pedidos pagados, IVA incluido')
                ->chart($series['gross'])
                ->color('primary'),
            Stat::make('Ingreso neto', $money($summary['net']))
                ->description('Ventas − reembolsos − comisiones')
                ->chart($series['net'])
                ->color('success'),
            Stat::make('Comisiones Stripe', $money($summary['fees']))
                ->description($summary['gross'] ? round($summary['fees'] / $summary['gross'] * 100, 1).'% de las ventas' : 'Sin ventas')
                ->chart($series['fees'])
                ->color('danger'),
            Stat::make('Pedidos pagados', number_format($summary['orders']))
                ->description('Ticket promedio '.$money($summary['average'])),
            Stat::make('Reembolsos', $money($summary['refunds']))
                ->color($summary['refunds'] ? 'warning' : 'gray'),
        ];
    }

    protected function getColumns(): int
    {
        return 5;
    }
}
