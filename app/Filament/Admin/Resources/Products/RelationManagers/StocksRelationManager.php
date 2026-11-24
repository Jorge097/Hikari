<?php

namespace App\Filament\Admin\Resources\Products\RelationManagers;

use App\Filament\Admin\Support\StockActions;
use App\Models\Branch;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

// Existencia del producto en cada sucursal
class StocksRelationManager extends RelationManager
{
    protected static string $relationship = 'stocks';

    protected static ?string $title = 'Existencias por sucursal';

    protected static ?string $modelLabel = 'existencia';

    // Bajo pedido no lleva inventario
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->tracks_stock && parent::canViewForRecord($ownerRecord, $pageClass);
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('branch'))
            ->columns([
                TextColumn::make('branch.name')->label('Sucursal')->sortable(),
                IconColumn::make('branch.is_active')->label('Activa')->boolean(),
                TextColumn::make('quantity')
                    ->label('Existencia')
                    ->badge()
                    ->color(fn ($state) => StockActions::color($state))
                    ->sortable(),
                TextColumn::make('updated_at')->label('Último cambio')->dateTime('d/m/Y H:i'),
            ])
            ->emptyStateHeading('Este producto no está en ninguna sucursal')
            ->headerActions([
                Action::make('add_branch')
                    ->label('Agregar a sucursal')
                    ->icon(Heroicon::OutlinedPlus)
                    ->schema([
                        Select::make('branch_id')
                            ->label('Sucursal')
                            ->options(fn () => Branch::query()
                                ->where('is_active', true)
                                ->whereDoesntHave('stocks', fn ($q) => $q->where('product_id', $this->getOwnerRecord()->id))
                                ->orderBy('name')
                                ->pluck('name', 'id'))
                            ->required(),
                        TextInput::make('quantity')->label('Stock inicial')->integer()->minValue(0)->maxValue(100000)->default(0)->required(),
                    ])
                    ->action(function (array $data) {
                        StockActions::addInitial((int) $data['branch_id'], $this->getOwnerRecord()->id, (int) $data['quantity']);
                        Notification::make()->title('Agregado a la sucursal')->success()->send();
                    }),
            ])
            ->recordActions([
                StockActions::adjust(),
                StockActions::remove(),
            ]);
    }
}
