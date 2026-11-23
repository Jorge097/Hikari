<?php

namespace App\Services\Invoicing;

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Mail\InvoiceIssued;
use App\Models\Invoice;
use App\Models\Order;
use App\Support\SatCatalogs;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    public function __construct(private readonly InvoiceProvider $provider) {}

    /**
     * Registra la solicitud de factura de un pedido pagado.
     *
     * @param  array{rfc: string, tax_name: string, tax_regime: string, tax_zip_code: string, cfdi_use: string, email?: ?string, payment_form?: ?string}  $fiscal
     */
    public function request(Order $order, array $fiscal): Invoice
    {
        return DB::transaction(function () use ($order, $fiscal) {
            $order = Order::lockForUpdate()->with('items', 'invoices', 'customer', 'payments')->findOrFail($order->id);

            $this->ensureInvoiceable($order);

            $lines = CfdiBuilder::lines($order);

            $invoice = $order->invoices()->create([
                'type' => 'I',
                'receiver_rfc' => $fiscal['rfc'],
                'receiver_name' => $fiscal['tax_name'],
                'receiver_tax_regime' => $fiscal['tax_regime'],
                'receiver_zip_code' => $fiscal['tax_zip_code'],
                'receiver_cfdi_use' => $fiscal['cfdi_use'],
                'receiver_email' => $fiscal['email'] ?? $order->customer->email,
                'payment_form' => $fiscal['payment_form'] ?? SatCatalogs::paymentForm($this->paymentMethod($order)),
                ...CfdiBuilder::totals($lines),
            ]);

            // Datos fiscales por defecto para la próxima vez
            $order->customer->update([
                'rfc' => $invoice->receiver_rfc,
                'tax_name' => $invoice->receiver_name,
                'tax_regime' => $invoice->receiver_tax_regime,
                'tax_zip_code' => $invoice->receiver_zip_code,
                'cfdi_use' => $invoice->receiver_cfdi_use,
            ]);

            return $invoice;
        });
    }

    // Timbra ante el PAC y guarda XML/PDF
    public function stamp(Invoice $invoice): Invoice
    {
        if (! in_array($invoice->status, [InvoiceStatus::Solicitada, InvoiceStatus::Error], true)) {
            throw new InvoiceProviderException('Solo se timbran facturas solicitadas o con error.');
        }

        if (! config('hikari.invoicing.expedition_place')) {
            throw new InvoiceProviderException('Falta HIKARI_CFDI_EXPEDITION_PLACE (CP del emisor) en .env.');
        }

        $invoice->loadMissing('order.items', 'relatedInvoice');
        $cfdi = CfdiBuilder::build($invoice, $this->linesFor($invoice));

        try {
            $result = $this->provider->stamp($invoice, $cfdi);
        } catch (InvoiceProviderException|ConnectionException $e) {
            $invoice->update(['status' => InvoiceStatus::Error, 'error_message' => $e->getMessage()]);

            throw new InvoiceProviderException($e->getMessage(), previous: $e);
        }

        $invoice->update([
            'status' => InvoiceStatus::Timbrada,
            'provider' => $this->provider->name(),
            'provider_id' => $result['id'],
            'uuid' => $result['uuid'],
            'stamped_at' => now(),
            'error_message' => null,
        ]);

        $this->storeFiles($invoice);

        if ($invoice->receiver_email) {
            Mail::to($invoice->receiver_email)->queue(new InvoiceIssued($invoice));
        }

        return $invoice;
    }

    // Descarga XML y PDF del PAC; si falla, se pueden bajar después
    public function storeFiles(Invoice $invoice): void
    {
        $paths = [];

        foreach (['xml', 'pdf'] as $format) {
            try {
                $path = "invoices/{$invoice->fileName($format)}";
                Storage::disk('local')->put($path, $this->provider->download($invoice, $format));
                $paths["{$format}_path"] = $path;
            } catch (InvoiceProviderException|ConnectionException) {
                // Reintentar con "Descargar archivos" en el panel
            }
        }

        $invoice->update($paths);
    }

    public function cancel(Invoice $invoice, string $motive, ?string $replacementUuid = null): Invoice
    {
        if (! in_array($invoice->status, [InvoiceStatus::Timbrada, InvoiceStatus::CancelacionPendiente], true)) {
            throw new InvoiceProviderException('Solo se cancelan facturas timbradas.');
        }

        $status = $this->provider->cancel($invoice, $motive, $replacementUuid);

        $invoice->update(match ($status) {
            'canceled' => ['status' => InvoiceStatus::Cancelada, 'cancel_motive' => $motive, 'cancelled_at' => now()],
            'active' => ['status' => InvoiceStatus::Timbrada],
            default => ['status' => InvoiceStatus::CancelacionPendiente, 'cancel_motive' => $motive],
        });

        return $invoice;
    }

    // Nota de crédito (egreso) por un reembolso de la factura
    public function creditNote(Invoice $original, int $amountCents): Invoice
    {
        if ($original->isCreditNote() || $original->status !== InvoiceStatus::Timbrada) {
            throw new InvoiceProviderException('La nota de crédito requiere una factura timbrada.');
        }

        $available = $original->total_cents - $original->creditNotes()
            ->whereIn('status', [InvoiceStatus::Timbrada, InvoiceStatus::Solicitada, InvoiceStatus::Error])
            ->sum('total_cents');

        if ($amountCents <= 0 || $amountCents > $available) {
            throw new InvoiceProviderException('El monto excede lo facturado pendiente de acreditar.');
        }

        $line = $this->creditLine($original, $amountCents);

        $note = $original->order->invoices()->create([
            'type' => 'E',
            'related_invoice_id' => $original->id,
            'receiver_rfc' => $original->receiver_rfc,
            'receiver_name' => $original->receiver_name,
            'receiver_tax_regime' => $original->receiver_tax_regime,
            'receiver_zip_code' => $original->receiver_zip_code,
            'receiver_cfdi_use' => 'G02', // Devoluciones, descuentos o bonificaciones
            'receiver_email' => $original->receiver_email,
            'payment_form' => $original->payment_form,
            ...CfdiBuilder::totals([$line]),
        ]);

        return $this->stamp($note);
    }

    private function linesFor(Invoice $invoice): array
    {
        return $invoice->isCreditNote()
            ? [$this->creditLine($invoice->relatedInvoice, $invoice->total_cents)]
            : CfdiBuilder::lines($invoice->order);
    }

    private function creditLine(Invoice $original, int $amountCents): array
    {
        $order = $original->order;
        $rate = (float) $order->tax_rate;
        $subtotal = (int) round($amountCents / (1 + $rate));

        return [
            'description' => "Devolución del pedido {$order->folio}",
            'quantity' => 1,
            'subtotal' => $subtotal,
            'tax' => $amountCents - $subtotal,
            'total' => $amountCents,
            'product_code' => config('hikari.invoicing.product_code'),
            'unit_code' => config('hikari.invoicing.unit_code'),
            'unit' => 'Pieza',
        ];
    }

    private function ensureInvoiceable(Order $order): void
    {
        $fail = fn (string $message) => throw ValidationException::withMessages(['folio' => $message]);

        if (! in_array($order->status, [OrderStatus::Pagado, OrderStatus::Enviado, OrderStatus::Entregado], true)) {
            $fail('Solo se pueden facturar pedidos pagados.');
        }

        if ($order->currency !== 'MXN') {
            $fail('La facturación en dólares aún no está disponible; contáctanos.');
        }

        if ($order->activeInvoice()) {
            $fail('Este pedido ya tiene una factura solicitada o emitida.');
        }
    }

    private function paymentMethod(Order $order)
    {
        return $order->payments
            ->where('type', PaymentType::Pago)
            ->where('status', PaymentStatus::Completado)
            ->sortByDesc('paid_at')
            ->first()?->method;
    }
}
