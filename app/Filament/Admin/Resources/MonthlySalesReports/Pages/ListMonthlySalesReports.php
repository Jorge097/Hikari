<?php

namespace App\Filament\Admin\Resources\MonthlySalesReports\Pages;

use App\Filament\Admin\Resources\MonthlySalesReports\MonthlySalesReportResource;
use App\Filament\Admin\Support\SalesFilters;
use App\Jobs\GenerateMonthlySalesReport;
use App\Models\MonthlySalesReport;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListMonthlySalesReports extends ListRecords
{
    protected static string $resource = MonthlySalesReportResource::class;

    protected function getHeaderActions(): array
    {
        $keys = SalesFilters::closedPeriodKeys();

        return [
            // Abre cualquier mes cerrado; si no tiene reporte, lo genera
            Action::make('open_month')
                ->label('Elegir mes')
                ->icon(Heroicon::OutlinedCalendarDays)
                ->modalHeading('Reporte de un mes')
                ->modalDescription('Solo meses ya cerrados. Si el mes no tiene reporte, se genera con los pagos registrados.')
                ->modalSubmitActionLabel('Ver reporte')
                ->schema([
                    // Solo meses cerrados, por año y de Enero a Diciembre; por defecto el mes anterior
                    Select::make('period')
                        ->label('Periodo')
                        ->options(SalesFilters::closedPeriods())
                        ->default(max($keys))
                        ->searchable()
                        ->required()
                        ->in($keys)
                        ->validationMessages(['in' => 'Ese mes aún no termina.']),
                    Toggle::make('send_email')
                        ->label('Enviar por correo')
                        ->default(false)
                        ->visible(fn () => auth()->user()->isAdmin()),
                ])
                ->action(function (array $data) {
                    [$year, $month] = SalesFilters::parsePeriod($data['period']);
                    $report = MonthlySalesReport::firstWhere(['year' => $year, 'month' => $month]);
                    $send = (bool) ($data['send_email'] ?? false) && auth()->user()->isAdmin();

                    if (! $report || $send) {
                        GenerateMonthlySalesReport::dispatchSync($year, $month, $send);
                        $report = MonthlySalesReport::firstWhere(['year' => $year, 'month' => $month]);
                    }

                    $this->redirect(MonthlySalesReportResource::getUrl('view', ['record' => $report]));
                }),
        ];
    }
}
