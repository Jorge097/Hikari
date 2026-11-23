<x-mail::message>
# Aquí están las ventas de este mes

Cierre de **{{ $report->periodLabel() }}** (corte por fecha de pago, hora de México).

| | |
|:--|--:|
| Pedidos pagados | {{ $report->orders_count }} |
| Subtotal | {{ $money($report->subtotal_mxn_cents) }} |
| Envío | {{ $money($report->shipping_mxn_cents) }} |
| IVA | {{ $money($report->tax_mxn_cents) }} |
| **Total** | **{{ $money($report->total_mxn_cents) }}** |

El detalle por pedido va en el archivo adjunto ({{ $report->fileName() }}). Montos en USD convertidos con el tipo de cambio de cada pedido.

Los pagos OXXO o SPEI confirmados después del cierre aparecen en el reporte del mes siguiente.

Hikari Candles & Co.
</x-mail::message>
