<x-mail::message>
# Tu {{ mb_strtolower($invoice->typeLabel()) }} está lista

Adjuntamos el XML y el PDF del comprobante de tu pedido **{{ $invoice->order->folio }}**.

| | |
|:--|--:|
| Receptor | {{ $invoice->receiver_name }} |
| RFC | {{ $invoice->receiver_rfc }} |
| Folio fiscal (UUID) | {{ $invoice->uuid }} |
| Total | ${{ number_format($invoice->total_cents / 100, 2) }} MXN |

Si algún dato no coincide con tu constancia de situación fiscal, respóndenos este correo.

Hikari Candles & Co.
</x-mail::message>
