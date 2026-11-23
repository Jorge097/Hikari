# Fase 1 — Pedidos, clientes, pagos y folio

Fecha: 2026-11-22.

## Qué se agregó

| Pieza | Detalle |
|---|---|
| `customers` | Nombre, correo, teléfono, notas. Datos fiscales llegan en Fase 5 |
| `orders` | Reemplaza la tabla original (vacía). Folio, origen, estado, moneda, tipo de cambio, desglose en centavos, envío, guía, fecha requerida y notas |
| `order_items` | Copia del producto al vender: nombre, aroma, tamaño, cantidad, precio unitario e importe en centavos |
| `payments` | Pagos y reembolsos: método, estado, monto, moneda, referencia y fecha. No se borran con el pedido |
| `sequences` | Contador del folio |
| Panel | Grupo **Ventas** con **Pedidos** (registro manual con partidas, envío y pago) y **Clientes** |

## Reglas

- **Folio:** `HK-001`, `HK-002`… `HK-1000`. Se asigna al crear el pedido con un contador bloqueado (`lockForUpdate`) dentro de la misma transacción: si el pedido falla, el número no se consume.
- **Montos:** en centavos (enteros). En el panel se capturan en pesos.
- **IVA:** tasa 16 % (`HIKARI_TAX_RATE`). Se asume que **los precios ya incluyen IVA** (`HIKARI_PRICES_INCLUDE_TAX=true`): el total no cambia y el IVA se muestra como "contenido". Cada pedido guarda la tasa y el modo con los que se calculó.
- **Estado por pagos:** cuando los pagos completados cubren el total, el pedido pasa a `pagado` y guarda `paid_at` (fecha del pago). Si se borra el pago vuelve a `pendiente`; un reembolso total lo marca `reembolsado`.
- **Fechas de estado:** `enviado`, `entregado` y `cancelado` guardan su fecha al cambiar.
- **Moneda:** MXN o USD; en USD el tipo de cambio es obligatorio.
- Los pedidos **no se pueden borrar** desde el panel: se cancelan.

## Variables nuevas de `.env` (opcionales)

```
HIKARI_TAX_RATE=0.16
HIKARI_PRICES_INCLUDE_TAX=true
```

## Cómo probar

1. `php artisan migrate` (respaldo previo en `storage/app/backups/`).
2. Entrar a `/panel_hikari` → Ventas → Pedidos → **Registrar pedido**.
3. Elegir o crear cliente, agregar artículos (el precio se llena desde el catálogo + extra del tamaño), envío y, si ya pagó, activar "Ya pagó".
4. `php artisan test` (44 + 1 tests).

## Pendiente

- **Confirmar con Charlotte/contador si los precios del catálogo incluyen IVA.** Si no, cambiar `HIKARI_PRICES_INCLUDE_TAX=false`.
- Los precios del catálogo (`products.price`, `sizes.extra_price`) siguen en decimales; pasan a centavos en Fase 2 junto con el checkout.
- La prueba de concurrencia real del folio requiere MySQL (en tests se usa SQLite).
