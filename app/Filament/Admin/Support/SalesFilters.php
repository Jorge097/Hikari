<?php

namespace App\Filament\Admin\Support;

use App\Services\Reports\SalesReport;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class SalesFilters
{
    // Meses en español: [1 => 'Enero', ...]
    public static function months(): array
    {
        return collect(range(1, 12))
            ->mapWithKeys(fn ($m) => [$m => SalesReport::monthName($m)])
            ->all();
    }

    public static function years(): array
    {
        $years = range((int) now()->year, 2025);

        return array_combine($years, $years);
    }

    // De X a Y por fecha de pago
    public static function paidRange(): Filter
    {
        return Filter::make('paid_range')
            ->label('Fecha de pago')
            ->columns(2)
            ->schema([
                DatePicker::make('from')->label('Pagado desde'),
                DatePicker::make('until')->label('Pagado hasta'),
            ])
            ->query(fn (Builder $query, array $data) => $query
                ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('paid_at', '>=', $date))
                ->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('paid_at', '<=', $date)))
            ->indicateUsing(function (array $data) {
                $from = $data['from'] ?? null;
                $until = $data['until'] ?? null;

                if (! $from && ! $until) {
                    return null;
                }

                return 'Pagado: '.($from ? Carbon::parse($from)->format('d/m/Y') : '…')
                    .' a '.($until ? Carbon::parse($until)->format('d/m/Y') : '…');
            });
    }

    // Ventas del mes N (por fecha de pago)
    public static function salesMonth(): Filter
    {
        return Filter::make('sales_month')
            ->label('Ventas del mes')
            ->columns(2)
            ->schema([
                Select::make('month')->label('Ventas del mes')->options(self::months()),
                Select::make('year')->label('Año')->options(self::years())->default(now()->year),
            ])
            ->query(function (Builder $query, array $data) {
                if (empty($data['month']) || empty($data['year'])) {
                    return $query;
                }

                [$from, $to] = SalesReport::monthRange((int) $data['year'], (int) $data['month']);

                return $query->whereNotNull('paid_at')->whereBetween('paid_at', [$from, $to]);
            })
            ->indicateUsing(fn (array $data) => ! empty($data['month']) && ! empty($data['year'])
                ? 'Ventas de '.self::months()[(int) $data['month']].' '.$data['year']
                : null);
    }
}
