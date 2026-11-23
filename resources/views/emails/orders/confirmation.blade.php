<x-mail::message>
# ¡Gracias por tu compra, {{ $order->customer->name }}!

Recibimos el pago de tu pedido **{{ $order->folio }}**.

<x-mail::table>
| Artículo | Cant. | Importe |
|:---------|:-----:|--------:|
@foreach ($order->items as $item)
| {{ $item->product_name }}@if($item->scent || $item->size)<br><small>{{ collect([$item->scent, $item->size])->filter()->join(' · ') }}</small>@endif | {{ $item->quantity }} | {{ $money($item->total_cents) }} |
@endforeach
</x-mail::table>

| | |
|:--|--:|
| Subtotal | {{ $money($order->subtotal_cents) }} |
| Envío | {{ $money($order->shipping_cents) }} |
| IVA{{ $order->prices_include_tax ? ' (incluido)' : '' }} | {{ $money($order->tax_cents) }} |
| **Total** | **{{ $money($order->total_cents) }}** |

@if ($payment)
**Método de pago:** {{ $payment->method?->getLabel() ?? 'En línea' }} · {{ $payment->paid_at?->format('d/m/Y H:i') }}
@endif

@if ($order->shipping_address)
**Envío a:** {{ $order->shipping_name }}, {{ $order->shipping_address }}, {{ $order->shipping_city }}, {{ $order->shipping_state }}, C.P. {{ $order->shipping_postal_code }}
@else
**Entrega:** local / recoger. Te contactaremos para coordinarla.
@endif

@if ($order->required_date)
**Fecha requerida:** {{ $order->required_date->format('d/m/Y') }}
@endif

Si tienes dudas, responde a este correo o escríbenos por WhatsApp con tu folio.

Gracias,<br>
Hikari Candles & Co.
</x-mail::message>
