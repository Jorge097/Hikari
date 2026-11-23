<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'order_id', 'type', 'status',
        'receiver_rfc', 'receiver_name', 'receiver_tax_regime', 'receiver_zip_code', 'receiver_cfdi_use', 'receiver_email',
        'payment_form', 'subtotal_cents', 'tax_cents', 'total_cents',
        'provider', 'provider_id', 'uuid', 'xml_path', 'pdf_path', 'error_message',
        'related_invoice_id', 'cancel_motive', 'stamped_at', 'cancelled_at',
    ];

    protected $attributes = [
        'type' => 'I',
        'status' => 'solicitada',
    ];

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'subtotal_cents' => 'integer',
            'tax_cents' => 'integer',
            'total_cents' => 'integer',
            'stamped_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // RFC y nombre en mayúsculas, como en la constancia
        static::saving(function (Invoice $invoice) {
            $invoice->receiver_rfc = mb_strtoupper(trim($invoice->receiver_rfc));
            $invoice->receiver_name = mb_strtoupper(trim($invoice->receiver_name));
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function relatedInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'related_invoice_id');
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(Invoice::class, 'related_invoice_id');
    }

    public function isCreditNote(): bool
    {
        return $this->type === 'E';
    }

    public function typeLabel(): string
    {
        return $this->isCreditNote() ? 'Nota de crédito' : 'Factura';
    }

    // "factura-HK-001-<uuid>.pdf"
    public function fileName(string $format): string
    {
        $prefix = $this->isCreditNote() ? 'nota-credito' : 'factura';

        return "{$prefix}-{$this->order->folio}-{$this->uuid}.{$format}";
    }
}
