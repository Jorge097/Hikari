<?php

namespace Tests\Feature\Checkout;

use App\Enums\OrderOrigin;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\Payments\PaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\FakePaymentGateway;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use CatalogFixtures, RefreshDatabase;

    private FakePaymentGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCatalog();
        config(['services.stripe.secret' => 'sk_test_fake', 'hikari.shipping_national_cents' => 15000]);
        $this->gateway = new FakePaymentGateway;
        $this->app->instance(PaymentGateway::class, $this->gateway);
    }

    public function test_pagina_de_checkout_carga(): void
    {
        $this->get('/checkout')->assertOk();
    }

    public function test_crea_pedido_con_precios_del_servidor_y_redirige_a_stripe(): void
    {
        // Precio manipulado en el navegador: se ignora
        $data = $this->checkoutData();
        $data['items'][0]['price'] = 1;

        $this->post('/checkout', $data, ['X-Inertia' => 'true'])
            ->assertStatus(409)
            ->assertHeader('X-Inertia-Location', 'https://checkout.stripe.test/cs_test_1');

        $order = Order::with('items', 'payments', 'customer')->sole();

        $this->assertSame('HK-001', $order->folio);
        $this->assertSame(OrderOrigin::Web, $order->origin);
        $this->assertSame(OrderStatus::Pendiente, $order->status);
        // 2 × 290 + 1 × (390 + 260)
        $this->assertSame(123000, $order->total_cents);
        $this->assertSame(65000, $order->items[1]->unit_price_cents);
        $this->assertSame('Grande', $order->items[1]->size);
        $this->assertSame('250g', $order->items[0]->size);
        $this->assertSame('ana@example.com', $order->customer->email);
        $this->assertNotNull($order->privacy_accepted_at);
        $this->assertSame(config('hikari.privacy.version'), $order->privacy_version);

        $payment = $order->payments->sole();
        $this->assertSame('stripe', $payment->provider);
        $this->assertSame(PaymentStatus::Pendiente, $payment->status);
        $this->assertSame('cs_test_1', $payment->reference);
        $this->assertStringContainsString('{CHECKOUT_SESSION_ID}', $this->gateway->sessions['cs_test_1']['successUrl']);
    }

    public function test_exige_aceptar_aviso_de_privacidad(): void
    {
        $this->post('/checkout', $this->checkoutData(['privacy_accepted' => false]))
            ->assertSessionHasErrors('privacy_accepted');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_pagina_de_aviso_de_privacidad(): void
    {
        $this->get('/aviso-de-privacidad')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('AvisoPrivacidad')->has('privacy.version'));
    }

    public function test_envio_nacional_suma_costo_y_pide_direccion(): void
    {
        $this->post('/checkout', $this->checkoutData(['delivery' => 'nacional']))
            ->assertSessionHasErrors(['shipping_address', 'shipping_city', 'shipping_state', 'shipping_postal_code']);

        $this->post('/checkout', $this->checkoutData([
            'delivery' => 'nacional',
            'shipping_address' => 'Av. Juárez 100, Centro',
            'shipping_city' => 'Torreón',
            'shipping_state' => 'Coahuila',
            'shipping_postal_code' => '27000',
        ]));

        $order = Order::sole();
        $this->assertSame(15000, $order->shipping_cents);
        $this->assertSame(138000, $order->total_cents);
    }

    public function test_rechaza_aroma_inactivo_y_tamano_invalido(): void
    {
        $data = $this->checkoutData();
        $data['items'][0]['scent'] = 'Lavanda';
        $data['items'][1]['size'] = 'Gigante';

        $this->post('/checkout', $data)->assertSessionHasErrors('items.0.scent');
        $this->assertSame(0, Order::count());
    }

    public function test_rechaza_producto_inactivo(): void
    {
        $this->vela->update(['is_active' => false]);

        $this->post('/checkout', $this->checkoutData())->assertSessionHasErrors('items.0.product_id');
        $this->assertSame(0, Order::count());
    }

    public function test_sin_llaves_de_stripe_no_crea_pedido(): void
    {
        config(['services.stripe.secret' => null]);

        $this->post('/checkout', $this->checkoutData())->assertSessionHasErrors('payment');
        $this->assertSame(0, Order::count());
    }

    public function test_si_stripe_falla_el_pedido_queda_cancelado(): void
    {
        $this->gateway->fail = true;

        $this->post('/checkout', $this->checkoutData())->assertSessionHasErrors('payment');

        $this->assertSame(OrderStatus::Cancelado, Order::sole()->status);
    }

    public function test_cliente_existente_se_reutiliza_por_correo(): void
    {
        $this->post('/checkout', $this->checkoutData());
        $this->post('/checkout', $this->checkoutData(['email' => 'ANA@example.com', 'phone' => '8719999999']));

        $this->assertSame(2, Order::count());
        $this->assertSame(1, Order::query()->distinct()->count('customer_id'));
        $this->assertSame('8719999999', Order::latest('id')->first()->customer->phone);
    }

    public function test_pagina_de_confirmacion(): void
    {
        $this->post('/checkout', $this->checkoutData());

        $this->get('/pedido/confirmado?session_id=cs_test_1')
            ->assertOk()
            ->assertSee('HK-001');

        $this->get('/pedido/confirmado?session_id=cs_otra')->assertNotFound();
    }

    public function test_limite_de_intentos(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post('/checkout', ['items' => []]);
        }

        $this->post('/checkout', ['items' => []])->assertStatus(429);
    }
}
