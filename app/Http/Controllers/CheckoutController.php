<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Requests\CheckoutRequest;
use App\Models\Payment;
use App\Services\CheckoutService;
use App\Services\Payments\PaymentGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class CheckoutController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Checkout', [
            'shippingNationalCents' => config('hikari.shipping_national_cents'),
            'onlinePaymentsEnabled' => $this->stripeConfigured(),
        ]);
    }

    public function store(CheckoutRequest $request, CheckoutService $checkout): HttpResponse|RedirectResponse
    {
        if (! $this->stripeConfigured()) {
            return back()->withErrors(['payment' => 'Los pagos en línea aún no están disponibles. Envía tu pedido por WhatsApp.']);
        }

        $order = $checkout->createOrder($request->validated());

        try {
            $session = app(PaymentGateway::class)->createCheckoutSession(
                $order,
                route('checkout.success').'?session_id={CHECKOUT_SESSION_ID}',
                route('checkout.cancel'),
            );
        } catch (\Throwable $e) {
            Log::error('Stripe: no se pudo crear la sesión', ['folio' => $order->folio, 'error' => $e->getMessage()]);
            $order->update(['status' => OrderStatus::Cancelado, 'notes' => trim($order->notes."\nNo se pudo iniciar el pago en Stripe.")]);

            return back()->withErrors(['payment' => 'No pudimos iniciar el pago. Intenta de nuevo en unos minutos.']);
        }

        // Pago pendiente ligado a la sesión
        $order->payments()->create([
            'provider' => 'stripe',
            'status' => PaymentStatus::Pendiente,
            'amount_cents' => $order->total_cents,
            'currency' => $order->currency,
            'reference' => $session['id'],
        ]);

        // Redirección completa al Checkout de Stripe
        return Inertia::location($session['url']);
    }

    public function success(Request $request): Response
    {
        $payment = Payment::with('order')
            ->where('provider', 'stripe')
            ->where('reference', (string) $request->query('session_id'))
            ->first();

        abort_unless($payment, 404);

        $order = $payment->order;

        return Inertia::render('CheckoutSuccess', [
            'order' => [
                'folio' => $order->folio,
                'status' => $order->status->value,
                'status_label' => $order->status->getLabel(),
                'total_cents' => $order->total_cents,
                'currency' => $order->currency,
            ],
        ]);
    }

    public function cancel(): RedirectResponse
    {
        return redirect()->route('cart')->with('message', 'Cancelaste el pago. Tu carrito sigue aquí.');
    }

    private function stripeConfigured(): bool
    {
        return filled(config('services.stripe.secret'));
    }
}
