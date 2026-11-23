<x-mail::message>
# Aquí están las ventas de este mes

Cierre de **{{ $report->periodLabel() }}** (corte por fecha de pago, hora de México).

| | |
|:--|--:|
| Pedidos pagados | {{ $report->orders_count }} |
| Subtotal | {{ $money($report->subtotal_mxn_cents) }} |
| Envío | {{ $money($report->shipping_mxn_cents) }} |
| IVA | {{ $money($report->tax_mxn_cents) }} |
| **Total vendido** | **{{ $money($report->total_mxn_cents) }}** |
| Reembolsos del mes | −{{ $money($report->refunds_mxn_cents) }} |
| Comisiones de Stripe | −{{ $money($report->fees_mxn_cents) }} |
| **Neto** | **{{ $money($report->netMxnCents()) }}** |

El archivo adjunto ({{ $report->fileName() }}) trae el resumen, el detalle por pedido y los reembolsos del mes. Montos en USD convertidos con el tipo de cambio de cada pedido.

Los pagos OXXO o SPEI confirmados después del cierre aparecen en el reporte del mes siguiente.

Hikari Candles & Co.
</x-mail::message>
