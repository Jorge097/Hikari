<?php

namespace App\Filament\Admin\Resources\MonthlySalesReports;

use App\Filament\Admin\Resources\MonthlySalesReports\Pages\ListMonthlySalesReports;
use App\Filament\Admin\Resources\MonthlySalesReports\Pages\ViewMonthlySalesReport;
use App\Filament\Admin\Support\SalesFilters;
use App\Jobs\GenerateMonthlySalesReport;
use App\Models\MonthlySalesReport;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use UnitEnum;

class MonthlySalesReportResource extends Resource
{
    protected static ?string $model = MonthlySalesReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Ventas';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'reporte mensual';

    protected static ?string $pluralModelLabel = 'reportes mensuales';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        $mxn = fn (string $name, string $label) => TextColumn::make($name)->label($label)->money('MXN', divideBy: 100);

        return $table
            // Solo meses cerrados; el mes en curso aún no termina
            ->modifyQueryUsing(fn (Builder $query) => $query->whereRaw('year * 12 + month < ?', [now()->year * 12 + now()->month]))
            ->filters([
                SelectFilter::make('year')->label('Año')->options(SalesFilters::years()),
                SelectFilter::make('month')->label('Mes')->options(SalesFilters::months()),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->columns([
                TextColumn::make('period')
                    ->label('Mes')
                    ->state(fn (MonthlySalesReport $record) => $record->periodLabel()),
                TextColumn::make('orders_count')->label('Pedidos'),
                $mxn('subtotal_mxn_cents', 'Subtotal'),
                $mxn('tax_mxn_cents', 'IVA'),
                $mxn('shipping_mxn_cents', 'Envío'),
                $mxn('total_mxn_cents', 'Total')->weight('bold'),
                TextColumn::make('generated_at')->label('Generado')->dateTime('d/m/Y H:i'),
                TextColumn::make('emailed_at')->label('Enviado')->dateTime('d/m/Y H:i')->placeholder('—'),
            ])
            ->defaultSort(fn ($query) => $query->orderByDesc('year')->orderByDesc('month'))
            ->recordActions([
                ViewAction::make(),
                self::downloadAction(),
                self::regenerateAction(),
            ]);
    }

    public static function downloadAction(): Action
    {
        return Action::make('download')
            ->label('Descargar')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->visible(fn (MonthlySalesReport $record) => $record->file_path && Storage::disk('local')->exists($record->file_path))
            ->action(fn (MonthlySalesReport $record) => Storage::disk('local')->download($record->file_path, $record->fileName()));
    }

    // Reemplaza el snapshot con los datos actuales
    public static function regenerateAction(): Action
    {
        return Action::make('regenerate')
            ->label('Regenerar')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('warning')
            ->visible(fn () => auth()->user()->isAdmin())
            ->requiresConfirmation()
            ->modalDescription('Vuelve a calcular el mes con los pagos actuales y reemplaza el archivo. No reenvía el correo.')
            ->action(function (MonthlySalesReport $record) {
                GenerateMonthlySalesReport::dispatchSync($record->year, $record->month, sendEmail: false);

                Notification::make()->title("Reporte de {$record->periodLabel()} regenerado")->success()->send();
            });
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMonthlySalesReports::route('/'),
            'view' => ViewMonthlySalesReport::route('/{record}'),
        ];
    }
}
