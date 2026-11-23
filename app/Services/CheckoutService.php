<?php

namespace App\Services;

use App\Enums\OrderOrigin;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Scent;
use App\Models\Size;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    // Crea el pedido web con precios del catálogo (nunca del navegador)
    public function createOrder(array $data): Order
    {
        $items = $this->resolveItems($data['items']);

        return DB::transaction(function () use ($data, $items) {
            $customer = Customer::firstOrNew(['email' => strtolower($data['email'])]);
            $customer->fill(['name' => $data['name'], 'phone' => $data['phone']])->save();

            $national = $data['delivery'] === 'nacional';

            $order = $customer->orders()->create([
                'origin' => OrderOrigin::Web,
                'currency' => 'MXN',
                'shipping_cents' => $national ? config('hikari.shipping_national_cents') : 0,
                'shipping_name' => $data['name'],
                'shipping_phone' => $data['phone'],
                'shipping_address' => $data['shipping_address'] ?? null,
                'shipping_city' => $data['shipping_city'] ?? null,
                'shipping_state' => $data['shipping_state'] ?? null,
                'shipping_postal_code' => $data['shipping_postal_code'] ?? null,
                'required_date' => $data['required_date'] ?? null,
                'notes' => trim(($national ? 'Envío nacional' : 'Entrega local / recoger')
                    ."\n".($data['notes'] ?? '')),
            ]);

            $order->items()->createMany($items);
            $order->recalculateTotals();

            return $order->refresh();
        });
    }

    private function resolveItems(array $input): array
    {
        $products = Product::with('category')
            ->whereIn('id', collect($input)->pluck('product_id'))
            ->where('is_active', true)
            ->whereHas('category', fn ($q) => $q->where('is_active', true))
            ->get()
            ->keyBy('id');

        $scents = Scent::where('is_active', true)->pluck('name');
        $sizes = Size::pluck('name');

        return collect($input)->map(function ($item, $i) use ($products, $scents, $sizes) {
            $product = $products->get($item['product_id']);

            if (! $product) {
                $this->fail("items.$i.product_id", 'Un producto ya no está disponible. Quítalo del carrito.');
            }

            if (! $scents->contains($item['scent'])) {
                $this->fail("items.$i.scent", "El aroma de {$product->name} ya no está disponible.");
            }

            // Tamaño elegido solo en Luminaria Imperial; en el resto, el del producto
            $size = $product->hasSizeOptions() ? ($item['size'] ?? null) : $product->size;

            if ($product->hasSizeOptions() && ! $sizes->contains($size)) {
                $this->fail("items.$i.size", "Elige un tamaño válido para {$product->name}.");
            }

            return [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'scent' => $item['scent'],
                'size' => $size,
                'quantity' => (int) $item['quantity'],
                'unit_price_cents' => $product->unitPriceCents($size),
            ];
        })->all();
    }

    private function fail(string $key, string $message): never
    {
        throw ValidationException::withMessages([$key => $message]);
    }
}
