# Fase 6 — Plan de deploy a Neubox

Fecha: 2026-11-22. **Estado: plan listo, nada ejecutado en Neubox.**

Cada paso marcado con 🔒 necesita el "sí" de Jorge en el momento (regla 1 del plan). Nada va directo a producción: primero se ensaya en staging.

---

## 0. Contexto

- **Producción ya está viva** (`hikaricandlesandco.com`, commit `08a9a1a` de marzo) con productos, imágenes y usuarios reales. Las imágenes **solo existen en el servidor** (`~/Hikari/storage/app/public`). El deploy **no debe tocar `storage/` ni `.env`**.
- Estructura en Neubox (la que espera `public/index.php`):

```
~/Hikari/        app Laravel (vendor, app, config, storage, .env)
~/public_html/   contenido de public/ (index.php, .htaccess, build/, storage → ../Hikari/storage/app/public)
```

- Llegan 7 migraciones nuevas (fases 1–5). `rebuild_orders_tables` **se detiene sola** si las tablas viejas `orders`/`order_items` de producción tienen datos; por eso primero se ensaya con una copia de la base de producción.

## 1. Datos que faltan (bloquean el deploy)

| # | Qué | Quién |
|---|---|---|
| 9 | Tipo de plan Neubox, **Terminal/SSH**, versión de PHP (≥ 8.2), si el cron permite **cada minuto** | Jorge (preguntar a soporte de Neubox) |
| — | Cuenta de **Stripe** de Charlotte verificada (llaves live) | Charlotte |
| — | Cuenta de correo `pedidos@hikaricandlesandco.com` (o la que elija) | Jorge, en cPanel |
| — | Domicilio y correo para el aviso de privacidad, revisado por un abogado | Charlotte |
| 7 | Destinatarios del cierre mensual | Jorge |
| 4, 8 | Facturación: régimen, claves SAT, CSD en Facturama (puede salir después; el sitio funciona sin facturar) | Charlotte + contador |

> Si el plan **no tiene Terminal**, los comandos de la sección 5 no se pueden correr. Opciones: pedirle a Neubox que active SSH o cambiar a un plan que lo tenga. No se recomienda ejecutar artisan desde una URL pública.

## 2. Armar el paquete (local)

```powershell
powershell -ExecutionPolicy Bypass -File scripts\build-release.ps1
```

Genera `dist\hikari-<commit>.zip` desde el último commit, con `vendor` sin dependencias de desarrollo y `npm run build` ya compilado. No incluye `storage/`, `.env`, tests ni `node_modules`. En el servidor no hace falta Node.

## 3. Staging (ensayo completo) 🔒

1. En cPanel: subdominio `staging.hikaricandlesandco.com` con document root `~/staging/public_html`, base de datos `…_hikari_staging` y SSL (AutoSSL).
2. 🔒 **Respaldo de producción**: en phpMyAdmin, *Exportar* la base de producción → `hikari-prod-AAAAMMDD.sql`. Guardarla fuera del servidor también.
3. Importar ese respaldo en `…_hikari_staging`. Copiar `~/Hikari/storage/app/public` a `~/staging/Hikari/storage/app/public` para tener las imágenes.
4. Subir el zip a `~/staging/` y extraerlo (Administrador de archivos → *Extract*).
5. Crear `~/staging/Hikari/.env` con la plantilla de la sección 6, pero con `APP_ENV=staging`, la URL de staging, la DB de staging, **llaves de Stripe test** y **Facturama sandbox**.
6. Correr los comandos de la sección 5 dentro de `~/staging/Hikari`.
7. Webhook de Stripe **test** → `https://staging.hikaricandlesandco.com/stripe/webhook`.
8. Pruebas manuales:
   - catálogo con imágenes;
   - checkout con tarjeta de prueba `4242 4242 4242 4242`, OXXO y SPEI de prueba;
   - que llegue el correo de confirmación;
   - en el panel: pedido marcado como pagado, registrar un pedido de WhatsApp, exportar XLSX;
   - `php artisan reports:monthly <año> <mes>` y que llegue el correo;
   - `/aviso-de-privacidad`, `/factura` y, si ya hay sandbox de Facturama, timbrar una factura de prueba.
9. Si algo falla, se corrige en local, se hace un nuevo commit y se repite desde el paso 4. Producción sigue intacta.

## 4. Producción (ventana de mantenimiento, de noche) 🔒

1. 🔒 Respaldo **inmediato antes** de tocar nada:
   - base de datos (phpMyAdmin → Exportar);
   - zip de `~/Hikari` **sin** `vendor` (incluye `storage/` y `.env`);
   - zip de `~/public_html`.
2. `php artisan down --refresh=15` en `~/Hikari`.
3. Renombrar las carpetas actuales a `~/Hikari_old` y `~/public_html_old`. **No borrarlas** hasta que pase una semana.
4. Extraer el zip nuevo en `~/` y luego:
   - mover `~/Hikari_old/.env` → `~/Hikari/.env` y actualizarlo con la sección 6;
   - mover `~/Hikari_old/storage` → `~/Hikari/storage`, que conserva imágenes, logs y archivos.
