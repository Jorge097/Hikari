<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class InvoiceIssued extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Invoice $invoice)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "{$this->invoice->typeLabel()} de tu pedido {$this->invoice->order->folio} — Hikari Candles & Co.",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.invoices.issued');
    }

    // XML y PDF si ya se descargaron
    public function attachments(): array
    {
        return collect(['xml' => 'application/xml', 'pdf' => 'application/pdf'])
            ->filter(fn ($mime, $format) => $this->invoice->{"{$format}_path"} && Storage::disk('local')->exists($this->invoice->{"{$format}_path"}))
            ->map(fn ($mime, $format) => Attachment::fromStorageDisk('local', $this->invoice->{"{$format}_path"})
                ->as($this->invoice->fileName($format))
                ->withMime($mime))
            ->values()
            ->all();
    }
}
