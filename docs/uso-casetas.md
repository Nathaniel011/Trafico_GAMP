# Uso y activación de las mejoras de casetas

Fecha: 23 de octubre de 2026.

## Alcance activo

Por decisión del usuario, se mantienen los puntos 1 al 6. El punto 7 y Recaudaciones quedan aplazados. config/features.php mantiene caseta_dossier=false y recaudaciones=false: no se muestran sus menús, expediente, cronología, verificaciones de pago ni versiones; las rutas de consulta, importación y vinculación devuelven 404. La edición normal de certificados continúa sin exigir motivo de revisión ni crear versiones nuevas. Se conservan las tablas y los datos anteriores, junto con los registros internos necesarios para los flujos activos.

- Casetas independientes con propietario, CI, contacto, ubicación y observaciones.
- Licencias con número, gestión, emisión, vencimiento y fuente, sin consulta de pagos.
- Falta de licencia verificada, situación por verificar y declaración del propietario (X) diferenciadas.
- Resumen operativo con propietario, ubicación, estado de licencia, avisos y procedimientos. La edición de certificados detecta cambios simultáneos; el expediente e historial ampliado están desactivados.
- Los puntos grises mantienen visibles los documentos antiguos en el mapa. La bandeja y la vinculación histórica del expediente están desactivadas; no se infieren secuencias ni plazos.
- Tres avisos: amarillo, azul y verde con 3; rojo a las 48 horas del tercero.
- Búsqueda exacta de número y gestión conservando ceros y tipos históricos SHOP/SHOPS.
- Mapa autenticado con filtros, resumen, fechas, observaciones y actualización cada minuto. Carga por zona visible, hasta 1000 puntos por consulta, con aviso para acercar el mapa.
- GPS confirmado, precisión visible y correcciones manuales con motivo. Los nuevos certificados y avisos actualizan la ubicación compartida conservando las coordenadas de los documentos anteriores.
- Fotos con vista previa, carga previa al guardado y reintento. Los archivos nuevos son privados y el temporal solo puede consumirse una vez por su propietario.
- Perfiles web aplicados en el servidor: Inspector-Notificador, Asesor Legal y Jefe de Unidad, además de ADMIN.
- Correcciones de conclusión técnica negativa, retiro, fechas y coordenadas.
- Interfaz institucional para menú, inicio y acceso, compilada con Vite sobre Bootstrap y AdminLTE. El acceso usa una tarjeta centrada sobre ondas granate y gris, escudo.png ampliado y un control accesible para mostrar u ocultar la contraseña, según la referencia visual solicitada. La portada pública recupera su diseño original, con fotografía, escudo grande, cabecera y distribución anteriores, con la misma paleta institucional del panel. El menú incluye iconos, grupos, búsqueda y selección activa. Se conservan los logotipos existentes y los colores reglados de avisos y licencias.
- Todos los mapas interactivos usan Leaflet y OpenStreetMap, incluidos parqueos y perímetro público. Los parqueos guardan precisión y fuente mediante la cuarta migración aditiva.

## Operación

El número oficial se considera único por tipo y gestión. El orden 1/2/3 pertenece al procedimiento abierto de la caseta. No se añaden intervalos entre avisos que las notas no especifican.

Las 48 horas parten de la fecha y hora registrada del tercer aviso. Las notificaciones nuevas y sus plazos se almacenan en UTC y se muestran en hora de Bolivia; las fechas históricas mantienen su interpretación anterior.

Los avisos históricos sin secuencia verificada no activan plazos. No se admite un cuarto aviso en un procedimiento; jefatura puede cerrarlo con motivo conservando el historial. Un procedimiento posterior exige motivo de apertura.

El rojo identifica seguimiento pendiente, sin ejecutar retiros ni sanciones. La decisión de retiro se registra expresamente por jefatura y puede permanecer pendiente.

El cuerpo del marcador representa licencia; el distintivo interior, la etapa del aviso. La X corresponde a declaración del propietario.

## Activar sobre la instalación existente

1. Respaldar base y adjuntos y comprobar restauración. Ensayar las migraciones sobre una copia del motor real, especialmente PostgreSQL.
2. Instalar composer.lock usando PHP 8.5 y el driver de la base.
3. Con la configuración propia del servidor, aplicar las cinco migraciones nuevas: php artisan migrate --force.
4. Servir public/. Los archivos compilados están en public/build; no hay una aplicación separada.
5. Conservar una cuenta ADMIN o JEFE_UNIDAD y asignar perfiles en /perfiles. Los roles vacíos mantienen consulta, sin permisos de escritura.
6. Para crear una cuenta web, el comando existente acepta php artisan create:user --role=JEFE_UNIDAD y solicita nombre, usuario y contraseña. No se crearon cuentas de producción durante este trabajo.
7. Configurar el programador estándar de Laravel para ejecutar schedule:run cada minuto. Registra vencimientos una sola vez y limpia temporales vencidos. El estado rojo también se calcula al consultar, sin depender del programador.
8. Servir por HTTPS, mantener APP_DEBUG=false y comprobar cámara, GPS y guardado desde un teléfono real.

