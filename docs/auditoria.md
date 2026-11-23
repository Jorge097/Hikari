# Auditoría inicial — Hikari Candles & Co.

Fecha: 2026-11-22 · Fase 0 del plan (`HIKARI_PLAN.md`).
Estado del código en `main` (commit `08a9a1a`) más cambios locales sin commit.

---

## 1. Entorno local

| Elemento | Estado |
|---|---|
| Ruta del proyecto | `C:\Users\jorge\OneDrive - Universidad Tecmilenio\Documents\Proyectos Dev\Hikari` (movido desde `C:\xampp\htdocs\Hikari`) |
| PHP usado | `C:\xampp\php\php.exe` 8.2.12. El PHP de winget (8.3) **no tiene `pdo_mysql` habilitado** y no conecta a MySQL |
| DB | MySQL local, base `hikari`. 15 migraciones aplicadas |
| Arranque | `php artisan serve` (puerto 8000) + `npm run dev` (Vite, 5173) |
| `APP_URL` | `http://127.0.0.1:8000` |
| `public/storage` | Enlace (junction) recreado con `php artisan storage:link` |
| `.env` | Existe y está en `.gitignore` |

`composer dev` también lanza `pail`, que requiere `pcntl` y no funciona en Windows.

### Datos en la DB local

| Tabla | Filas |
|---|---|
| users | 1 |
| categories | 10 |
| products | 45 |
| scents | 20 |
| sizes | 3 |
| product_size / product_scent | 0 / 0 |
| orders / order_items | 0 / 0 |

Las **imágenes de productos no existen en local** (`storage/app/public` está vacío); el sitio muestra los productos sin foto. Viven solo en producción.

---

## 2. Qué existe hoy

### Rutas (`routes/web.php`)
| Ruta | Página | Notas |
|---|---|---|
| `/` | `Welcome` | Productos de temporada (`is_seasonal`) + categorías |
| `/velas` | `Velas` | Catálogo con búsqueda y filtro por categoría |
| `/categorias` | `Categorias` | |
| `/producto/{slug}` | `ProductDetail` | Selector de aroma y tamaño |
| `/pedido` | `Cart` | Carrito → WhatsApp |
| `/Conocenos` | `Conocenos` | URL con mayúscula |
| `/dashboard`, `/profile`, auth | Breeze | Registro público abierto |
| `/panel_hikari` | Filament | Panel admin |

### Modelos
- `Category` → hasMany `Product`. Campos: name, slug, description, is_active, sort.
- `Product` → belongsTo `Category`, belongsToMany `Size`. Columnas reales: name, slug, description, `price` (decimal 8,2), size (texto), is_seasonal, is_active, image, sort.
- `Scent` (aromas) y `Size` (tamaños con `extra_price` decimal). Hay tablas pivote `product_scent` y `product_size`, **ambas vacías**: en la práctica todos los aromas y tamaños aplican a todos los productos.
- `Order` / `OrderItem`: existen pero **no se usan** (0 filas, sin controlador ni recurso Filament).
- `User`: `canAccessPanel()` devuelve `true` siempre.

### Panel Filament (`app/Filament/Admin/Resources`)
Recursos: Categorías (con relation manager de productos), Productos, Aromas, Tamaños. No hay recursos de pedidos, clientes, pagos ni reportes.

### Flujo de pedido actual (WhatsApp)
1. `ProductDetail` arma el artículo en el navegador (producto + aroma + tamaño) y lo guarda en `localStorage` (`useCart`, clave `hikari_cart`).
2. `Cart` calcula el total **en el cliente** y abre `wa.me/528717811541` con el mensaje del pedido.
3. No se guarda nada en el servidor: no hay folio, registro de venta ni pago.

### Tests
Solo los de Breeze (`tests/Feature/Auth`, `ProfileTest`, `ExampleTest`).

---

## 3. Problemas encontrados

### Seguridad (prioridad alta)
1. **Cualquier usuario registrado entra al panel admin.** El registro de Breeze (`/register`) es público y `User::canAccessPanel()` devuelve `true`. Corregir en Fase 4 (roles); conviene un parche mínimo antes (cerrar registro o limitar por correo/rol).
2. Precios calculados en el navegador. Hoy no importa (no hay cobro), pero el checkout de Fase 2 **debe recalcular todo en el servidor**.

