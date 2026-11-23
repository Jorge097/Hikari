<?php

namespace App\Filament\Admin\Resources\MonthlySalesReports\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Pedidos del mes';

    public function table(Table $table): Table
    {
        $money = fn (string $name, string $label) => TextColumn::make($name)
            ->label($label)
            ->money(fn ($record) => $record->currency, divideBy: 100);

        return $table
            ->columns([
                TextColumn::make('folio')->label('Folio')->searchable(),
                TextColumn::make('paid_at')->label('Fecha de pago')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('customer_name')->label('Cliente')->searchable(),
                $money('subtotal_cents', 'Subtotal'),
                $money('tax_cents', 'IVA'),
                $money('shipping_cents', 'Envío'),
                $money('total_cents', 'Total'),
                TextColumn::make('total_mxn_cents')->label('Total MXN')->money('MXN', divideBy: 100),
                TextColumn::make('payment_method')->label('Método')->placeholder('—'),
                TextColumn::make('status')->label('Estado')->badge(),
                TextColumn::make('stripe_payment_id')->label('Stripe')->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('paid_at');
    }
}
