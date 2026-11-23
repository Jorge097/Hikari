<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'type', 'provider', 'method', 'status', 'amount_cents', 'fee_cents', 'currency',
        'reference', 'stripe_payment_intent_id', 'paid_at', 'notes',
    ];

    protected $attributes = [
        'type' => 'pago',
        'provider' => 'manual',
        'status' => 'pendiente',
        'currency' => 'MXN',
    ];

    protected function casts(): array
    {
        return [
            'type' => PaymentType::class,
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'amount_cents' => 'integer',
            'fee_cents' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Payment $payment) {
            // Completado sin fecha: ahora
            if ($payment->status === PaymentStatus::Completado && ! $payment->paid_at) {
                $payment->paid_at = now();
            }
        });

        // Mantiene el estado del pedido al día
        static::saved(fn (Payment $payment) => $payment->order->syncPaymentStatus());
        static::deleted(fn (Payment $payment) => $payment->order->syncPaymentStatus());
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
