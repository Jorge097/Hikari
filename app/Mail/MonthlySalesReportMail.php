<?php

namespace App\Mail;

use App\Models\MonthlySalesReport;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class MonthlySalesReportMail extends Mailable
{
    public function __construct(public MonthlySalesReport $report) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Aquí están las ventas de este mes — {$this->report->periodLabel()}",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.reports.monthly', with: [
            'report' => $this->report,
            'money' => fn (int $cents) => '$'.number_format($cents / 100, 2).' MXN',
        ]);
    }

    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk('local', $this->report->file_path)
                ->as($this->report->fileName())
                ->withMime('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
        ];
    }
}
