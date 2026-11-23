<?php

namespace App\Filament\Admin\Resources\MonthlySalesReports\Pages;

use App\Filament\Admin\Resources\MonthlySalesReports\MonthlySalesReportResource;
use App\Filament\Admin\Support\SalesFilters;
use App\Jobs\GenerateMonthlySalesReport;
use App\Services\Reports\SalesReport;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListMonthlySalesReports extends ListRecords
{
    protected static string $resource = MonthlySalesReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Genera cualquier mes a mano
            Action::make('generate')
                ->label('Generar mes')
                ->visible(fn () => auth()->user()->isAdmin())
                ->schema([
                    Select::make('month')->label('Mes')->options(SalesFilters::months())->default(now()->month)->required(),
                    Select::make('year')->label('Año')->options(SalesFilters::years())->default(now()->year)->required(),
                    Toggle::make('send_email')->label('Enviar por correo')->default(false),
                ])
                ->action(function (array $data) {
                    GenerateMonthlySalesReport::dispatchSync((int) $data['year'], (int) $data['month'], (bool) $data['send_email']);

                    Notification::make()
                        ->title('Reporte de '.SalesReport::monthName((int) $data['month'])." {$data['year']} generado")
                        ->success()
                        ->send();
                }),
        ];
    }
}
