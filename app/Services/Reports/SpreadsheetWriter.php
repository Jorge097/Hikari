<?php

namespace App\Services\Reports;

use App\Models\MonthlySalesReport;
use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;

class SpreadsheetWriter
{
    /**
     * Escribe el reporte en XLSX o CSV.
     *
     * @param  Collection<int, array<string, mixed>>  $rows  Filas de SalesReport::rows()
     */
    public static function write(Collection $rows, string $path, string $format = 'xlsx'): string
    {
        $writer = $format === 'csv' ? new CsvWriter : new XlsxWriter;
        $writer->openToFile($path);

        $writer->addRow(Row::fromValues(array_values(SalesReport::HEADERS), (new Style)->setFontBold()));

        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues(SalesReport::toCells($row)));
        }

        // Totales en MXN
        $sum = fn (string $key) => round($rows->sum($key) / 100, 2);
        $writer->addRow(Row::fromValues([]));
        $writer->addRow(Row::fromValues(
            ['Pedidos', $rows->count(), null, null, null, null, null, null, null, 'Total MXN', $sum('total_mxn_cents')],
            (new Style)->setFontBold(),
        ));

        $writer->close();

        return $path;
    }

    /**
     * Cierre del mes para el contador: Resumen, Ventas y Reembolsos.
     *
     * @param  Collection<int, array<string, mixed>>  $rows  Filas de SalesReport::rows()
     * @param  Collection<int, array<string, mixed>>  $refunds  Filas de SalesReport::refunds()
     */
    public static function writeMonthly(MonthlySalesReport $report, Collection $rows, Collection $refunds, string $path): string
    {
        $bold = (new Style)->setFontBold();
        $pesos = fn (?int $cents) => $cents === null ? null : round($cents / 100, 2);

        $writer = new XlsxWriter;
        $writer->openToFile($path);

        // Resumen
        $writer->getCurrentSheet()->setName('Resumen');
        $writer->addRow(Row::fromValues(['Ventas de '.$report->periodLabel()], $bold));
        $writer->addRow(Row::fromValues(['Corte por fecha de pago, hora de México. Montos en MXN.']));
        $writer->addRow(Row::fromValues([]));
        foreach ([
            ['Pedidos pagados', $report->orders_count],
            ['Subtotal', $pesos($report->subtotal_mxn_cents)],
            ['IVA', $pesos($report->tax_mxn_cents)],
            ['Envío', $pesos($report->shipping_mxn_cents)],
            ['Total vendido', $pesos($report->total_mxn_cents)],
            ['Reembolsos del mes', $pesos($report->refunds_mxn_cents)],
            ['Comisiones de Stripe', $pesos($report->fees_mxn_cents)],
        ] as $line) {
            $writer->addRow(Row::fromValues($line));
        }
        $writer->addRow(Row::fromValues(['Neto', $pesos($report->netMxnCents())], $bold));

        // Ventas, una fila por pedido
        $writer->addNewSheetAndMakeItCurrent()->setName('Ventas');
        $writer->addRow(Row::fromValues(array_values(SalesReport::HEADERS), $bold));
        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues(SalesReport::toCells($row)));
        }

        // Reembolsos hechos en el mes (pueden ser de ventas de meses anteriores)
        $writer->addNewSheetAndMakeItCurrent()->setName('Reembolsos');
        $writer->addRow(Row::fromValues(SalesReport::REFUND_HEADERS, $bold));
        foreach ($refunds as $refund) {
            $writer->addRow(Row::fromValues([
                $refund['folio'],
                $refund['paid_at']?->format('Y-m-d H:i'),
                $refund['customer_name'],
                $pesos($refund['amount_mxn_cents']),
                $refund['method'],
                $refund['notes'],
                $refund['cfdi_uuid'],
            ]));
        }
        if ($refunds->isEmpty()) {
            $writer->addRow(Row::fromValues(['Sin reembolsos en el mes']));
        }

        $writer->close();

        return $path;
    }
}
