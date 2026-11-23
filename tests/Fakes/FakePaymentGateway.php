<?php

namespace Tests\Fakes;

use App\Models\Order;
use App\Services\Payments\PaymentGateway;

class FakePaymentGateway implements PaymentGateway
{
    public array $sessions = [];

    public string $methodType = 'card';

    public bool $fail = false;

    public function createCheckoutSession(Order $order, string $successUrl, string $cancelUrl): array
    {
        if ($this->fail) {
            throw new \RuntimeException('Stripe caído');
        }

        $id = 'cs_test_'.$order->id;
        $this->sessions[$id] = compact('order', 'successUrl', 'cancelUrl');

        return ['id' => $id, 'url' => "https://checkout.stripe.test/{$id}"];
    }

    public function paymentMethodType(string $paymentIntentId): ?string
    {
        return $this->methodType;
    }

    public ?int $fee = 1234;

    public function feeCents(string $paymentIntentId): ?int
    {
        return $this->fee;
    }
}
