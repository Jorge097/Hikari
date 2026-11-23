<?php

namespace Tests\Unit;

use App\Services\OrderTotals;
use PHPUnit\Framework\TestCase;

class OrderTotalsTest extends TestCase
{
    public function test_precios_con_iva_incluido(): void
    {
        // 2 x $290 + envío $150 = $730, IVA contenido = 730 - 730/1.16
        $totals = OrderTotals::calculate(
            [['quantity' => 2, 'unit_price_cents' => 29000]],
            15000,
            0.16,
            true,
        );

        $this->assertSame(58000, $totals['subtotal_cents']);
        $this->assertSame(15000, $totals['shipping_cents']);
        $this->assertSame(10069, $totals['tax_cents']);
        $this->assertSame(73000, $totals['total_cents']);
    }

    public function test_precios_sin_iva(): void
    {
        $totals = OrderTotals::calculate(
            [
                ['quantity' => 1, 'unit_price_cents' => 10000],
                ['quantity' => 3, 'unit_price_cents' => 5000],
            ],
            0,
            0.16,
            false,
        );

        $this->assertSame(25000, $totals['subtotal_cents']);
        $this->assertSame(4000, $totals['tax_cents']);
        $this->assertSame(29000, $totals['total_cents']);
    }

    public function test_pedido_vacio(): void
    {
        $totals = OrderTotals::calculate([], 0, 0.16, true);

        $this->assertSame(0, $totals['total_cents']);
        $this->assertSame(0, $totals['tax_cents']);
    }
}
