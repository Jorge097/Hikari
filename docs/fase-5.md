# Fase 5 — Facturación CFDI 4.0 con Facturama

Fecha: 2026-11-22.

## PAC elegido: Facturama API Web

Comparativa con precios oficiales del 2026-11-22 (IVA incluido):

| | Facturama API Web | Facturapi |
|---|---|---|
| Costo fijo | $1,650 MXN/año (incluye 100 folios) | $299 MXN/mes |
| Por timbre | $0.50 (1–10,000) | $0.60 |
| Pruebas | Sandbox gratis (`apisandbox.facturama.mx`) | 14 días |

Con el volumen de Hikari, Facturama sale en ≈ $137/mes contra ≈ $300+/mes de Facturapi.

El código depende de la interfaz `InvoiceProvider`; cambiar de PAC es escribir otro adaptador.

## Flujo

1. **El cliente solicita la factura** en `/factura` (enlace en el footer y en la confirmación de pago) con su folio, el correo de la compra y sus datos fiscales.
   - Si el folio no existe o el correo no coincide, responde con el mismo mensaje, para no revelar pedidos.
   - Valida el formato del RFC (rechaza los genéricos XAXX/XEXX), el régimen y el uso de CFDI contra el catálogo del SAT y el CP de 5 dígitos.
   - Límite de 5 solicitudes por minuto por IP.
   - Solo se facturan pedidos pagados, enviados o entregados, en MXN y sin otra factura vigente.
2. **También se puede facturar desde el panel**: en Pedido → **Facturar**, con los datos fiscales que el cliente usó la última vez.
3. Panel → Ventas → **Facturas**:
   - un indicador con las solicitudes pendientes;
   - **Editar** para corregir datos antes de timbrar;
   - **Timbrar**, que guarda el UUID y el XML/PDF en `storage/app/private/invoices/` y le envía el correo al cliente con los dos archivos.
4. Si el PAC rechaza la factura, queda en **Error** con el mensaje del PAC. Se corrige y se vuelve a timbrar.
5. **Cancelar CFDI** (motivos 01, 02 y 03; el 01 pide el UUID que la sustituye). Si el receptor tiene que aceptarla, queda como *Cancelación pendiente*.
6. **Nota de crédito**: es un CFDI de egreso relacionado con la factura (tipo de relación 01, uso G02), con tope en lo facturado. Por defecto propone el monto reembolsado.
7. El reporte mensual y la exportación ya llenan **RFC** y **UUID CFDI**.

El contador ve las facturas y descarga XML y PDF, pero no timbra, no cancela y no emite notas de crédito.

## Cálculo

Los precios incluyen IVA. Por partida, la base es `total / 1.16` redondeada al centavo, y el IVA es la diferencia. Así el CFDI cuadra exactamente con el total cobrado. El envío va como partida aparte.

## Configuración (`.env`)

```
FACTURAMA_USER=
FACTURAMA_PASSWORD=
FACTURAMA_SANDBOX=true

HIKARI_CFDI_EXPEDITION_PLACE=27000   # CP del domicilio fiscal de Charlotte
HIKARI_CFDI_SERIE=HK
HIKARI_CFDI_PRODUCT_CODE=01010101    # clave SAT de las velas: confirmar con el contador
HIKARI_CFDI_SHIPPING_CODE=78102203   # clave SAT del envío: confirmar con el contador
```

- **El CSD y los datos fiscales de Charlotte los carga ella en el portal de Facturama.** El código nunca maneja certificados ni llaves (`*.cer`, `*.key`, `*.pfx` y `*.pem` están en `.gitignore`).
- Facturama no registra en logs los datos fiscales, y los errores solo se guardan en la factura.

## Antes de producción (requiere a Jorge, Charlotte y el contador)

1. Jorge crea la cuenta del **sandbox** de Facturama (gratis) y pone sus credenciales en `.env`. Ahí se configura el perfil fiscal de prueba con el RFC `EKU9003173C9` y los sellos de prueba.
2. **Verificar en el sandbox** lo que la documentación pública no deja claro:
   - el formato de la respuesta de descarga (se lee `Content` en Base64);
   - la estructura `Relations` de la nota de crédito;
   - el envío de `Currency`/`Serie`.
3. Confirmar con el contador:
   - las claves de producto/servicio del SAT;
   - la forma de pago con tarjeta (hoy es 04 crédito; débito sería 28; se puede cambiar antes de timbrar);
   - el régimen fiscal de Charlotte (pendiente #4).
4. Charlotte contrata el módulo API, sube su CSD y su logo, y se cambia a `FACTURAMA_SANDBOX=false`.

## No implementado (a propósito)

- **Factura global a público en general**: pendiente de lo que diga el contador (#5).
- Facturación en USD.
- Complemento de pago (PPD): todos los pagos son de contado (PUE).
