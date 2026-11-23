<?php

namespace App\Filament\Admin\Resources\Customers\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Correo')
                    ->searchable(),
                TextColumn::make('orders_count')
                    ->label('Pedidos')
                    ->counts('orders')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Alta')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('name')
            ->recordActions([
                ViewAction::make()->hidden(fn ($record) => auth()->user()->can('update', $record)),
                EditAction::make(),
            ]);
    }
}
