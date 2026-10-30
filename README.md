# CertTráfico — develop

Sistema de Tráfico y Vialidad con PHP 8.5.12, Laravel 13 y vistas Blade. Conserva las etapas legal y técnica de develop e incorpora el control de casetas, permisos, notificaciones, GPS y mapas Leaflet/OpenStreetMap.

## Instalación inicial

Configurar el archivo .env con los valores del servidor, instalar las extensiones indicadas en composer.json y servir la carpeta public/.

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan db:seed --force
```

En una instalación nueva hay que generar APP_KEY con `php artisan key:generate`. Al actualizar una instalación existente se conserva su APP_KEY, base y adjuntos; ensayar previamente las migraciones sobre un respaldo del motor real.

### Única cuenta inicial

El seeder crea exclusivamente esta cuenta cuando la tabla users está vacía:

| Dato | Valor |
|---|---|
| Nombre | Administrador |
| Usuario | `administrador` |
| Contraseña inicial | `administrador` |
| Rol | Administrador (`ADMIN`) |

La contraseña se guarda como hash. No se crean usuarios de muestra, inspectores ni otros perfiles. Repetir el seeder no duplica cuentas ni restablece contraseñas. Si la base ya tiene usuarios, se conservan y el seeder no crea ninguna cuenta adicional.

Después de ingresar, la clave puede cambiarse desde Mi cuenta → Cambiar mi contraseña. Los demás usuarios se crean desde Perfiles; solo Administrador y Jefe de Unidad tienen ese permiso. El sistema permite una sola cuenta ADMIN.

## Operación y validación

Servir por HTTPS para GPS y cámara fuera de localhost y programar `php artisan schedule:run` cada minuto.

```bash
php vendor/bin/phpunit
node --test tests/JavaScript/map-support.test.js
npm run build
```

La [guía de adaptación](docs/adaptacion-develop.md) describe los módulos y los permisos. Recaudaciones y el expediente ampliado permanecen desactivados. La configuración local, dependencias instaladas y bases de prueba quedan excluidas de Git.
