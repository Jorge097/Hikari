<?php

namespace App\Services\Reports;

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
}
