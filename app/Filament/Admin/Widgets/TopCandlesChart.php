<?php

namespace App\Filament\Admin\Widgets;

use App\Services\Reports\DashboardMetrics;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class TopCandlesChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 4;

    protected ?string $heading = 'Velas más vendidas';

    protected ?string $description = 'Piezas vendidas en pedidos pagados';

    protected ?string $maxHeight = '280px';

    private const COLORS = ['#f59e0b', '#10b981', '#6366f1', '#f43f5e', '#0ea5e9', '#a855f7', '#84cc16', '#a8a29e'];

    protected function getData(): array
    {
        $top = (new DashboardMetrics($this->pageFilters['period'] ?? null))->topProducts();

        return [
            'datasets' => [[
                'label' => 'Piezas',
                'data' => array_values($top),
                'backgroundColor' => array_slice(self::COLORS, 0, count($top)),
            ]],
            'labels' => array_keys($top),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['position' => 'right']],
            'scales' => ['x' => ['display' => false], 'y' => ['display' => false]],
        ];
    }
}
