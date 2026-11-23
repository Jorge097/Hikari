# Fase 4 — Aviso de privacidad, roles y hardening

Fecha: 2026-11-22.

## Aviso de privacidad

- Página pública **`/aviso-de-privacidad`** (enlace en el footer y en el checkout).
- Redactado conforme a la **LFPDPPP publicada en el DOF el 20-mar-2025** (abrogó la ley de 2010; el INAI desapareció y la autoridad ahora es la Secretaría Anticorrupción y Buen Gobierno). Incluye responsable, datos, finalidades necesarias y adicionales, remisiones a proveedores (Stripe, paquetería, PAC, contador, hosting), derechos ARCO, revocación, cookies/localStorage y cambios.
- **Es un borrador: debe revisarlo un abogado antes de producción.**
- Datos del responsable en `.env` (mientras falten, la página los marca como *pendiente*):

```
HIKARI_PRIVACY_RESPONSIBLE="Nombre o razón social"
HIKARI_PRIVACY_ADDRESS="Calle, número, colonia, CP, ciudad, estado"
HIKARI_PRIVACY_EMAIL=privacidad@...
HIKARI_PRIVACY_PHONE="871 781 1541"
```

- Al cambiar el texto, actualiza `privacy.version` en `config/hikari.php`.

### Consentimiento en el checkout

Casilla obligatoria "He leído y acepto el aviso de privacidad". Sin ella el servidor rechaza el pedido. Cada pedido web guarda `privacy_accepted_at` y `privacy_version` como evidencia.

## Roles del panel

| Rol | Ventas (pedidos, clientes, pagos, reportes) | Catálogo | Usuarios |
|---|---|---|---|
| **Administrador** | ver, crear, editar, exportar, generar/regenerar cierres | sí | sí |
| **Contador** | solo ver y exportar XLSX/CSV, descargar reportes | no | no |
| Sin rol | sin acceso al panel | — | — |

- Columna `users.role` (`admin`, `contador` o vacío).
- Los correos en `ADMIN_EMAILS` siempre son administradores (acceso de respaldo).
- Panel → Configuración → **Usuarios**: el admin crea la cuenta del contador (contraseña de 12+ caracteres). Nadie puede borrar su propia cuenta.
- Las cuentas del registro público del sitio no tienen rol, así que no entran al panel.
- Policies en `app/Policies` (`SalesPolicy` = lectura para el contador, `AdminOnlyPolicy` = solo admin).

## Hardening

- Middleware `SecurityHeaders`: `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`; **HSTS** solo en producción con HTTPS.
- `URL::forceScheme('https')` en producción.
- `.gitignore`: `*.cer`, `*.key`, `*.pfx`, `*.pem` (CSD/FIEL de la Fase 5 nunca al repo).
- `composer audit` y `npm audit`: **0 vulnerabilidades** (2026-11-22).

### `.env` de producción (Fase 6)

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://dominio
SESSION_SECURE_COOKIE=true
LOG_LEVEL=warning
```

## Pendiente

- Revisión del aviso por un abogado y datos del responsable (domicilio y correo).
- Content-Security-Policy: queda para después del deploy (Filament, Vite y Stripe requieren ajustarla con cuidado).
- Decidir si se desactiva el registro público (`/register`), que hoy no da acceso a nada.
