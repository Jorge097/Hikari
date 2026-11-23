<?php

namespace App\Services\Payments;

use App\Models\Order;
use Stripe\StripeClient;

class StripeGateway implements PaymentGateway
{
    public function __construct(private StripeClient $stripe) {}

    public function createCheckoutSession(Order $order, string $successUrl, string $cancelUrl): array
    {
        $order->loadMissing('items', 'customer');
        $currency = strtolower($order->currency);

        // Partidas con los precios ya calculados en el servidor
        $lineItems = $order->items->map(fn ($item) => [
            'quantity' => $item->quantity,
            'price_data' => [
                'currency' => $currency,
                'unit_amount' => $item->unit_price_cents,
                'product_data' => [
                    'name' => $item->product_name,
                    'description' => collect([$item->scent, $item->size])->filter()->join(' · ') ?: null,
                ],
            ],
        ])->all();

        // IVA aparte solo si los precios no lo incluyen
        if (! $order->prices_include_tax && $order->tax_cents > 0) {
            $lineItems[] = [
                'quantity' => 1,
                'price_data' => [
                    'currency' => $currency,
                    'unit_amount' => $order->tax_cents,
                    'product_data' => ['name' => 'IVA'],
                ],
            ];
        }

        $params = [
            'mode' => 'payment',
            'locale' => 'es',
            'line_items' => $lineItems,
            'customer_email' => $order->customer->email,
            // SPEI (transferencia) requiere un Customer en Stripe
            'customer_creation' => 'always',
            'client_reference_id' => (string) $order->id,
            'metadata' => ['order_id' => $order->id, 'folio' => $order->folio],
            'payment_intent_data' => [
                'description' => "Pedido {$order->folio}",
                'metadata' => ['order_id' => $order->id, 'folio' => $order->folio],
            ],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ];

        if ($order->shipping_cents > 0) {
            $params['shipping_options'] = [[
                'shipping_rate_data' => [
                    'type' => 'fixed_amount',
                    'display_name' => 'Envío nacional',
                    'fixed_amount' => ['amount' => $order->shipping_cents, 'currency' => $currency],
                ],
            ]];
        }

        // Métodos (tarjeta, MSI, OXXO, SPEI) se activan en el Dashboard
        $session = $this->stripe->checkout->sessions->create($params, [
            'idempotency_key' => "checkout-order-{$order->id}",
        ]);

        return ['id' => $session->id, 'url' => $session->url];
    }

    public function paymentMethodType(string $paymentIntentId): ?string
    {
        $intent = $this->stripe->paymentIntents->retrieve($paymentIntentId, [
            'expand' => ['payment_method'],
        ]);

        return $intent->payment_method->type ?? null;
    }

    public function feeCents(string $paymentIntentId): ?int
    {
        $intent = $this->stripe->paymentIntents->retrieve($paymentIntentId, [
            'expand' => ['latest_charge.balance_transaction'],
        ]);

        $transaction = $intent->latest_charge->balance_transaction ?? null;

        return is_object($transaction) ? (int) $transaction->fee : null;
    }
}
