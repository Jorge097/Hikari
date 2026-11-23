<?php

namespace Tests\Feature\Panel;

use App\Enums\UserRole;
use App\Filament\Admin\Resources\MonthlySalesReports\Pages\ListMonthlySalesReports;
use App\Filament\Admin\Resources\Orders\Pages\ListOrders;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RolesTest extends TestCase
{
    use RefreshDatabase;

    private function order()
    {
        $order = Customer::factory()->create()->orders()->create();
        $order->items()->create(['product_name' => 'Vela', 'quantity' => 1, 'unit_price_cents' => 10000]);
        $order->recalculateTotals();

        return $order;
    }

    public function test_contador_consulta_ventas_en_solo_lectura(): void
    {
        $order = $this->order();
        $this->actingAs(User::factory()->create(['role' => UserRole::Contador]));

        $this->get('/panel_hikari/orders')->assertOk();
        $this->get("/panel_hikari/orders/{$order->id}")->assertOk()->assertSee('HK-001');
        $this->get('/panel_hikari/customers')->assertOk();
        $this->get('/panel_hikari/monthly-sales-reports')->assertOk();

        $this->get('/panel_hikari/orders/create')->assertForbidden();
        $this->get("/panel_hikari/orders/{$order->id}/edit")->assertForbidden();
        $this->get("/panel_hikari/customers/{$order->customer_id}/edit")->assertForbidden();
    }

    public function test_contador_no_ve_catalogo(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Contador]));

        $this->get('/panel_hikari/products')->assertForbidden();
        $this->get('/panel_hikari/categories')->assertForbidden();
    }

    public function test_contador_exporta_y_consulta_meses(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Contador]));

        Livewire::test(ListOrders::class)
            ->assertActionVisible('export_xlsx')
            ->assertActionHidden('create');

        Livewire::test(ListMonthlySalesReports::class)->assertActionVisible('open_month');
    }

    public function test_usuario_sin_rol_no_entra(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/panel_hikari/orders')->assertForbidden();
    }

    public function test_admin_por_rol_entra_sin_estar_en_admin_emails(): void
    {
        config(['app.admin_emails' => []]);
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $this->get('/panel_hikari/products')->assertOk();
    }

    public function test_usuarios_ya_no_esta_en_el_panel(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $this->get('/panel_hikari/users')->assertNotFound();
    }
}
