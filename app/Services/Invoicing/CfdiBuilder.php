<?php

namespace App\Services\Invoicing;

use App\Models\Invoice;
use App\Models\Order;

// Arma el CFDI 4.0 en el formato de Facturama API Web
class CfdiBuilder
{
    /**
     * Partidas con desglose de IVA en centavos.
     *
     * @return array<int, array{description: string, quantity: int, total: int, subtotal: int, tax: int, product_code: string, unit_code: string, unit: string}>
     */
    public static function lines(Order $order): array
    {
        $config = config('hikari.invoicing');

        $lines = $order->items->map(fn ($item) => self::line(
            $order,
            trim("{$item->product_name} ".implode(' ', array_filter([$item->scent, $item->size]))),
            $item->quantity,
            $item->total_cents,
            $config['product_code'],
            $config['unit_code'],
            'Pieza',
        ))->all();

        if ($order->shipping_cents > 0) {
            $lines[] = self::line($order, 'Envío', 1, $order->shipping_cents, $config['shipping_product_code'], $config['shipping_unit_code'], 'Servicio');
        }

        return $lines;
    }

    // IVA incluido: base = total / 1.16; el IVA es la diferencia para cuadrar al centavo
    public static function line(Order $order, string $description, int $quantity, int $amountCents, string $productCode, string $unitCode, string $unit): array
    {
        $rate = (float) $order->tax_rate;

        if ($order->prices_include_tax) {
            $subtotal = (int) round($amountCents / (1 + $rate));
            $total = $amountCents;
        } else {
            $subtotal = $amountCents;
            $total = $amountCents + (int) round($amountCents * $rate);
        }

        return [
            'description' => mb_substr($description, 0, 1000),
            'quantity' => $quantity,
            'subtotal' => $subtotal,
            'tax' => $total - $subtotal,
            'total' => $total,
            'product_code' => $productCode,
            'unit_code' => $unitCode,
            'unit' => $unit,
        ];
    }

    public static function build(Invoice $invoice, array $lines): array
    {
        $order = $invoice->order;
        $rate = (float) $order->tax_rate;
        $money = fn (int $cents) => round($cents / 100, 2);

        $cfdi = [
            'Serie' => config('hikari.invoicing.serie'),
            'Folio' => (string) $invoice->id,
            'CfdiType' => $invoice->type,
            'NameId' => $invoice->isCreditNote() ? '2' : '1',
            'ExpeditionPlace' => (string) config('hikari.invoicing.expedition_place'),
            'PaymentForm' => $invoice->payment_form,
            'PaymentMethod' => 'PUE',
            'Currency' => 'MXN',
            'Receiver' => [
                'Rfc' => $invoice->receiver_rfc,
                'Name' => $invoice->receiver_name,
                'CfdiUse' => $invoice->receiver_cfdi_use,
                'FiscalRegime' => $invoice->receiver_tax_regime,
                'TaxZipCode' => $invoice->receiver_zip_code,
            ],
            'Items' => array_map(fn (array $line) => [
                'ProductCode' => $line['product_code'],
                'IdentificationNumber' => $order->folio,
                'Description' => $line['description'],
                'Unit' => $line['unit'],
                'UnitCode' => $line['unit_code'],
                'UnitPrice' => round($line['subtotal'] / 100 / $line['quantity'], 6),
                'Quantity' => $line['quantity'],
                'Subtotal' => $money($line['subtotal']),
                'TaxObject' => '02',
                'Taxes' => [[
                    'Name' => 'IVA',
                    'Rate' => $rate,
                    'Base' => $money($line['subtotal']),
                    'Total' => $money($line['tax']),
                    'IsRetention' => false,
                ]],
                'Total' => $money($line['total']),
            ], $lines),
        ];

        // Nota de crédito relacionada con la factura original (01 = nota de crédito)
        if ($invoice->isCreditNote()) {
            $cfdi['Relations'] = [
                'Type' => '01',
                'Cfdis' => [['Uuid' => $invoice->relatedInvoice->uuid]],
            ];
        }

        return $cfdi;
    }

    // Suma de las partidas en centavos
    public static function totals(array $lines): array
    {
        return [
            'subtotal_cents' => array_sum(array_column($lines, 'subtotal')),
            'tax_cents' => array_sum(array_column($lines, 'tax')),
            'total_cents' => array_sum(array_column($lines, 'total')),
        ];
    }
}