No se intervino el servidor ni una base real y no se creó .env. Se preparó una copia local separada y se ensayó también en PostgreSQL 18 aislado. Los escenarios son ficticios y los registros existentes no se vinculan automáticamente. Para actualizar el servidor, seguir produccion.md.

## Fotografías y recuperación

Los temporales vencen a las 24 horas. La limpieza elimina archivos abandonados y conserva adjuntos definitivos. Los archivos privados se guardan en storage/app/caseta-photos; incluirlos en el respaldo junto con la base. Las fotos históricas conservan sus ubicaciones previas.

Si se vuelve a una versión anterior después de capturar datos nuevos, conservar tablas y adjuntos; no usar una reversión para eliminar trabajo de campo. La ampliación numérica de coordenadas se conserva al revertir su índice para no truncar datos.

## Verificación

- 98 pruebas PHP y 819 comprobaciones pasan con PHP 8.5.12, además de 4 pruebas JavaScript de GPS. Incluyen alcance activo, bloqueo de módulos aplazados y conservación de datos. Las pruebas del código en reserva habilitan sus funciones explícitamente; la configuración normal permanece desactivada.
- Compilación de producción con Vite 8.3.5 y laravel-vite-plugin 3.2.0.
- Edge aislado: alta, ficha, mapa, formulario móvil, GPS simulado, carga previa de foto y guardado. Parqueos: vehículos, motos, perímetro local, ubicación pública y alta/edición móvil, sin peticiones a Google Maps.
- Sin errores JavaScript en esos recorridos; formulario a 390 px sin desbordamiento horizontal.
- Límites temporales, cambio de gestión entre UTC/Bolivia, permisos, privacidad, historial y conservación de avisos históricos al revertir las migraciones nuevas.

El ensayo anterior en PostgreSQL 18 cubrió casetas, parqueos y conservación de registros. El cambio de alcance se valida adicionalmente comprobando el bloqueo de rutas y la conservación de datos con las funciones desactivadas. Esto no sustituye el ensayo del respaldo actual del servidor ni la medición de precisión con los teléfonos reales.

## Pendientes externos

Recaudaciones queda fuera del alcance actual. Su código y sus datos anteriores se conservan en reserva, sin consultas ni importaciones activas. Consultar recaudaciones.md únicamente como referencia del módulo aplazado.

El expediente e historial del punto 7, las versiones de certificados y la bandeja de documentos históricos están aplazados. No se fusionan ni eliminan documentos existentes.

Se modernizó el tema compartido y la navegación de los módulos heredados. El trabajo completamente sin conexión y la conexión con GNSS externo no están implementados. Consultar mapas-y-gps.md para las alternativas y el piloto.

## Entorno local de ensayo

Se conserva intacta database/certtrafico-local.sqlite. La copia migrada está en storage/framework/testing/entorno-pruebas-20261023.sqlite, excluida de Git. La base original contenía una cuenta y ningún certificado ni aviso; la copia conserva esa cuenta y añade cuentas y escenarios identificados como PRUEBA. No se deben transferir esos datos al servidor.

El entorno iniciado en esta sesión escucha únicamente en http://127.0.0.1:8015 y recibe la configuración mediante variables del proceso, sin un .env en el proyecto. Su clave temporal y la referencia del proceso están en el directorio de pruebas ignorado. La contraseña de las cuentas de ensayo se entrega en el chat, no en el código.

Para revisar el resultado: casetas PRUEBA-001 a PRUEBA-007, licencias ficticias, avisos en distintas etapas, certificados y documentos sin vincular. Los tres perfiles de ensayo son pruebas.jefatura, pruebas.inspector y pruebas.legal. El servidor de desarrollo funciona mientras siga activo su proceso y no constituye un servicio de producción.
## Revisión de interfaz

Se verificaron 16 pantallas del menú a 1440 px y 390 px: inicio, mapas, casetas, certificaciones, notificaciones, parqueos, denuncias, avisos, sindicatos, inspectores, reportes y perfiles, incluidos los formularios principales. Se corrigieron tablas con desbordamiento horizontal, el enlace Nuevo aviso que abría otro módulo y las acciones de edición visibles para perfiles de consulta.

La portada conserva el aspecto del tema original y el mapa actualizado de vehículos, motocicletas y perímetro, la ubicación con precisión visible y la atribución cartográfica. El enlace de restricción abre esa capa directamente y Actualizar mapa conserva la capa seleccionada. Se retiraron las referencias a Popayán, el formulario de contacto de demostración y enlaces sin destino. No se modificaron los datos, los formatos oficiales de PDF ni las funciones aplazadas del punto 7 y Recaudaciones.
