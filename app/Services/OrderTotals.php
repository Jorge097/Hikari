<?php

namespace App\Services;

class OrderTotals
{
    /**
     * Calcula el desglose en centavos.
     *
     * Con precios con IVA incluido: subtotal y envío son montos finales y
     * tax es el IVA contenido. Sin IVA incluido: tax se suma al total.
     *
     * @param  array<int, array{quantity: int, unit_price_cents: int}>  $items
     * @return array{subtotal_cents: int, shipping_cents: int, tax_cents: int, total_cents: int}
     */
    public static function calculate(array $items, int $shippingCents, float $taxRate, bool $pricesIncludeTax): array
    {
        $subtotal = 0;

        foreach ($items as $item) {
            $subtotal += (int) $item['quantity'] * (int) $item['unit_price_cents'];
        }

        $base = $subtotal + $shippingCents;

        if ($pricesIncludeTax) {
            $tax = $base - (int) round($base / (1 + $taxRate));
            $total = $base;
        } else {
            $tax = (int) round($base * $taxRate);
            $total = $base + $tax;
        }

        return [
            'subtotal_cents' => $subtotal,
            'shipping_cents' => $shippingCents,
            'tax_cents' => $tax,
            'total_cents' => $total,
        ];
    }
}
