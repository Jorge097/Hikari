# Hikari Candles & Co

**Hikari** es una tienda en línea de velas artesanales, elegidas cuidadosamente para crear ambientes mágicos, relajantes y cuidadosamente diseñados. Nuestro objetivo es ofrecer una experiencia premium, desde el diseño del producto hasta la entrega al cliente.

Visita nuestra página en Instagram para inspirarte con nuestro estilo: [@hikari.candlesandco](https://www.instagram.com/hikari.candlesandco/?hl=es-la)

---

## Descripción

Hikari es un e-commerce de velas artesanales.  
Ofrecemos distintas colecciones: velas aromáticas, velas minimalistas, velas para meditación y velas de edición limitada. Cada vela es elaborada a mano y empaquetada con mucho detalle para brindar una experiencia atractiva tanto visual como olfativa.

---

## Funcionalidades

- **Gestión de Temporadas:** Lógica de negocio para activar/desactivar catálogos completos dinámicamente.
- **Catálogo Interactivo:** Vistas detalladas de productos con filtrado por disponibilidad y stock.
- **Stack Moderno:** Uso de Laravel Breeze + React para autenticación y frontend.
- **Diseño Responsive:** Interfaz estilizada con Tailwind CSS, adaptada a móviles y escritorio.
- **Base de Datos Relacional:** Estructura optimizada con MySQL.
  
---

## Tecnologías

Aquí puedes listar las tecnologías que usas para construir la tienda:

### Backend 
Encargado de la seguridad, manejo de datos y reglas de negocio.
- **Framework:** ![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=flat-square&logo=laravel&logoColor=white) (v10.x)
- **Lenguaje:** ![PHP](https://img.shields.io/badge/PHP-777BB4?style=flat-square&logo=php&logoColor=white) (v8.1+)
- **Puente:** ![Inertia](https://img.shields.io/badge/Inertia.js-9553E9?style=flat-square&logo=inertia&logoColor=white) (Server-side Adapter)
- **Autenticación:** Laravel Breeze.

### Frontend
SPA (Single Page Application) reactiva y optimizada.
- **Librería:** ![React](https://img.shields.io/badge/React-20232A?style=flat-square&logo=react&logoColor=61DAFB)
- **Estilos:** ![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=flat-square&logo=tailwind-css&logoColor=white)
- **Compilador:** ![Vite](https://img.shields.io/badge/Vite-646CFF?style=flat-square&logo=vite&logoColor=white)
- **Lenguaje:** JavaScript (JSX).

### Base de Datos 
- **Motor:** ![MySQL](https://img.shields.io/badge/MySQL-005C84?style=flat-square&logo=mysql&logoColor=white) (v8.0)
- **ORM:** Eloquent (Manejo de modelos y relaciones).
- **Gestión:** Migraciones y Seeders para control de versiones de esquema.

## Instalación y Configuración
1. Clona el repositorio:
   ```bash
    git clone https://github.com/Jorge097/Hikari.git
   cd Hikari

2. Instalar dependencias
   ```bash
       composer install
       npm install
   
3. Configurar entorno
    ```bash
    cp .env.example .env
    php artisan key:generate

4. Base de Datos
- Asegúrate de tener el servicio de MySQL corriendo. Edita tu archivo .env:
  
   ```bash
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=database_name
   DB_USERNAME=root
   DB_PASSWORD=password
   
5. Migraciones y Seeders
   ```bash
    php artisan migrate:fresh --seed
   
6. Ejecutar
   
- Necesitas dos terminales:
  - Terminal 1
    
       ```bash
        php artisan serve
   - Terminal 2
     
        ```bash
        npm run dev

7. Visita http://127.0.0.1:8000


© 2025 Jorge Silva. Todos los derechos reservados.
Este proyecto no puede ser usado, modificado ni vendido sin autorización del autor.

Contacto

Jorge Silva – jorge.silva-10@outlook.com
Repositorio: https://github.com/Jorge097/Hikari
