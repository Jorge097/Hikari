<?php

namespace App\Filament\Admin\Pages;

use App\Services\Reports\DashboardMetrics;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;

// Dashboard de ventas con filtro de periodo compartido por los widgets
class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static ?string $title = 'Inicio';

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('period')
                ->label('Periodo')
                ->options(DashboardMetrics::PERIODS)
                ->default('month')
                ->selectablePlaceholder(false),
        ]);
    }

    public function getColumns(): int|array
    {
        return 2;
    }
}
