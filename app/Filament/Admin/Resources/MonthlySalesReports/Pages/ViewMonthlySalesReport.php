<?php

namespace App\Filament\Admin\Resources\MonthlySalesReports\Pages;

use App\Filament\Admin\Resources\MonthlySalesReports\MonthlySalesReportResource;
use Filament\Resources\Pages\ViewRecord;

class ViewMonthlySalesReport extends ViewRecord
{
    protected static string $resource = MonthlySalesReportResource::class;

    public function getTitle(): string
    {
        return 'Ventas de '.$this->record->periodLabel();
    }

    protected function getHeaderActions(): array
    {
        return [
            MonthlySalesReportResource::downloadAction(),
            MonthlySalesReportResource::regenerateAction(),
        ];
    }
}
