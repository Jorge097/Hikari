<?php

namespace App\Filament\Admin\Support;

use App\Enums\StockMovementType;
use App\Models\Stock;
use App\Services\Inventory\InsufficientStockException;
use App\Services\Inventory\StockService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

// Acciones de existencias compartidas por Sucursales y Productos
class StockActions
{
    public const MODES = [
        'entrada' => 'Entrada: sumar piezas que llegaron',
        'salida' => 'Salida: restar (merma, rotura, regalo)',
        'conteo' => 'Conteo físico: dejar la cantidad exacta',
    ];

    // rojo = agotado, ámbar = bajo, verde = bien
    public static function color(?int $quantity): string
    {
        return match (true) {
            ($quantity ?? 0) <= 0 => 'danger',
            $quantity < config('inventory.low_stock_threshold') => 'warning',
            default => 'success',
        };
    }

    public static function adjust(): Action
    {
        return Action::make('adjust')
            ->label('Ajustar stock')
            ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
            ->modalHeading(fn (Stock $record) => "Ajustar {$record->product->name} en {$record->branch->name}")
            ->modalDescription(fn (Stock $record) => "Existencia actual: {$record->quantity}. Cada ajuste queda en el historial.")
            ->modalSubmitActionLabel('Guardar ajuste')
            ->schema([
                Select::make('mode')
                    ->label('Tipo de ajuste')
                    ->options(self::MODES)
                    ->default('entrada')
                    ->live()
                    ->required(),
                TextInput::make('quantity')
                    ->label(fn (Get $get) => $get('mode') === 'conteo' ? 'Cantidad contada' : 'Piezas')
                    ->integer()
                    ->minValue(fn (Get $get) => $get('mode') === 'conteo' ? 0 : 1)
                    ->maxValue(100000)
                    ->required(),
                TextInput::make('reason')
                    ->label('Motivo')
                    ->maxLength(255)
                    ->required(fn (Get $get) => $get('mode') === 'salida'),
            ])
            ->action(function (Stock $record, array $data, Action $action) {
                $service = app(StockService::class);
                $quantity = (int) $data['quantity'];
                $reason = $data['reason'] ?? null;

                try {
                    match ($data['mode']) {
                        'entrada' => $service->move($record->branch, $record->product, StockMovementType::Entrada, $quantity, $reason, user: auth()->user()),
                        'salida' => $service->move($record->branch, $record->product, StockMovementType::Ajuste, -$quantity, $reason, user: auth()->user()),
                        'conteo' => $service->setCount($record->branch, $record->product, $quantity, $reason, auth()->user()),
                    };
                } catch (InsufficientStockException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();
                    $action->halt();
                }

                Notification::make()->title('Stock actualizado')->body('Quedan '.$record->refresh()->quantity.' piezas.')->success()->send();
            });
    }

    // Solo con existencia en 0; el historial se conserva
    public static function remove(): Action
    {
        return Action::make('remove')
            ->label('Quitar')
            ->icon(Heroicon::OutlinedTrash)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Quitar producto de la sucursal')
            ->action(function (Stock $record, Action $action) {
                if ($record->quantity > 0) {
                    Notification::make()
                        ->title('Aún tiene existencias')
                        ->body("Quedan {$record->quantity} piezas. Primero haz un ajuste a 0.")
                        ->danger()
                        ->send();
                    $action->halt();
                }

                $record->delete();
                Notification::make()->title('Producto quitado de la sucursal')->success()->send();
            });
    }

    // Alta del renglón con stock inicial opcional
    public static function addInitial(int $branchId, int $productId, int $quantity): void
    {
        $stock = Stock::query()->firstOrCreate(['branch_id' => $branchId, 'product_id' => $productId]);

        if ($quantity > 0) {
            app(StockService::class)->move($stock->branch, $stock->product, StockMovementType::Entrada, $quantity, 'Stock inicial', user: auth()->user());
        }
    }
}
