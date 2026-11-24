<?php

namespace App\Filament\Admin\Resources\StockMovements;

use App\Enums\StockMovementType;
use App\Filament\Admin\Resources\StockMovements\Pages\ListStockMovements;
use App\Models\StockMovement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

// Historial de inventario, solo lectura
class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|UnitEnum|null $navigationGroup = 'Inventario';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Movimientos';

    protected static ?string $modelLabel = 'movimiento';

    protected static ?string $pluralModelLabel = 'movimientos de inventario';

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['branch', 'product', 'user', 'order']))
            ->columns([
                TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('product.name')->label('Producto')->searchable(),
                TextColumn::make('branch.name')->label('Sucursal'),
                TextColumn::make('type')->label('Tipo')->badge(),
                TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->formatStateUsing(fn (int $state) => $state > 0 ? "+{$state}" : (string) $state)
                    ->color(fn (int $state) => $state > 0 ? 'success' : 'danger'),
                TextColumn::make('resulting_stock')->label('Queda'),
                TextColumn::make('reason')->label('Motivo')->placeholder('—')->limit(40),
                TextColumn::make('order.folio')->label('Pedido')->placeholder('—'),
                TextColumn::make('user.name')->label('Usuario')->placeholder('Sistema'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('type')->label('Tipo')->options(StockMovementType::class),
                SelectFilter::make('branch_id')->label('Sucursal')->relationship('branch', 'name'),
                SelectFilter::make('product_id')->label('Producto')->relationship('product', 'name')->searchable(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockMovements::route('/'),
        ];
    }
}
