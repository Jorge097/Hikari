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
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

class ListMonthlySalesReports extends ListRecords
{
    protected static string $resource = MonthlySalesReportResource::class;

    protected function getHeaderActions(): array
    {
        [$year, $month] = SalesFilters::previousMonth();

        return [
            // Abre cualquier mes cerrado; si no tiene reporte, lo genera
            Action::make('open_month')
                ->label('Elegir mes')
                ->icon(Heroicon::OutlinedCalendarDays)
                ->modalHeading('Reporte de un mes')
                ->modalDescription('Solo meses ya cerrados. Si el mes no tiene reporte, se genera con los pagos registrados.')
                ->modalSubmitActionLabel('Ver reporte')
                ->schema([
                    Select::make('month')
                        ->label('Mes')
                        ->options(SalesFilters::months())
                        ->default($month)
                        ->required()
                        ->rule(fn (Get $get) => function (string $attribute, $value, $fail) use ($get) {
                            if (! SalesFilters::isClosedMonth((int) $get('year'), (int) $value)) {
                                $fail('Ese mes aún no termina.');
                            }
                        }),
                    Select::make('year')->label('Año')->options(SalesFilters::years())->default($year)->required(),
                    Toggle::make('send_email')
                        ->label('Enviar por correo')
                        ->default(false)
                        ->visible(fn () => auth()->user()->isAdmin()),
                ])
                ->action(function (array $data) {
                    $report = MonthlySalesReport::firstWhere(['year' => $data['year'], 'month' => $data['month']]);
                    $send = (bool) ($data['send_email'] ?? false) && auth()->user()->isAdmin();

                    if (! $report || $send) {
                        GenerateMonthlySalesReport::dispatchSync((int) $data['year'], (int) $data['month'], $send);
                        $report = MonthlySalesReport::firstWhere(['year' => $data['year'], 'month' => $data['month']]);
                    }

                    $this->redirect(MonthlySalesReportResource::getUrl('view', ['record' => $report]));
                }),
        ];
    }
}
