<?php

namespace App\Filament\Admin\Resources\Branches\RelationManagers;

use App\Filament\Admin\Support\StockActions;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

// Productos de la sucursal con su existencia
class StocksRelationManager extends RelationManager
{
    protected static string $relationship = 'stocks';

    protected static ?string $title = 'Existencias';

    protected static ?string $modelLabel = 'existencia';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('product'))
            ->columns([
                TextColumn::make('product.name')->label('Producto')->searchable()->sortable(),
                TextColumn::make('quantity')
                    ->label('Existencia')
                    ->badge()
                    ->color(fn ($state) => StockActions::color($state))
                    ->sortable(),
                TextColumn::make('updated_at')->label('Último cambio')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->defaultSort('quantity')
            ->emptyStateHeading('Sin productos en esta sucursal')
            ->headerActions([
                Action::make('add_product')
                    ->label('Agregar producto')
                    ->icon(Heroicon::OutlinedPlus)
                    ->schema([
                        Select::make('product_id')
                            ->label('Producto')
                            ->options(fn () => Product::query()
                                ->where('tracks_stock', true)
                                ->whereDoesntHave('stocks', fn ($q) => $q->where('branch_id', $this->getOwnerRecord()->id))
                                ->orderBy('name')
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        TextInput::make('quantity')->label('Stock inicial')->integer()->minValue(0)->maxValue(100000)->default(0)->required(),
                    ])
                    ->action(function (array $data) {
                        StockActions::addInitial($this->getOwnerRecord()->id, (int) $data['product_id'], (int) $data['quantity']);
                        Notification::make()->title('Producto agregado')->success()->send();
                    }),
            ])
            ->recordActions([
                StockActions::adjust(),
                StockActions::remove(),
            ]);
    }
}
