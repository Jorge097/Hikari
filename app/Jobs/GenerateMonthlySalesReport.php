<?php

namespace App\Jobs;

use App\Mail\MonthlySalesReportMail;
use App\Models\MonthlySalesReport;
use App\Services\Reports\SalesReport;
use App\Services\Reports\SpreadsheetWriter;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class GenerateMonthlySalesReport implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public int $year,
        public int $month,
        public bool $sendEmail = true,
    ) {}

    // Un solo cierre por mes a la vez
    public function uniqueId(): string
    {
        return "{$this->year}-{$this->month}";
    }

    public function handle(): void
    {
        [$from, $to] = SalesReport::monthRange($this->year, $this->month);
        $rows = SalesReport::rows(SalesReport::paidBetween($from, $to));

        $mxn = fn (string $key) => (int) $rows->sum(fn ($row) => round($row[$key] * ($row['exchange_rate'] ?? 1)));

        // Idempotente: reemplaza el snapshot del mes
        $report = DB::transaction(function () use ($rows, $mxn) {
            $report = MonthlySalesReport::updateOrCreate(
                ['year' => $this->year, 'month' => $this->month],
                [
                    'orders_count' => $rows->count(),
                    'subtotal_mxn_cents' => $mxn('subtotal_cents'),
                    'tax_mxn_cents' => $mxn('tax_cents'),
                    'shipping_mxn_cents' => $mxn('shipping_cents'),
                    'total_mxn_cents' => $rows->sum('total_mxn_cents'),
                    'generated_at' => now(),
                ],
            );

            $report->items()->delete();
            $report->items()->createMany($rows->all());

            return $report;
        });

        // Archivo XLSX en storage/app/private/reports
        $path = 'reports/'.$report->fileName();
        Storage::disk('local')->makeDirectory('reports');
        SpreadsheetWriter::write($rows, Storage::disk('local')->path($path));
        $report->update(['file_path' => $path]);

        $recipients = config('hikari.report_emails');

        if ($this->sendEmail && $recipients) {
            Mail::to($recipients)->send(new MonthlySalesReportMail($report));
            $report->update(['emailed_at' => now()]);
        }
    }
}
