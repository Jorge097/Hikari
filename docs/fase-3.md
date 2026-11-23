# Fase 3 — Filtros, exportación y cierre mensual

Fecha: 2026-11-22.

## Panel → Ventas → Pedidos

- Filtro **Ventas del mes** (mes + año) y filtro **Fecha de pago** (de X a Y), además de estado y origen. Ambos cortan por `paid_at`: los pedidos sin pago no aparecen.
- Botón **Exportar → Excel (XLSX) / CSV**: descarga exactamente lo filtrado.

Columnas (iguales en exportación y cierre mensual):

Folio · Fecha de pago · Cliente · RFC · Subtotal · IVA · Envío · Total · Moneda · Tipo de cambio · Total MXN · Método de pago · Estado · Origen · ID de pago Stripe · UUID CFDI

RFC y UUID CFDI quedan vacíos hasta la Fase 5. Al final va una fila con número de pedidos y total en MXN.

## Cierre mensual automatizado

1. **Último día de cada mes a las 23:59** (America/Mexico_City) el scheduler encola `GenerateMonthlySalesReport` con el mes en curso.
2. El job consolida los pedidos con `paid_at` dentro del mes (hora de México) y guarda el snapshot en `monthly_sales_reports` + `monthly_sales_report_items`.
3. Genera `storage/app/private/reports/ventas-AAAA-MM.xlsx`.
4. Envía **"Aquí están las ventas de este mes — Mes Año"** con el archivo adjunto a `HIKARI_REPORT_EMAILS`.

- **Idempotente**: volver a correrlo para el mismo mes reemplaza el snapshot y el archivo, no duplica.
- Panel → Ventas → **Reportes mensuales**: lista con totales, detalle por pedido, **Descargar**, **Regenerar** (no reenvía correo) y **Generar mes** (con opción de enviar).
- Consola: `php artisan reports:monthly 2026 11 [--no-email]`.
- Montos en USD se convierten a MXN con el tipo de cambio guardado en cada pedido.
- Pedidos reembolsados después de pagados siguen en el mes en que se pagaron, con su estado.

### Corte por fecha de pago

Los pagos OXXO/SPEI confirmados después del cierre caen en el mes siguiente (lo dice el correo). Si un pago llega entre que corre el job (23:59) y la medianoche, basta **Regenerar** el mes.

## Cambios de configuración

- **Zona horaria de la app**: `America/Mexico_City` (antes UTC). Fechas del panel, cortes y `paid_at` en hora de México. México no tiene horario de verano desde 2022.
- Composer fijado a **PHP 8.2.12** (`config.platform`): evita instalar paquetes que XAMPP no soporta. Confirmar la versión de PHP de Neubox (pendiente #9).
- `openspout/openspout` (ya venía con Filament) declarado como dependencia directa. Requiere la extensión `zip`.

## Variables nuevas de `.env`

```
HIKARI_REPORT_EMAILS=charlotte@...,segundo@...
APP_TIMEZONE=America/Mexico_City
```

## En producción (Neubox) — Fase 6

- Cron: `* * * * * php /ruta/artisan schedule:run >> /dev/null 2>&1`
- Cola (el cierre y los correos van en cola): `php artisan queue:work --stop-when-empty` en otro cron cada minuto, o `QUEUE_CONNECTION=sync` si el plan no lo permite.

## Pendiente

- Segundo destinatario del correo mensual (pendiente #7).
- Formato exacto que pide el contador (pendiente #3); las columnas están en `SalesReport::HEADERS`.
