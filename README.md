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

La secuencia para actualizar una instalación existente, conservar sus datos y comprobar la recuperación está en [Actualización de producción](docs/produccion.md).

Trabajar directamente con esta carpeta. Configurar el sitio para servir únicamente `public/`, conservar la clave y los archivos existentes y dar permisos de escritura a `storage/` y `bootstrap/cache/`. En producción, usar HTTPS, `APP_ENV=production` y `APP_DEBUG=false`.

Instalar las dependencias de producción con `composer install --no-dev --optimize-autoloader` y compilar configuración y vistas en el servidor mediante los comandos estándar de Artisan. No se necesita una carpeta `dist`, un ZIP ni scripts auxiliares.

## Casetas, mapa y notificaciones

El registro está en /casetas y el mapa unificado en /casetas/mapa. El alcance activo comprende los puntos 1 al 6: resumen de caseta, licencias, perfiles, GPS confirmado, fotografías privadas y tres notificaciones con vencimiento de 48 horas. El expediente e historial ampliado del punto 7 y Recaudaciones están aplazados y desactivados en config/features.php.

Para migraciones, perfiles y programador estándar de Laravel, consultar [Uso y activación](docs/uso-casetas.md). Los accesos y operaciones de Recaudaciones, versiones de certificados y vinculación histórica permanecen bloqueados. Se conservan los datos anteriores y el código en reserva para retomarlos posteriormente.

Los recursos nuevos usan Vite y se entregan compilados en public/build. Para modificarlos, usar Node.js compatible con Vite, ejecutar npm ci y npm run build. Para desarrollo, npm run dev. PHP puede servir los archivos compilados sin Node.js.

Todos los mapas interactivos usan Leaflet y OpenStreetMap con proveedor configurable; incluyen casetas, parqueos y perímetro municipal local: [Mapas y precisión GPS](docs/mapas-y-gps.md). La precisión física debe validarse con los teléfonos de campo.

## Interfaz institucional

El menú y el panel de inicio usan una identidad municipal en granate, blanco y gris, con los recursos gráficos existentes. El acceso sigue la referencia visual solicitada: tarjeta blanca centrada, fondo con ondas en la paleta institucional granate y gris, escudo.png ampliado y controles de usuario y contraseña. La portada pública conserva su diseño original, con fotografía de fondo, escudo, cabecera y secciones del tema anterior, con los colores del panel, y mantiene el mapa actualizado con Leaflet. Las opciones nuevas se integran en grupos del menú con iconos, búsqueda y selección de la pantalla actual. Las tablas y formularios se adaptan al teléfono.

La paleta común está en resources/css/institutional-palette.css. El panel y el acceso usan resources/css/institutional.css; la portada usa resources/css/public.css para conservar su distribución y tipografía anteriores. Las interacciones están en resources/js/institutional.js. Se compila mediante Vite y se entrega en public/build. La base de Bootstrap y AdminLTE se conserva, sin añadir dependencias. Los colores reglados de licencias y notificaciones mantienen su significado.

## Repositorio y validación

Se incluyen el código, las migraciones, composer.lock, package-lock.json y los recursos de producción de public/build. La configuración de referencia es .env.example. Las bases de datos, las credenciales, los adjuntos locales, las dependencias instaladas y los archivos del entorno de ensayo quedan excluidos de Git.

Para comprobar un cambio antes de subirlo, ejecutar las pruebas PHP, las de GPS y la compilación:

```bash
php artisan test
node --test tests/JavaScript/map-support.test.js
npm run build
```

La actualización del servidor requiere ensayar las migraciones sobre una copia de la base real y conservar su configuración y adjuntos, según docs/produccion.md.
