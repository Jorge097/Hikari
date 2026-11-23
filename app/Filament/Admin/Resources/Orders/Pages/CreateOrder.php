<?php

namespace App\Filament\Admin\Resources\Orders\Pages;

use App\Enums\PaymentStatus;
use App\Filament\Admin\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    // Folio, partidas y pago en una sola transacción
    protected ?bool $hasDatabaseTransactions = true;

    protected array $initialPayment = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Separa los datos del pago inicial
        $this->initialPayment = Arr::only($data, ['register_payment', 'payment_method', 'payment_paid_at', 'payment_reference']);

        return Arr::except($data, array_keys($this->initialPayment));
    }

    protected function afterCreate(): void
    {
        /** @var Order $order */
        $order = $this->record;
        $order->recalculateTotals();

        if (! empty($this->initialPayment['register_payment'])) {
            $order->payments()->create([
                'method' => $this->initialPayment['payment_method'],
                'status' => PaymentStatus::Completado,
                'amount_cents' => $order->total_cents,
                'currency' => $order->currency,
                'paid_at' => $this->initialPayment['payment_paid_at'] ?? now(),
                'reference' => $this->initialPayment['payment_reference'] ?? null,
            ]);
        }
    }
}
