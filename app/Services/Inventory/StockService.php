<?php

namespace App\Services\Inventory;

use App\Enums\StockMovementType;
use App\Models\Branch;
use App\Models\Order;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// Único lugar que cambia existencias: movimiento + saldo en una transacción con bloqueo
class StockService
{
    public function move(
        Branch $branch,
        Product $product,
        StockMovementType $type,
        int $quantity,
        ?string $reason = null,
        ?Order $order = null,
        ?User $user = null,
    ): StockMovement {
        if ($quantity === 0) {
            throw new InsufficientStockException('La cantidad no puede ser 0.');
        }

        return DB::transaction(function () use ($branch, $product, $type, $quantity, $reason, $order, $user) {
            Stock::query()->firstOrCreate(['branch_id' => $branch->id, 'product_id' => $product->id]);

            $stock = Stock::query()
                ->where('branch_id', $branch->id)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first();

            $result = $stock->quantity + $quantity;

            if ($result < 0) {
                throw new InsufficientStockException(
                    "No alcanza: {$product->name} tiene {$stock->quantity} en {$branch->name}."
                );
            }

            $stock->update(['quantity' => $result]);

            return StockMovement::create([
                'branch_id' => $branch->id,
                'product_id' => $product->id,
                'type' => $type,
                'quantity' => $quantity,
                'resulting_stock' => $result,
                'reason' => $reason,
                'order_id' => $order?->id,
                'user_id' => $user?->id,
            ]);
        });
    }

    // Conteo físico: deja la existencia en $count
    public function setCount(Branch $branch, Product $product, int $count, ?string $reason = null, ?User $user = null): ?StockMovement
    {
        if ($count < 0) {
            throw new InsufficientStockException('El conteo no puede ser negativo.');
        }

        return DB::transaction(function () use ($branch, $product, $count, $reason, $user) {
            $current = (int) Stock::query()
                ->where('branch_id', $branch->id)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->value('quantity');

            if ($count === $current) {
                return null;
            }

            return $this->move($branch, $product, StockMovementType::Ajuste, $count - $current, $reason ?: 'Conteo físico', user: $user);
        });
    }
}
