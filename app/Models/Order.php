<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\OrderOrigin;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Mail\OrderConfirmation;
use App\Services\FolioGenerator;
use App\Services\OrderTotals;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Mail;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id', 'origin', 'status', 'currency', 'exchange_rate',
        'shipping_cents',
        'shipping_name', 'shipping_phone', 'shipping_address', 'shipping_city',
        'shipping_state', 'shipping_postal_code', 'carrier', 'tracking_number', 'tracking_url',
        'required_date', 'notes', 'privacy_accepted_at', 'privacy_version',
    ];

    protected $attributes = [
        'origin' => 'web',
        'status' => 'pendiente',
        'currency' => 'MXN',
        'shipping_cents' => 0,
    ];

    protected function casts(): array
    {
        return [
            'origin' => OrderOrigin::class,
            'status' => OrderStatus::class,
            'exchange_rate' => 'decimal:6',
            'tax_rate' => 'decimal:4',
            'prices_include_tax' => 'boolean',
            'privacy_accepted_at' => 'datetime',
            'subtotal_cents' => 'integer',
            'shipping_cents' => 'integer',
            'tax_cents' => 'integer',
            'total_cents' => 'integer',
            'required_date' => 'date',
            'paid_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'confirmation_sent_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            // Folio y reglas fiscales vigentes al crear
            $order->folio ??= FolioGenerator::next();
            $order->tax_rate ??= config('hikari.tax_rate');
            $order->prices_include_tax ??= config('hikari.prices_include_tax');
        });

        static::saving(function (Order $order) {
            // Sin tipo de cambio en MXN
            if ($order->currency === 'MXN') {
                $order->exchange_rate = null;
            }

            // Fecha de cada cambio de estado
            if ($order->isDirty('status')) {
                $field = match ($order->status) {
                    OrderStatus::Enviado => 'shipped_at',
                    OrderStatus::Entregado => 'delivered_at',
                    OrderStatus::Cancelado => 'cancelled_at',
                    default => null,
                };

                if ($field && ! $order->{$field}) {
                    $order->{$field} = now();
                }
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    // Factura de ingreso vigente o en trámite
    public function activeInvoice(): ?Invoice
    {
        return $this->invoices
            ->where('type', 'I')
            ->whereIn('status', InvoiceStatus::active())
            ->sortByDesc('id')
            ->first();
    }

    // Total reembolsado en centavos
    public function refundedCents(): int
    {
        return (int) $this->payments()
            ->where('status', PaymentStatus::Completado)
            ->where('type', PaymentType::Reembolso)
            ->sum('amount_cents');
    }

    // Recalcula el desglose desde las partidas
    public function recalculateTotals(): void
    {
        $items = $this->items()->get(['quantity', 'unit_price_cents'])->toArray();

        $this->forceFill(OrderTotals::calculate(
            $items,
            (int) $this->shipping_cents,
            (float) $this->tax_rate,
            (bool) $this->prices_include_tax,
        ))->save();
    }

    // Ajusta estado y paid_at según los pagos completados
    public function syncPaymentStatus(): void
    {
        $completed = $this->payments()
            ->where('status', PaymentStatus::Completado)
            ->get(['type', 'amount_cents', 'paid_at']);

        $paid = $completed->where('type', PaymentType::Pago);
        $refunded = $completed->where('type', PaymentType::Reembolso)->sum('amount_cents');
        $net = $paid->sum('amount_cents') - $refunded;

        $justPaid = false;

        if ($this->status === OrderStatus::Pendiente && $this->total_cents > 0 && $net >= $this->total_cents) {
            $this->status = OrderStatus::Pagado;
            $this->paid_at = $paid->max('paid_at') ?? now();
            $justPaid = true;
        } elseif ($refunded > 0 && $net <= 0 && $this->status !== OrderStatus::Cancelado) {
            $this->status = OrderStatus::Reembolsado;
        } elseif ($this->status === OrderStatus::Pagado && $refunded === 0 && $net < $this->total_cents) {
            // Pago corregido o borrado: vuelve a pendiente
            $this->status = OrderStatus::Pendiente;
            $this->paid_at = null;
        }

        $this->save();

        if ($justPaid) {
            $this->sendConfirmation();
        }
    }

    // Correo de confirmación, una sola vez por pedido
    public function sendConfirmation(): void
    {
        $email = $this->customer?->email;

        if (! $email || $this->confirmation_sent_at) {
            return;
        }

        Mail::to($email)->queue(new OrderConfirmation($this));

        $this->forceFill(['confirmation_sent_at' => now()])->saveQuietly();
    }
}
