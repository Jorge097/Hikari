<?php

namespace Tests\Feature\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderPaymentsTest extends TestCase
{
    use RefreshDatabase;

    private function order(): Order
    {
        $order = Customer::factory()->create()->orders()->create(['shipping_cents' => 10000]);
        $order->items()->create([
            'product_name' => 'Vela de soya',
            'quantity' => 2,
            'unit_price_cents' => 25000,
        ]);
        $order->recalculateTotals();

        return $order->refresh();
    }

    public function test_recalcula_totales_desde_partidas(): void
    {
        $order = $this->order();

        $this->assertSame(50000, $order->items->first()->total_cents);
        $this->assertSame(50000, $order->subtotal_cents);
        $this->assertSame(60000, $order->total_cents);
        $this->assertSame(8276, $order->tax_cents);
    }

    public function test_pago_completo_marca_pagado(): void
    {
        $order = $this->order();

        $order->payments()->create([
            'method' => PaymentMethod::Transferencia,
            'status' => PaymentStatus::Completado,
            'amount_cents' => 60000,
            'paid_at' => '2026-11-20 10:00:00',
        ]);

        $order->refresh();
        $this->assertSame(OrderStatus::Pagado, $order->status);
        $this->assertSame('2026-11-20 10:00:00', $order->paid_at->format('Y-m-d H:i:s'));
    }

    public function test_pago_parcial_sigue_pendiente(): void
    {
        $order = $this->order();

        $order->payments()->create([
            'method' => PaymentMethod::Efectivo,
            'status' => PaymentStatus::Completado,
            'amount_cents' => 30000,
        ]);

        $this->assertSame(OrderStatus::Pendiente, $order->refresh()->status);
        $this->assertNull($order->paid_at);
    }

    public function test_pago_pendiente_no_cuenta(): void
    {
        $order = $this->order();

        $order->payments()->create([
            'method' => PaymentMethod::Spei,
            'status' => PaymentStatus::Pendiente,
            'amount_cents' => 60000,
        ]);

        $this->assertSame(OrderStatus::Pendiente, $order->refresh()->status);
    }

    public function test_borrar_pago_regresa_a_pendiente(): void
    {
        $order = $this->order();
        $payment = $order->payments()->create([
            'method' => PaymentMethod::Efectivo,
            'status' => PaymentStatus::Completado,
            'amount_cents' => 60000,
        ]);

        $payment->delete();

        $order->refresh();
        $this->assertSame(OrderStatus::Pendiente, $order->status);
        $this->assertNull($order->paid_at);
    }

    public function test_reembolso_total_marca_reembolsado(): void
    {
        $order = $this->order();
        $order->payments()->create([
            'method' => PaymentMethod::Tarjeta,
            'status' => PaymentStatus::Completado,
            'amount_cents' => 60000,
        ]);

        $order->payments()->create([
            'type' => PaymentType::Reembolso,
            'method' => PaymentMethod::Tarjeta,
            'status' => PaymentStatus::Completado,
            'amount_cents' => 60000,
        ]);

        $order->refresh();
        $this->assertSame(OrderStatus::Reembolsado, $order->status);
        $this->assertNotNull($order->paid_at);
    }

    public function test_cambio_de_estado_guarda_fecha(): void
    {
        $order = $this->order();

        $order->update(['status' => OrderStatus::Enviado]);

        $this->assertNotNull($order->refresh()->shipped_at);
    }

    public function test_mxn_no_guarda_tipo_de_cambio(): void
    {
        $order = $this->order();

        $order->update(['exchange_rate' => 18.5]);

        $this->assertNull($order->refresh()->exchange_rate);
    }
}
