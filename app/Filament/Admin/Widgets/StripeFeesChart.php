<?php

namespace App\Filament\Admin\Widgets;

use App\Services\Reports\DashboardMetrics;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class StripeFeesChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 3;

    protected ?string $heading = 'Comisiones de Stripe';

    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $series = (new DashboardMetrics($this->pageFilters['period'] ?? null))->series();

        return [
            'datasets' => [[
                'label' => 'Comisión',
                'data' => $series['fees'],
                'backgroundColor' => '#f43f5e',
                'borderRadius' => 4,
            ]],
            'labels' => $series['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<'JS'
            {
                plugins: { legend: { display: false } },
                scales: { y: { ticks: { callback: (value) => '$' + value.toLocaleString('es-MX') } } },
            }
        JS);
    }
}