### Configuración
3. **Conflicto de Tailwind:** `tailwindcss ^3.2.1` + `@tailwindcss/vite ^4`. En la práctica se usa **v3**: `postcss.config.js` carga `tailwindcss`, `app.css` usa `@tailwind base/...` y `vite.config.js` **no** importa `@tailwindcss/vite`. Recomendación: quitar `@tailwindcss/vite` (cambio mínimo, sin tocar estilos). Migrar a v4 queda como mejora opcional.
4. **`"composer": "^4.1.0"` en `dependencies` de npm:** nada en `resources/js` la importa. Quitar.
5. **`public/index.php` en git está hecho para Neubox** (rutas `../Hikari/vendor`, `../Hikari/bootstrap` y `usePublicPath`). En local está modificado sin commit para apuntar a `../vendor`. Hay que unificarlo (p. ej., detectar la ruta o documentar el layout de producción) antes del deploy para no romper ninguno de los dos.
6. La ruta de mantenimiento en `public/index.php` sigue apuntando a `../Hikari/storage/...`.
7. Archivo `laravel` en la raíz (sin seguimiento en git): es una **base SQLite** con el esquema de la app, probablemente creada por error. Confirmar y borrar o ignorar.
8. Cambios locales sin commit: `AdminPanelProvider` (agrega `->login()`), `public/index.php`, lockfiles y assets publicados de Filament (`public/js|css|fonts/filament`). Decidir qué se versiona.

### Modelo de datos
9. Precios en `decimal`, no en centavos enteros; sin moneda.
10. `Product::$fillable` incluye `stock`, `aroma` y `presentation`, que **no existen** como columnas.
11. `orders` exige `user_id` (obliga a cuenta) y no tiene folio, origen, desglose de IVA/envío, moneda, tipo de cambio ni datos de cliente/envío estructurados.
12. `order_items` no guarda aroma, tamaño ni nombre del producto al momento de la venta.
13. El `down()` de `add_sort_to_products_table` está vacío (no revierte).
14. En `ProductForm`, el toggle **"Activo" en realidad edita `is_seasonal`**; `is_active` no se puede editar desde el formulario.
15. `Product::isImperial()` compara con el nombre de categoría `'IsImperial'`; parece código muerto.

---

## 4. Qué falta (según el plan)

| Requerimiento | Estado |
|---|---|
| RF-1 Variantes con precio por variante, centavos, stock | Parcial: aromas/tamaños globales, `extra_price` decimal, sin stock |
| RF-1 Carrito/checkout en sitio | Carrito en `localStorage`; sin checkout |
| RF-2 Pedidos, folio `HK-###`, origen web/WhatsApp, desglose | No existe (tablas base sin usar) |
| RF-2 Registro manual de pedidos WhatsApp en Filament | No existe |
| RF-3 Stripe + webhooks | No existe |
| RF-4 Correo de confirmación | No existe; SMTP sin configurar |
| RF-5 Guías de envío | No existe |
| RF-6 CFDI / PAC | No existe |
| RF-7 Recursos Pedidos/Clientes/Pagos/Reportes, filtros, exportación, roles | No existe |
| RF-8 Cierre mensual automatizado | No existe (`routes/console.php` vacío) |
| RF-9 Aviso de privacidad | No existe |

---

## 5. Avance de Fase 0

- [x] Quitados `@tailwindcss/vite` y `composer` de `package.json` (se queda Tailwind v3). `npm run build` OK.
- [x] `public/index.php` detecta la estructura: usa `../Hikari` si existe (Neubox) o `../` (local). También corrige la ruta de mantenimiento.
- [x] Panel restringido: `canAccessPanel()` solo permite correos en `ADMIN_EMAILS` (`.env`). El registro público sigue abierto pero ya no da acceso al panel. Roles completos en Fase 4.
- [x] `ExampleTest` usa `RefreshDatabase` (fallaba por falta de tablas). 25/25 tests pasan.
- [ ] Archivo `laravel` (SQLite sin datos de negocio: solo migraciones y 5 sesiones). Pendiente borrarlo a mano.
- [ ] Siguiente: Fase 1 (modelo de pedidos/clientes/pagos y folio).

Variable nueva de `.env`: `ADMIN_EMAILS` (correos separados por coma). **Agregarla en Neubox antes del próximo deploy** o nadie podrá entrar al panel.
