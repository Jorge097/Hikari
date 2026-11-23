<?php

namespace App\Filament\Admin\Widgets;

use App\Services\Reports\DashboardMetrics;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class SalesChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Ventas e ingreso neto';

    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $series = (new DashboardMetrics($this->pageFilters['period'] ?? null))->series();

        return [
            'datasets' => [
                [
                    'label' => 'Ventas',
                    'data' => $series['gross'],
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.15)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Ingreso neto',
                    'data' => $series['net'],
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'transparent',
                    'tension' => 0.3,
                ],
            ],
            'labels' => $series['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<'JS'
            {
                scales: { y: { ticks: { callback: (value) => '$' + value.toLocaleString('es-MX') } } },
                plugins: { tooltip: { callbacks: { label: (c) => c.dataset.label + ': $' + c.parsed.y.toLocaleString('es-MX', { minimumFractionDigits: 2 }) } } },
            }
        JS);
    }
}
