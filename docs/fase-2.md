# Fase 2 — Checkout con Stripe, webhooks y correo de confirmación

Fecha: 2026-11-22.

## Flujo

1. Carrito (`/pedido`) → **Pagar en línea** → `/checkout` (datos de contacto, entrega y evento).
2. `POST /checkout` (máx. 10 por minuto por IP):
   - Valida con `CheckoutRequest`.
   - **Recalcula precios desde el catálogo** (nunca usa los del navegador). Producto, aroma y tamaño deben estar activos.
   - Crea o reutiliza el cliente por correo, crea el pedido `web` (folio `HK-###`) y un pago `stripe` pendiente.
   - Abre una sesión de **Stripe Checkout alojado** y redirige.
3. Stripe redirige a `/pedido/confirmado?session_id=...` (muestra folio y estado, vacía el carrito) o a `/pedido/cancelado` (regresa al carrito).
4. `POST /stripe/webhook` es la **fuente de verdad**:

| Evento | Resultado |
|---|---|
| `checkout.session.completed` + `paid` (tarjeta, MSI) | Pago completado → pedido `pagado` → correo |
| `checkout.session.completed` + `unpaid` (OXXO, SPEI) | Guarda método e intent; sigue `pendiente` |
| `checkout.session.async_payment_succeeded` | Pago completado → `pagado` → correo |
| `checkout.session.async_payment_failed` | Pago `fallido` (pedido sigue pendiente) |
| `checkout.session.expired` | Pago `fallido` y pedido `cancelado` |

- Firma verificada con `STRIPE_WEBHOOK_SECRET`; ruta excluida de CSRF.
- Idempotencia: cada `event.id` se guarda en `stripe_events` y se procesa una sola vez.
- `paid_at` = momento en que Stripe confirma el pago (OXXO/SPEI pueden caer en el mes siguiente).

## Correo de confirmación

`OrderConfirmation` (en cola, solo después del commit): folio, artículos, subtotal, envío, IVA, total, método de pago y datos de entrega. Se envía **una vez** (`orders.confirmation_sent_at`) cuando el pedido pasa a `pagado`, también en pedidos manuales con correo.

## Métodos de pago (según docs de Stripe vigentes)

No se fijan en el código: Checkout muestra los que estén **activos en el Dashboard** de la cuenta MX.
- **Tarjeta** y **Meses sin intereses**: MSI solo en MXN, tarjetas de crédito mexicanas; Stripe decide la elegibilidad.
- **OXXO** y **SPEI** (transferencia bancaria): asíncronos. SPEI necesita un Customer en Stripe, por eso se usa `customer_creation=always`.
- Cobro web en **MXN**. Los pedidos en USD se registran a mano en el panel (con tipo de cambio).

## Cambios de precio visibles

Los tamaños de **Luminaria Imperial** tienen extra (Mediana +$130, Grande +$260). Antes el carrito lo ignoraba; ahora el detalle y el carrito lo suman, igual que el servidor.

## Variables nuevas de `.env`

```
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
HIKARI_SHIPPING_NATIONAL_CENTS=0
```

Sin `STRIPE_SECRET` el checkout muestra "pagos en línea no disponibles" y no crea pedidos.

## Cómo probar con Stripe (modo test)

1. Charlotte crea la cuenta Stripe MX y activa tarjeta, MSI, OXXO y transferencias en *Settings → Payment methods*.
2. Copiar la llave secreta **de prueba** a `STRIPE_SECRET`.
3. Stripe CLI: `stripe listen --forward-to 127.0.0.1:8000/stripe/webhook` y copiar el `whsec_...` a `STRIPE_WEBHOOK_SECRET`.
4. Correr la cola para el correo: `php artisan queue:work` (en local el correo va al log con `MAIL_MAILER=log`).
5. Comprar con la tarjeta de prueba `4242 4242 4242 4242`; probar OXXO/SPEI con las opciones de prueba de Checkout.

## Pendiente

- Costo real del **envío nacional** (Charlotte).
- Confirmar si los precios incluyen IVA (sigue pendiente de Fase 1).
- Política de reembolsos (los reembolsos desde Filament vía API quedan para cuando exista).
- Aviso de privacidad y casilla de aceptación en checkout: Fase 4.
- Precios del catálogo siguen en decimales; se convierten a centavos en el servidor al cobrar.
