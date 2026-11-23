<?php

namespace App\Mail;

use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
        // Solo se encola si la transacción se confirma
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Confirmación de tu pedido {$this->order->folio} — Hikari Candles & Co.",
        );
    }

    public function content(): Content
    {
        $this->order->loadMissing('items', 'customer', 'payments');

        return new Content(
            markdown: 'emails.orders.confirmation',
            with: [
                'order' => $this->order,
                'payment' => $this->order->payments->firstWhere('status', PaymentStatus::Completado),
                'money' => fn (int $cents) => '$'.number_format($cents / 100, 2).' '.$this->order->currency,
            ],
        );
    }
}
