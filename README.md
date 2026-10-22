# Trafico_GAMP

Sistema de Tráfico y Vialidad desarrollado con Laravel y vistas Blade.

El proyecto principal está actualizado a **Laravel 13.38.0 y PHP 8.5.12**. Conserva su estructura habitual: `app`, `bootstrap`, `config`, `database`, `public`, `resources`, `routes`, `storage` y `tests`. Las dependencias están fijadas en `composer.lock`.

## Requisitos

- PHP 8.5.12 o un parche posterior de la rama 8.5, tanto para la consola como para el servidor web.
- Composer 2 y las extensiones declaradas en `composer.json`.
- Driver PDO del motor de datos configurado en `.env`.

## Instalación y ejecución

Desde esta misma carpeta y con PHP 8.5 configurado en el entorno:

```bash
composer install
composer check-platform-reqs
php artisan serve
```

Conservar el `.env`, la `APP_KEY`, la base de datos y los adjuntos de una instalación existente. En una instalación nueva, copiar `.env.example` a `.env`, configurar los datos del entorno y ejecutar `php artisan key:generate` una sola vez.

La base SQLite de la prueba local se conserva en `database/certtrafico-local.sqlite`, excluida del control de versiones. Las migraciones históricas fueron diseñadas para PostgreSQL: confirmar el motor antes de ejecutar migraciones. La migración aditiva de Sanctum incorpora `expires_at` a los tokens y debe aplicarse si la instalación todavía no tiene esa columna.

Para las pruebas del proyecto:

```bash
php artisan test
```

## Servidor

Trabajar directamente con esta carpeta. Configurar el sitio para servir únicamente `public/`, conservar la clave y los archivos existentes y dar permisos de escritura a `storage/` y `bootstrap/cache/`. En producción, usar HTTPS, `APP_ENV=production` y `APP_DEBUG=false`.

Instalar las dependencias de producción con `composer install --no-dev --optimize-autoloader` y compilar configuración y vistas en el servidor mediante los comandos estándar de Artisan. No se necesita una carpeta `dist`, un ZIP ni scripts auxiliares.
