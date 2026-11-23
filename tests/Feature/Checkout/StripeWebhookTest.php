<?php

namespace Tests\Feature\Checkout;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Mail\OrderConfirmation;
use App\Models\Order;
use App\Services\Payments\PaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Tests\Fakes\FakePaymentGateway;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use CatalogFixtures, RefreshDatabase;

    private const SECRET = 'whsec_test';

    private FakePaymentGateway $gateway;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->seedCatalog();
        config(['services.stripe.secret' => 'sk_test_fake', 'services.stripe.webhook_secret' => self::SECRET]);
        $this->gateway = new FakePaymentGateway;
        $this->app->instance(PaymentGateway::class, $this->gateway);

        $this->post('/checkout', $this->checkoutData());
        $this->order = Order::sole();
    }

    // Evento firmado como lo hace Stripe
    private function sendEvent(string $type, array $session, string $id = 'evt_1', ?string $secret = null): TestResponse
    {
        $payload = json_encode([
            'id' => $id,
            'object' => 'event',
            'type' => $type,
            'data' => ['object' => array_merge([
                'id' => 'cs_test_1',
                'object' => 'checkout.session',
                'amount_total' => $this->order->total_cents,
                'payment_intent' => 'pi_test_1',
                'payment_status' => 'paid',
            ], $session)],
        ]);

        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret ?? self::SECRET);

        return $this->call('POST', '/stripe/webhook', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
            'CONTENT_TYPE' => 'application/json',
        ], $payload);
    }

    public function test_firma_invalida_se_rechaza(): void
    {
        $this->sendEvent('checkout.session.completed', [], secret: 'whsec_otro')->assertStatus(400);

        $this->assertSame(OrderStatus::Pendiente, $this->order->refresh()->status);
    }

    public function test_pago_con_tarjeta_marca_pagado_y_envia_correo(): void
    {
        $this->sendEvent('checkout.session.completed', [])->assertOk();

        $this->order->refresh();
        $payment = $this->order->payments->sole();

        $this->assertSame(OrderStatus::Pagado, $this->order->status);
        $this->assertNotNull($this->order->paid_at);
        $this->assertSame(PaymentStatus::Completado, $payment->status);
        $this->assertSame(PaymentMethod::Tarjeta, $payment->method);
        $this->assertSame('pi_test_1', $payment->stripe_payment_intent_id);
        $this->assertSame(1234, $payment->fee_cents);

        Mail::assertQueued(OrderConfirmation::class, fn ($mail) => $mail->hasTo('ana@example.com'));
        $this->assertNotNull($this->order->confirmation_sent_at);
    }

    public function test_si_falla_la_comision_el_pago_igual_se_registra(): void
    {
        $this->app->instance(PaymentGateway::class, new class extends FakePaymentGateway
        {
            public function feeCents(string $paymentIntentId): ?int
            {
                throw new \RuntimeException('Stripe caído');
            }
        });

        $this->sendEvent('checkout.session.completed', [])->assertOk();

        $payment = $this->order->payments()->sole();
        $this->assertSame(PaymentStatus::Completado, $payment->status);
        $this->assertNull($payment->fee_cents);
    }

    public function test_sincroniza_comisiones_faltantes(): void
    {
        $this->gateway->fee = null;
        $this->sendEvent('checkout.session.completed', [])->assertOk();
        $this->assertNull($this->order->payments()->sole()->fee_cents);

        $this->gateway->fee = 2500;
        $this->artisan('stripe:sync-fees')->expectsOutput('Comisiones actualizadas: 1')->assertSuccessful();

        $this->assertSame(2500, $this->order->payments()->sole()->fee_cents);
    }

    public function test_evento_repetido_se_procesa_una_vez(): void
    {
        $this->sendEvent('checkout.session.completed', [])->assertOk();
        $this->sendEvent('checkout.session.completed', [])->assertOk();

        Mail::assertQueuedCount(1);
        $this->assertDatabaseCount('stripe_events', 1);
    }

    public function test_oxxo_queda_pendiente_hasta_confirmar(): void
    {
        $this->gateway->methodType = 'oxxo';

        $this->sendEvent('checkout.session.completed', ['payment_status' => 'unpaid'])->assertOk();

        $this->order->refresh();
        $this->assertSame(OrderStatus::Pendiente, $this->order->status);
        $this->assertSame(PaymentMethod::Oxxo, $this->order->payments->sole()->method);
        Mail::assertNothingQueued();

        $this->sendEvent('checkout.session.async_payment_succeeded', [], 'evt_2')->assertOk();

        $this->assertSame(OrderStatus::Pagado, $this->order->refresh()->status);
        Mail::assertQueued(OrderConfirmation::class);
    }

    public function test_spei_fallido_marca_pago_fallido(): void
    {
        $this->gateway->methodType = 'customer_balance';

        $this->sendEvent('checkout.session.completed', ['payment_status' => 'unpaid']);
        $this->sendEvent('checkout.session.async_payment_failed', ['payment_status' => 'unpaid'], 'evt_2');

        $payment = $this->order->payments()->sole();
        $this->assertSame(PaymentMethod::Spei, $payment->method);
        $this->assertSame(PaymentStatus::Fallido, $payment->status);
        $this->assertSame(OrderStatus::Pendiente, $this->order->refresh()->status);
    }

    public function test_sesion_vencida_cancela_pedido(): void
    {
        $this->sendEvent('checkout.session.expired', ['payment_status' => 'unpaid', 'payment_intent' => null])->assertOk();

        $this->assertSame(OrderStatus::Cancelado, $this->order->refresh()->status);
        $this->assertSame(PaymentStatus::Fallido, $this->order->payments()->sole()->status);
    }

    public function test_sesion_desconocida_se_ignora(): void
    {
        $this->sendEvent('checkout.session.completed', ['id' => 'cs_otra'])->assertOk();

        $this->assertSame(OrderStatus::Pendiente, $this->order->refresh()->status);
    }

    public function test_correo_muestra_folio_desglose_y_metodo(): void
    {
        $this->sendEvent('checkout.session.completed', []);

        $html = (new OrderConfirmation($this->order->refresh()))->render();

        $this->assertStringContainsString('HK-001', $html);
        $this->assertStringContainsString('Luminaria', $html);
        $this->assertStringContainsString('$1,230.00 MXN', $html);
        $this->assertStringContainsString('Tarjeta', $html);
        $this->assertStringContainsString('IVA (incluido)', $html);
    }

    public function test_pedido_manual_pagado_tambien_envia_correo_una_vez(): void
    {
        $this->order->payments()->create(['method' => 'efectivo', 'status' => 'completado', 'amount_cents' => $this->order->total_cents]);
        $this->order->refresh()->sendConfirmation();

        Mail::assertQueuedCount(1);
    }
}
