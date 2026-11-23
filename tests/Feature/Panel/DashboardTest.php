<?php

namespace Tests\Feature\Panel;

use App\Enums\UserRole;
use App\Filament\Admin\Widgets\LatestOrders;
use App\Filament\Admin\Widgets\SalesChart;
use App\Filament\Admin\Widgets\SalesOverview;
use App\Filament\Admin\Widgets\StripeFeesChart;
use App\Filament\Admin\Widgets\TopCandlesChart;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Services\Reports\DashboardMetrics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->travelTo(now('America/Mexico_City')->setDate(2026, 11, 20)->setTime(12, 0));
    }

    // Pedido pagado por Stripe en la fecha indicada
    private function sale(string $date, array $items, ?int $fee = null, string $provider = 'stripe'): Order
    {
        $order = Customer::factory()->create()->orders()->create();
        foreach ($items as $name => $qty) {
            $order->items()->create(['product_name' => $name, 'quantity' => $qty, 'unit_price_cents' => 10000]);
        }
        $order->recalculateTotals();
        $order->payments()->create([
            'provider' => $provider, 'method' => 'tarjeta', 'status' => 'completado',
            'amount_cents' => $order->total_cents, 'fee_cents' => $fee, 'paid_at' => "{$date} 10:00",
        ]);

        return $order->refresh();
    }

    public function test_resumen_del_mes_con_neto_y_comisiones(): void
    {
        $this->sale('2026-11-03', ['Vela lavanda' => 2], fee: 1000);
        $refunded = $this->sale('2026-11-10', ['Vela vainilla' => 1], fee: 500);
        $refunded->payments()->create(['type' => 'reembolso', 'method' => 'tarjeta', 'status' => 'completado', 'amount_cents' => 4000, 'paid_at' => '2026-11-12 09:00']);
        $this->sale('2026-10-28', ['Vela lavanda' => 5], fee: 9999); // mes anterior
        Customer::factory()->create()->orders()->create(); // sin pagar

        $summary = (new DashboardMetrics('month'))->summary();

        $this->assertSame(30000, $summary['gross']);
        $this->assertSame(4000, $summary['refunds']);
        $this->assertSame(1500, $summary['fees']);
        $this->assertSame(30000 - 4000 - 1500, $summary['net']);
        $this->assertSame(2, $summary['orders']);
        $this->assertSame(15000, $summary['average']);
    }

    public function test_serie_diaria_y_mensual(): void
    {
        $this->sale('2026-11-03', ['Vela' => 1], fee: 300);
        $this->sale('2026-11-03', ['Vela' => 1], fee: 300);
        $this->sale('2026-06-15', ['Vela' => 1], fee: 300);

        $daily = (new DashboardMetrics('month'))->series();
        $this->assertCount(20, $daily['labels']);
        $this->assertSame('3 nov', $daily['labels'][2]);
        $this->assertEquals(200.0, $daily['gross'][2]);
        $this->assertEquals(194.0, $daily['net'][2]);
        $this->assertEquals(6.0, $daily['fees'][2]);
        $this->assertEquals(0.0, $daily['gross'][0]);

        $monthly = (new DashboardMetrics('12m'))->series();
        $this->assertCount(12, $monthly['labels']);
        $this->assertSame('jun 2026', $monthly['labels'][6]);
        $this->assertEquals(100.0, $monthly['gross'][6]);
        $this->assertEquals(200.0, $monthly['gross'][11]);
    }

    public function test_velas_mas_vendidas_agrupa_el_resto(): void
    {
        $this->sale('2026-11-05', ['A' => 9, 'B' => 7, 'C' => 5]);
        $this->sale('2026-11-06', ['A' => 1, 'D' => 2, 'E' => 1]);

        $this->assertSame(['A' => 10, 'B' => 7, 'Otras' => 8], (new DashboardMetrics('month'))->topProducts(2));
        $this->assertSame(['A' => 10, 'B' => 7, 'C' => 5, 'D' => 2, 'E' => 1], (new DashboardMetrics('month'))->topProducts());
    }

    public function test_dashboard_carga_para_admin_y_contador(): void
    {
        $this->sale('2026-11-03', ['Vela lavanda' => 2], fee: 1000);

        foreach ([UserRole::Admin, UserRole::Contador] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));

            $this->get('/panel_hikari')->assertOk()->assertSee('Periodo');

            Livewire::test(SalesOverview::class, ['pageFilters' => ['period' => 'month']])
                ->assertSee('Ingreso neto')
                ->assertSee('$190.00');
            Livewire::test(SalesChart::class, ['pageFilters' => ['period' => '12m']])->assertOk();
            Livewire::test(StripeFeesChart::class)->assertOk();
            Livewire::test(TopCandlesChart::class)->assertOk();
        }
    }

    public function test_ultimos_20_pedidos(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $customer = Customer::factory()->create();
        $orders = collect(range(1, 22))->map(fn ($i) => tap($customer->orders()->create(), fn ($o) => $o->forceFill(['created_at' => now()->subMinutes(30 - $i)])->save()));

        Livewire::test(LatestOrders::class)
            ->assertCanSeeTableRecords($orders->slice(2))
            ->assertCanNotSeeTableRecords($orders->take(2))
            ->assertSee('HK-022');
    }
}
