<?php

namespace Tests\Feature\Orders;

use App\Enums\OrderOrigin;
use App\Enums\OrderStatus;
use App\Filament\Admin\Resources\Orders\Pages\CreateOrder;
use App\Filament\Admin\Resources\Orders\Pages\ListOrders;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrderPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.admin_emails' => ['admin@hikari.test']]);
        $this->actingAs(User::factory()->create(['email' => 'admin@hikari.test']));
    }

    public function test_lista_de_pedidos_carga(): void
    {
        $this->get('/panel_hikari/orders')->assertOk();
    }

    public function test_paginas_del_panel_cargan(): void
    {
        $order = Customer::factory()->create()->orders()->create();
        $order->items()->create(['product_name' => 'Vela', 'quantity' => 1, 'unit_price_cents' => 10000]);
        $order->recalculateTotals();
        $order->payments()->create(['method' => 'efectivo', 'status' => 'completado', 'amount_cents' => 10000]);

        $this->get('/panel_hikari/orders/create')->assertOk();
        $this->get("/panel_hikari/orders/{$order->id}/edit")->assertOk()->assertSee('HK-001');
        $this->get('/panel_hikari/customers')->assertOk();
    }

    public function test_usuario_no_admin_no_entra(): void
    {
        $this->actingAs(User::factory()->create(['email' => 'otro@hikari.test']));

        $this->get('/panel_hikari/orders')->assertForbidden();
    }

    public function test_registra_pedido_de_whatsapp_pagado(): void
    {
        $customer = Customer::factory()->create();

        Livewire::test(CreateOrder::class)
            ->fillForm([
                'customer_id' => $customer->id,
                'origin' => OrderOrigin::Whatsapp->value,
                'status' => OrderStatus::Pendiente->value,
                'currency' => 'MXN',
                'shipping_cents' => 120,
                'items' => [
                    ['product_name' => 'Vela aromática', 'quantity' => 30, 'unit_price_cents' => 85.5],
                ],
                'register_payment' => true,
                'payment_method' => 'transferencia',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $order = Order::with('items', 'payments')->sole();

        $this->assertSame('HK-001', $order->folio);
        $this->assertSame(OrderOrigin::Whatsapp, $order->origin);
        $this->assertSame(8550, $order->items->first()->unit_price_cents);
        $this->assertSame(256500, $order->subtotal_cents);
        $this->assertSame(12000, $order->shipping_cents);
        $this->assertSame(268500, $order->total_cents);
        $this->assertSame(268500, $order->payments->first()->amount_cents);
        $this->assertSame(OrderStatus::Pagado, $order->status);

        Livewire::test(ListOrders::class)->assertCanSeeTableRecords([$order]);
    }
}
