<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\Payments\PaymentGateway;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        // Verifica la firma de Stripe
        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature'),
                (string) config('services.stripe.webhook_secret'),
            );
        } catch (SignatureVerificationException|\UnexpectedValueException) {
            return response('Firma inválida', 400);
        }

        try {
            DB::transaction(function () use ($event) {
                // Idempotencia: cada evento una sola vez
                DB::table('stripe_events')->insert([
                    'id' => $event->id,
                    'type' => $event->type,
                    'processed_at' => now(),
                ]);

                $this->handle($event->type, $event->data->object);
            });
        } catch (UniqueConstraintViolationException) {
            return response('Evento ya procesado', 200);
        }

        return response('OK', 200);
    }

    private function handle(string $type, object $session): void
    {
        if (! str_starts_with($type, 'checkout.session.')) {
            return;
        }

        $payment = Payment::with('order')
            ->where('provider', 'stripe')
            ->where('reference', $session->id)
            ->lockForUpdate()
            ->first();

        if (! $payment) {
            Log::warning('Stripe: sesión sin pago local', ['session' => $session->id, 'type' => $type]);

            return;
        }

        match ($type) {
            // Tarjeta: pagado al completar. OXXO/SPEI: espera evento async
            'checkout.session.completed' => $session->payment_status === 'paid'
                ? $this->markPaid($payment, $session)
                : $this->markAwaiting($payment, $session),
            'checkout.session.async_payment_succeeded' => $this->markPaid($payment, $session),
            'checkout.session.async_payment_failed' => $this->markFailed($payment, cancelOrder: false),
            'checkout.session.expired' => $this->markFailed($payment, cancelOrder: true),
            default => null,
        };
    }

    private function markAwaiting(Payment $payment, object $session): void
    {
        $payment->fill([
            'stripe_payment_intent_id' => $session->payment_intent,
            'method' => $this->method($session->payment_intent),
        ])->save();
    }

    private function markPaid(Payment $payment, object $session): void
    {
        if ($session->amount_total !== $payment->amount_cents) {
            Log::warning('Stripe: monto distinto al del pedido', [
                'folio' => $payment->order->folio,
                'stripe' => $session->amount_total,
                'pedido' => $payment->amount_cents,
            ]);
        }

        $payment->fill([
            'status' => PaymentStatus::Completado,
            'amount_cents' => $session->amount_total,
            'stripe_payment_intent_id' => $session->payment_intent,
            'method' => $payment->method ?? $this->method($session->payment_intent),
            'fee_cents' => $this->fee($session->payment_intent),
            'paid_at' => now(),
        ])->save();
    }

    // La comisión no debe frenar el pago: si falla, se sincroniza después
    private function fee(?string $paymentIntentId): ?int
    {
        if (! $paymentIntentId) {
            return null;
        }

        try {
            return app(PaymentGateway::class)->feeCents($paymentIntentId);
        } catch (\Throwable $e) {
            Log::warning('Stripe: no se obtuvo la comisión', ['error' => class_basename($e)]);

            return null;
        }
    }

    private function markFailed(Payment $payment, bool $cancelOrder): void
    {
        if ($payment->status === PaymentStatus::Completado) {
            return;
        }

        $payment->update(['status' => PaymentStatus::Fallido]);

        // Sesión vencida sin pago: se cancela el pedido
        if ($cancelOrder && $payment->order->status === OrderStatus::Pendiente) {
            $payment->order->update(['status' => OrderStatus::Cancelado]);
        }
    }

    private function method(?string $paymentIntentId): ?PaymentMethod
    {
        if (! $paymentIntentId) {
            return null;
        }

        return match (app(PaymentGateway::class)->paymentMethodType($paymentIntentId)) {
            'card' => PaymentMethod::Tarjeta,
            'oxxo' => PaymentMethod::Oxxo,
            'customer_balance' => PaymentMethod::Spei,
            default => null,
        };
    }
}
