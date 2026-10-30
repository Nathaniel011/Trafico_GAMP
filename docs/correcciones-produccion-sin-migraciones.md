# Correcciones de compatibilidad con la instalación anterior

Preparadas localmente el 30 de octubre de 2026. No se desplegaron en producción ni se cambió su base de datos.

## Problemas y solución implementada

| Problema observado | Corrección de código |
| --- | --- |
| `/casetas` y `/casetas/puntos`: tabla `casetas` inexistente | Detectar capacidades del esquema mediante consultas de metadatos; usar `certificates` y `notices` originales para listado, búsqueda y mapa. |
| Crear notificaciones de casetas exige una ficha inexistente | Formulario independiente con número oficial, fecha, dirección, fotografía y ubicación; guardar en `notices` y `uploaded_files` existentes. |
| `/inspectors/1`: falta `show` | Acción y vista de consulta sin exponer contraseñas. |
| `/groups/create`: falta `create` | Alta con nombre, descripción, año y logo, usando la tabla existente; restringida a ADMIN/Jefe. |
| Reporte carga 849 filas juntas | Paginación de 30 filas; conservar filtros y numeración. |
| Datos técnicos y parqueos intentan escribir metadatos nuevos | Mantener coordenadas y campos originales; omitir exclusivamente los metadatos nuevos cuyas columnas no existan. |

El modo anterior conserva el flujo legal → Completar técnico. No convierte certificados en casetas únicas ni inventa licencias, secuencias, vencimientos o relaciones. Las acciones que requieren fichas nuevas responden con un mensaje de indisponibilidad antes de consultar modelos de tablas ausentes. Con el esquema completo, se conserva el flujo nuevo.

La precisión, fuente y fecha de captura GPS no se persisten cuando faltan esas columnas; sí se validan la confirmación y las coordenadas. Para conservar esos metadatos y activar fichas únicas/licencias/procedimientos haría falta una ampliación del esquema, fuera del alcance autorizado.

## Despliegue exclusivamente de código y configuración

Los arreglos se entregan en la rama `develop`, con el commit `correcciones`. Publicar el código en Git no lo despliega en producción. Para desplegar, actualizar los archivos de aplicación, vistas y proveedores conservando `.env`, `storage` y los datos del servidor. No cambian dependencias ni recursos compilados.

En el `.env` del servidor establecer:

```dotenv
APP_ENV=production
APP_DEBUG=false
```

Conservar `caseta_dossier` y `recaudaciones` en `false` dentro de `config/features.php`, como están en esta entrega. Son valores de configuración del archivo, no variables del `.env`.

Desde la carpeta del proyecto en el servidor, regenerar únicamente las cachés de código/configuración:

```sh
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Estos comandos no se han ejecutado en producción. No ejecutar migraciones, seeders, `migrate:fresh`, `db:wipe` ni reinicializar usuarios. Esta entrega no crea ninguna cuenta.

Verificar por lectura: `/casetas`, `/casetas/mapa` y su petición `/casetas/puntos`; listado/detalle de notificaciones con filtro de gestión; detalle de inspector; formulario de sindicatos autorizado; reporte paginado y formulario Completar técnico. Revisar registros internos si surge una excepción; el navegador debe mostrar una página genérica sin SQL ni conexión.

## Validación y límites

Resultado final local: **146 pruebas, 1.771 aserciones**, PHP 8.5.12; suite completa aprobada. Informe técnico: `.local/concrete-fixes-suite.xml` (archivo local ignorado por Git). Se comprobó además el flujo HTTP legal → técnico y guardado de parqueos con el esquema original.

Las pruebas automatizadas utilizan SQLite en memoria, con casos que aplican exclusivamente las migraciones originales anteriores a 2026 y otros que utilizan el esquema completo. Ningún formulario de negocio se envió a producción. La compatibilidad se comprobó contra las migraciones originales del repositorio; no equivale a conocer todas las modificaciones manuales que pudiera tener PostgreSQL en el servidor.

La creación de avisos en PostgreSQL serializa por tipo/gestión/número mediante un bloqueo transaccional parametrizado para evitar duplicados concurrentes sin añadir índices. Esa rama específica de PostgreSQL se revisó en código; no se ejecutó contra producción ni se validó con una prueba concurrente PostgreSQL.

La revisión de producción fue de lectura y autenticación. No se comprobó allí el guardado de usuarios, certificados, parqueos, sindicatos ni notificaciones. Esas escrituras se prueban únicamente en bases aisladas; el despliegue sigue pendiente.
