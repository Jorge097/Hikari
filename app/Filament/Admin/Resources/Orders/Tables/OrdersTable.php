<?php

namespace App\Filament\Admin\Resources\Orders\Tables;

use App\Enums\OrderOrigin;
use App\Enums\OrderStatus;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('folio')
                    ->label('Folio')
                    ->searchable()
                    ->sortable(query: fn ($query, string $direction) => $query->orderBy('id', $direction)),
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label('Cliente')
                    ->searchable(),
                TextColumn::make('origin')
                    ->label('Origen')
                    ->badge(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                TextColumn::make('total_cents')
                    ->label('Total')
                    ->money(fn ($record) => $record->currency, divideBy: 100)
                    ->sortable(),
                TextColumn::make('paid_at')
                    ->label('Pagado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(OrderStatus::class),
                SelectFilter::make('origin')
                    ->label('Origen')
                    ->options(OrderOrigin::class),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
