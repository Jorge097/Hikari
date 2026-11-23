<?php

namespace App\Services\Payments;

use App\Models\Order;

interface PaymentGateway
{
    /**
     * Crea la sesión de pago alojada.
     *
     * @return array{id: string, url: string}
     */
    public function createCheckoutSession(Order $order, string $successUrl, string $cancelUrl): array;

    // Tipo de método usado (card, oxxo, customer_balance...)
    public function paymentMethodType(string $paymentIntentId): ?string;
}
