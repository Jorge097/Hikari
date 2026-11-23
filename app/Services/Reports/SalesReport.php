<?php

namespace App\Services\Reports;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Order;
use App\Models\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SalesReport
{
    // Encabezados para el contador
    public const HEADERS = [
        'folio' => 'Folio',
        'paid_at' => 'Fecha de pago',
        'customer_name' => 'Cliente',
        'rfc' => 'RFC',
        'subtotal' => 'Subtotal',
        'tax' => 'IVA',
        'shipping' => 'Envío',
        'total' => 'Total',
        'currency' => 'Moneda',
        'exchange_rate' => 'Tipo de cambio',
        'total_mxn' => 'Total MXN',
        'payment_method' => 'Método de pago',
        'status' => 'Estado',
        'origin' => 'Origen',
        'stripe_payment_id' => 'ID de pago Stripe',
        'cfdi_uuid' => 'UUID CFDI',
        'fee' => 'Comisión Stripe',
    ];

    public const REFUND_HEADERS = ['Folio', 'Fecha del reembolso', 'Cliente', 'Monto MXN', 'Método', 'Motivo', 'UUID CFDI'];

    // "Noviembre"
    public static function monthName(int $month): string
    {
        return ucfirst(CarbonImmutable::create(2000, $month, 1)->locale('es')->monthName);
    }

    // Inicio y fin del mes en hora de México
    public static function monthRange(int $year, int $month): array
    {
        $start = CarbonImmutable::create($year, $month, 1, 0, 0, 0, config('app.timezone'));

        return [$start, $start->endOfMonth()];
    }

    // Pedidos con pago confirmado (corte por paid_at)
    public static function paidBetween(CarbonImmutable $from, CarbonImmutable $to): Builder
    {
        return Order::query()
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$from, $to]);
    }

    /**
     * Filas listas para el reporte o exportación.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function rows(Builder $query): Collection
    {
        return $query
            ->with(['customer', 'payments', 'invoices'])
            ->orderBy('paid_at')
            ->orderBy('id')
            ->get()
            ->map(fn (Order $order) => self::row($order));
    }

    public static function row(Order $order): array
    {
        $payment = $order->payments
            ->where('type', PaymentType::Pago)
            ->where('status', PaymentStatus::Completado)
            ->sortByDesc('paid_at')
            ->first();

        $rate = $order->currency === 'MXN' ? 1 : (float) $order->exchange_rate;

        // Comisión de Stripe del pago (ya en MXN)
        $fees = $order->payments
            ->where('type', PaymentType::Pago)
            ->where('status', PaymentStatus::Completado)
            ->whereNotNull('fee_cents');

        // Factura de ingreso timbrada (si la hay)
        $invoice = $order->invoices
            ->where('type', 'I')
            ->where('status', InvoiceStatus::Timbrada)
            ->sortByDesc('id')
            ->first();

        return [
            'order_id' => $order->id,
            'folio' => $order->folio,
            'paid_at' => $order->paid_at,
            'customer_name' => $order->customer?->name,
            'rfc' => $invoice?->receiver_rfc,
            'subtotal_cents' => $order->subtotal_cents,
            'tax_cents' => $order->tax_cents,
            'shipping_cents' => $order->shipping_cents,
            'total_cents' => $order->total_cents,
            'currency' => $order->currency,
            'exchange_rate' => $order->currency === 'MXN' ? null : (float) $order->exchange_rate,
            'total_mxn_cents' => (int) round($order->total_cents * $rate),
            'payment_method' => $payment?->method?->getLabel(),
            'status' => $order->status->getLabel(),
            'origin' => $order->origin->getLabel(),
            'stripe_payment_id' => $payment?->stripe_payment_intent_id,
            'cfdi_uuid' => $invoice?->uuid,
            'fee_cents' => $fees->isEmpty() ? null : (int) $fees->sum('fee_cents'),
        ];
    }

    /**
     * Reembolsos hechos en el periodo (por fecha del reembolso), en MXN.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function refunds(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return Payment::query()
            ->with(['order.customer', 'order.invoices'])
            ->where('type', PaymentType::Reembolso)
            ->where('status', PaymentStatus::Completado)
            ->whereBetween('paid_at', [$from, $to])
            ->orderBy('paid_at')
            ->get()
            ->map(function (Payment $refund) {
                $order = $refund->order;
                $rate = $refund->currency === 'MXN' ? 1 : (float) $order->exchange_rate;
                $invoice = $order->invoices->where('type', 'I')->where('status', InvoiceStatus::Timbrada)->sortByDesc('id')->first();

                return [
                    'folio' => $order->folio,
                    'paid_at' => $refund->paid_at,
                    'customer_name' => $order->customer?->name,
                    'amount_mxn_cents' => (int) round($refund->amount_cents * $rate),
                    'method' => $refund->method?->getLabel(),
                    'notes' => $refund->notes,
                    'cfdi_uuid' => $invoice?->uuid,
                ];
            });
    }

    /**
     * Fila en el orden de HEADERS, montos en pesos.
     *
     * @return array<int, mixed>
     */
    public static function toCells(array $row): array
    {
        $money = fn (?int $cents) => $cents === null ? null : round($cents / 100, 2);

        return [
            $row['folio'],
            $row['paid_at']?->format('Y-m-d H:i'),
            $row['customer_name'],
            $row['rfc'],
            $money($row['subtotal_cents']),
            $money($row['tax_cents']),
            $money($row['shipping_cents']),
            $money($row['total_cents']),
            $row['currency'],
            $row['exchange_rate'],
            $money($row['total_mxn_cents']),
            $row['payment_method'],
            $row['status'],
            $row['origin'],
            $row['stripe_payment_id'],
            $row['cfdi_uuid'],
            $money($row['fee_cents'] ?? null),
        ];
    }
}
