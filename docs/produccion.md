# Actualización de la instalación existente

Fecha de preparación: 23 de octubre de 2026.

El proyecto se mantiene en sus carpetas originales, con Laravel 13 y PHP 8.5.12 o un parche posterior de la rama 8.5. Los recursos están compilados en public/build. No se necesita una distribución separada ni scripts de despliegue.

## Alcance de esta actualización

Solo están activos los puntos 1 al 6. El punto 7 (expediente e historial ampliado) y Recaudaciones están aplazados en config/features.php. Sus pantallas y operaciones están bloqueadas; no se deben incluir como comprobaciones de aceptación ni configurar integraciones externas. No eliminar sus tablas ni datos si proceden de un ensayo anterior.

## Antes de intervenir el servidor

Confirmar el motor, su versión y el historial de migraciones de la instalación. Se ensayó SQLite y PostgreSQL 18 local; todavía no se dispone del respaldo actual del servidor. No se propone cambiar de motor ni sustituir la base real por la de pruebas.

Respaldar y comprobar la restauración de la base completa, la configuración y los adjuntos. Incluir storage/app, archivos públicos históricos y cualquier ubicación adicional que utilice la instalación. Conservar el mismo conjunto de base y archivos para recuperar fotografías y documentos.

Restaurar el respaldo actual en un entorno de ensayo con el mismo motor y versión del servidor. Comparar los registros de migraciones y aplicar allí la actualización antes de hacerlo sobre producción. El ensayo incluido comprueba conservación de valores anteriores, usuarios, hashes de contraseña, tokens, documentos, fotografías y fechas; no detecta modificaciones particulares de una base que aún no se ha recibido.

La instalación PostgreSQL original utiliza uuid_generate_v4(). Comprobar que la extensión uuid-ossp existente sigue disponible en el esquema utilizado. Una instalación PostgreSQL nueva requiere habilitarla antes de ejecutar las migraciones históricas. No hace falta recrearla en una instalación que ya la tiene.

## Aplicación del cambio

1. Programar una ventana de mantenimiento y activar el mantenimiento con php artisan down. Usar PHP 8.5.12 o superior de la rama 8.5 tanto en consola como en el servidor web.
2. Actualizar los archivos de esta misma aplicación. Conservar el .env, la APP_KEY, la base y los adjuntos existentes. No ejecutar key:generate sobre la instalación actual. No transferir bases locales, storage/framework/testing, node_modules, archivos temporales, registros de pruebas ni credenciales de ensayo.
3. Instalar las versiones fijadas con composer install --no-dev --optimize-autoloader. Comprobar composer check-platform-reqs --no-dev. No ejecutar composer update para este despliegue.
4. Servir exclusivamente public/. Permitir escritura al usuario del servidor web en storage y bootstrap/cache. Confirmar que public/build/manifest.json y todos los recursos que referencia llegaron juntos. No conservar public/hot de desarrollo en producción.
5. Mantener APP_ENV=production, APP_DEBUG=false, APP_URL con el dominio HTTPS real y SESSION_SECURE_COOKIE=true. Mantener las credenciales del servidor fuera de Git. Si hay un proxy TLS, configurar solo sus direcciones conocidas como proxies de confianza y comprobar que la aplicación genera enlaces HTTPS.
6. Revisar php artisan migrate:status. Con el ensayo del respaldo ya completado, ejecutar php artisan migrate --force. Hay cinco migraciones nuevas de control de casetas; la migración anterior de Sanctum también se aplicará si está pendiente. Estas migraciones no crean registros de demostración ni vinculan documentos antiguos automáticamente.
7. Ejecutar php artisan config:cache, php artisan route:cache y php artisan view:cache. Reiniciar PHP/OPcache según el servicio del servidor. Reiniciar trabajadores de cola únicamente si esa instalación los utiliza.
8. Configurar el programador estándar de Laravel para ejecutar php artisan schedule:run cada minuto con el usuario y el PHP correctos. Comprobar php artisan schedule:list. El programador registra vencimientos y limpia fotografías temporales; el estado rojo también se calcula al consultar.
9. Conservar al menos una cuenta ADMIN o JEFE_UNIDAD. Asignar los perfiles reales desde /perfiles. El comando habitual php artisan create:user --role=JEFE_UNIDAD permite crear una cuenta si se necesita, solicitando sus datos interactivamente.
10. Comprobar las funciones siguientes antes de terminar con php artisan up y repetir una comprobación desde el dominio público.

## Comprobación funcional

- Acceso con los tres perfiles, edición permitida para cada uno y consulta del Asesor Legal sin escritura.
- Listado y mapa con casetas y documentos históricos pendientes, sin perder el acceso a registros antiguos.
- Confirmar que /casetas/documentos-historicos y /certificate/history/1 no están disponibles, al igual que las operaciones de vinculación histórica.
- Nueva inspección, certificado PDF y QR; edición normal y ubicación compartida. Revisar los nombres y firmas institucionales del formato original con los responsables de emisión.
- Fotografía visible y confirmada antes del guardado; abrir el adjunto definitivo después de guardar y volver a entrar.
- GPS desde un teléfono real por HTTPS, permisos, precisión informada y corrección manual con motivo. Leaflet y OpenStreetMap no modifican la precisión física del receptor.
- Búsqueda exacta de número y gestión, incluidos ceros iniciales y avisos históricos.
- Primer, segundo y tercer aviso; rojo cuando se cumplen 48 horas desde la fecha y hora del tercero. Un aviso antiguo sin secuencia verificada no inicia un plazo.
- Consulta de licencias. Confirmar que Recaudaciones no aparece en la navegación y que sus rutas de consulta e importación están bloqueadas, incluso con una cuenta de jefatura.

El PDF conserva su croquis Mapbox cuando existe MAPBOX_ACCESS_TOKEN válido. Si el token falta, la conexión falla o la respuesta no es una imagen válida, sigue generándose con las coordenadas y una indicación para consultar el mapa interactivo. Los mapas interactivos utilizan Leaflet y el proveedor configurado de teselas; revisar sus condiciones y capacidad antes de un uso intensivo.

## Recuperación

Si falla una comprobación, mantener el mantenimiento, conservar el estado fallido y el respaldo anterior y corregir o restaurar el conjunto ensayado. No ejecutar migrate:fresh, migrate:reset ni una reversión destructiva sobre la base real. Después de capturar datos nuevos, preservarlos antes de considerar cualquier restauración; las tablas nuevas contienen historial y evidencias que deben conservarse.

## Referencias

- [Despliegue de Laravel 13](https://laravel.com/framework/docs/13.x/deployment).
- [Migraciones de Laravel 13](https://laravel.com/framework/docs/13.x/migrations).
- [Extensión uuid-ossp de PostgreSQL](https://www.postgresql.org/docs/18/uuid-ossp.html).
- [Uso de casetas y alcance vigente](uso-casetas.md).
