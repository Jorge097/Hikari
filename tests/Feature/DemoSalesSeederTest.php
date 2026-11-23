<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use Database\Seeders\DemoSalesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DemoSalesSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_carga_20_pedidos_en_todos_los_estados_sin_duplicar(): void
    {
        Mail::fake();
        $category = Category::create(['name' => 'Siempre', 'slug' => 'siempre', 'description' => '-', 'is_active' => true]);
        foreach (['Rosa', 'Tulipán', 'Peonia'] as $i => $name) {
            Product::create(['category_id' => $category->id, 'name' => $name, 'slug' => "p{$i}", 'description' => '-', 'price' => 200 + $i * 50, 'is_active' => true]);
        }

        $this->seed(DemoSalesSeeder::class);
        $this->seed(DemoSalesSeeder::class); // segunda corrida reemplaza

        $this->assertSame(20, Order::count());
        $this->assertSame('HK-001', Order::orderBy('id')->value('folio'));

        $byStatus = Order::all()->countBy(fn ($order) => $order->status->value)->all();
        $this->assertSame(['pendiente' => 3, 'pagado' => 4, 'enviado' => 4, 'entregado' => 6, 'reembolsado' => 2, 'cancelado' => 1], array_merge(
            array_fill_keys(['pendiente', 'pagado', 'enviado', 'entregado', 'reembolsado', 'cancelado'], 0),
            $byStatus,
        ));

        $this->assertSame(6, Invoice::count());
        $this->assertSame(3, Payment::where('type', 'reembolso')->count());
        $this->assertTrue(Payment::where('provider', 'stripe')->where('type', 'pago')->where('fee_cents', '>', 0)->exists());
        $this->assertTrue(Order::where('status', OrderStatus::Enviado)->whereNotNull('tracking_number')->exists());
        Mail::assertNothingQueued();
    }
}
