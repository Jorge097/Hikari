<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

// Últimos 20 pedidos, sin importar el periodo
class LatestOrders extends TableWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Últimos 20 pedidos';

    public function table(Table $table): Table
    {
        return $table
            ->query(Order::query()->with('customer')->latest()->limit(20))
            ->columns([
                TextColumn::make('folio')->label('Folio')->weight('bold'),
                TextColumn::make('created_at')->label('Fecha del pedido')->dateTime('d/m/Y H:i'),
                TextColumn::make('customer.name')->label('Cliente')->limit(30),
                TextColumn::make('status')->label('Estado')->badge(),
                TextColumn::make('origin')->label('Origen')->badge(),
                TextColumn::make('total_cents')
                    ->label('Total')
                    ->state(fn (Order $record) => '$'.number_format($record->total_cents / 100, 2).' '.$record->currency),
            ])
            ->recordUrl(fn (Order $record) => OrderResource::getUrl(
                auth()->user()->can('update', $record) ? 'edit' : 'view',
                ['record' => $record],
            ))
            ->paginated(false);
    }
}