5. Comandos de la sección 5.
6. `php artisan hikari:preflight`: todo en ✓ (las ! de facturación pueden esperar).
7. Stripe **live**: crear el webhook → `https://hikaricandlesandco.com/stripe/webhook` con los eventos:
   - `checkout.session.completed`
   - `checkout.session.async_payment_succeeded`
   - `checkout.session.async_payment_failed`
   - `checkout.session.expired`

   Copiar su `whsec_…` al `.env` y correr `php artisan config:cache`.
8. `php artisan up`.
9. Compra real pequeña (por ejemplo, $10 MXN con un producto temporal o una vela barata), confirmar pedido, correo y panel, y **reembolsarla desde Stripe**.

### Rollback (si algo sale mal)

1. `php artisan down`.
2. Renombrar `Hikari` → `Hikari_failed`, `Hikari_old` → `Hikari` y lo mismo con `public_html`. La carpeta `storage` hay que regresarla a `Hikari_old` antes de renombrar.
3. 🔒 Restaurar el respaldo de la base del paso 4.1. Las migraciones nuevas cambian `orders`, así que el código viejo necesita la base vieja.
4. `php artisan up`.

## 5. Comandos en el servidor (Terminal de cPanel)

```bash
cd ~/Hikari
php -v                                  # ≥ 8.2
php artisan down --refresh=15           # solo producción
php artisan migrate --force             # 🔒 tras el respaldo
php artisan storage:link                # o: ln -s ~/Hikari/storage/app/public ~/public_html/storage
php artisan optimize                    # config, rutas, vistas y eventos en caché
php artisan filament:optimize
php artisan hikari:preflight
php artisan up
```

Si el `php` de la terminal no es la versión correcta, usar la ruta de cPanel (por ejemplo, `/opt/cpanel/ea-php83/root/usr/bin/php`).

Cualquier cambio posterior al `.env` requiere `php artisan config:cache`.

## 6. `.env` de producción

```
APP_NAME="Hikari Candles & Co."
APP_ENV=production
APP_DEBUG=false
APP_URL=https://hikaricandlesandco.com
APP_TIMEZONE=America/Mexico_City
APP_LOCALE=es
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=…
DB_USERNAME=…
DB_PASSWORD=…

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database
HIKARI_QUEUE_VIA_SCHEDULER=true

MAIL_MAILER=smtp
MAIL_HOST=mail.hikaricandlesandco.com
MAIL_PORT=465
MAIL_SCHEME=smtps
MAIL_USERNAME=pedidos@hikaricandlesandco.com
MAIL_PASSWORD=…
MAIL_FROM_ADDRESS=pedidos@hikaricandlesandco.com
MAIL_FROM_NAME="Hikari Candles & Co."

ADMIN_EMAILS=…                       # correo de Charlotte (y de Jorge)
HIKARI_REPORT_EMAILS=…

STRIPE_SECRET=sk_live_…
STRIPE_WEBHOOK_SECRET=whsec_…

HIKARI_PRIVACY_RESPONSIBLE="…"
HIKARI_PRIVACY_ADDRESS="…"
HIKARI_PRIVACY_EMAIL=…
HIKARI_PRIVACY_PHONE="871 781 1541"

# Facturación (cuando esté lista)
FACTURAMA_USER=
FACTURAMA_PASSWORD=
FACTURAMA_SANDBOX=false
HIKARI_CFDI_EXPEDITION_PLACE=
```

`APP_KEY` se conserva del `.env` actual: no se regenera, porque invalidaría sesiones y datos cifrados.

## 7. Cron (cPanel → Cron Jobs)

Un solo cron, **cada minuto**:

```
* * * * * cd ~/Hikari && php artisan schedule:run >> /dev/null 2>&1
```

Con `HIKARI_QUEUE_VIA_SCHEDULER=true` ese cron también procesa la cola (correos y cierres) sin un proceso permanente.

- **Si Neubox no permite cada minuto**, los correos se retrasan hasta el siguiente cron y el cierre de las 23:59 podría no correr. Para eso existe el **respaldo del día 1 a las 00:00**, que cierra el mes anterior si no se envió (requiere un intervalo que pase por el minuto 0, por ejemplo `*/5` o `*/15`). En ese caso conviene `QUEUE_CONNECTION=sync`, para que los correos salgan al momento.

## 8. Correo (entregabilidad)

1. cPanel → **Email Deliverability**: reparar **SPF** y **DKIM** del dominio hasta que queden en verde.
2. Agregar un registro **DMARC** TXT en `_dmarc` con `v=DMARC1; p=none; rua=mailto:<correo>`.
3. Probar con [mail-tester.com](https://www.mail-tester.com) enviando una confirmación de pedido desde staging.

## 9. Respaldos continuos

- cPanel → **Backup**: descargar el respaldo completo cada semana (base + `~/Hikari/storage`).
- Antes de cada deploy: el respaldo del paso 4.1.
- Conservar al menos 4 semanas de respaldos fuera del servidor.

## 10. Después del deploy

- Una semana sin problemas → 🔒 borrar `~/Hikari_old` y `~/public_html_old`.
- Revisar `~/Hikari/storage/logs/laravel.log` los primeros días.
- Último día del mes: confirmar que llegó "Aquí están las ventas de este mes".
- Facturación: completar la lista de `docs/fase-5.md` y pasar Facturama a producción.
- Pendiente de la Fase 4: Content-Security-Policy y decidir si se desactiva `/register`.
