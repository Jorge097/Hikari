<?php

namespace App\Filament\Admin\Resources\Products\Tables;

use App\Filament\Admin\Support\StockActions;
use App\Models\Product;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withAvailableStock())
            ->columns([
                TextColumn::make('category.name')
                    ->label('Categoría')
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                TextColumn::make('slug')
                    ->label('Clave')
                    ->searchable(),
                TextColumn::make('price')
                    ->label('Precio')
                    ->money('MXN')
                    ->sortable(),
                TextColumn::make('size')
                    ->label('Tamaño')
                    ->searchable(),
                // Suma de sucursales activas; bajo pedido no lleva número
                TextColumn::make('stock_available')
                    ->label('Stock disponible')
                    ->badge()
                    ->state(fn (Product $record) => $record->tracks_stock ? (int) $record->stock_available : 'Bajo pedido')
                    ->color(fn (Product $record) => $record->tracks_stock ? StockActions::color((int) $record->stock_available) : 'gray')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                IconColumn::make('is_seasonal')
                    ->label('Destacado')
                    ->boolean()
                    ->toggleable(),
                ImageColumn::make('image')
                    ->label('Imagen'),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])

            ->reorderable('sort')
            ->defaultSort('sort')
            ->filters([
                Filter::make('low_stock')
                    ->label('Stock bajo (menos de '.config('inventory.low_stock_threshold').')')
                    ->query(fn (Builder $query) => $query->lowStock()),
                Filter::make('out_of_stock')
                    ->label('Agotados')
                    ->query(fn (Builder $query) => $query->outOfStock()),
                TernaryFilter::make('tracks_stock')
                    ->label('Inventario')
                    ->trueLabel('Lleva inventario')
                    ->falseLabel('Bajo pedido'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
