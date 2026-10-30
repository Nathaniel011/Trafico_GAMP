# Adaptación de main a develop

Fecha: 30 de octubre de 2026.

Se integraron los módulos y correcciones existentes de certtrafico-main en las carpetas habituales de develop: app, bootstrap, config, database, resources, routes, public y tests. Se conservan los kernels, proveedores, controladores y vistas Blade; no se reemplazó la aplicación por un proyecto Laravel nuevo.

## Versiones y entorno

- PHP 8.5.12; Laravel 13; dependencias fijadas en composer.lock.
- Recursos compilados con Vite en public/build. webpack.mix.js se conserva como referencia anterior; los comandos activos están en package.json.
- Color institucional: rgb(44, 62, 80), equivalente a #2c3e50. Los colores de estado amarillo, azul, verde y rojo mantienen su significado.
- Entorno local independiente: http://127.0.0.1:8001/login. PHP está en .local/php85 y la base SQLite en .local/certtrafico.sqlite. No se incluyen en Git.
- Para iniciar el entorno local preparado desde PowerShell: .\.local\php85\php.exe -d opcache.enable=0 -d opcache.enable_cli=0 -S 127.0.0.1:8001 -t public server.php. OPcache se desactiva en ese comando de desarrollo para evitar el fallo ASLR de Windows; no es una configuración de producción.

## Funciones incorporadas

Casetas independientes, licencias, notificaciones 1/2/3 y vencimiento de 48 horas desde el tercer aviso; búsqueda por número y gestión; perfiles y permisos en servidor; mapas Leaflet/OpenStreetMap, GPS confirmado con precisión y fuente; fotos temporales privadas con previsualización y reintento; mejoras de reportes, sindicatos, denuncias y acceso de inspectores. Se incorporan las tarjetas del mapa y las correcciones del croquis PDF de main.

Recaudaciones y el expediente ampliado siguen desactivados en config/features.php, conforme al alcance final del chat de origen. Sus tablas y datos se conservan.

## Diferencias de develop que se conservan

El certificado mantiene sus etapas separadas:

1. Registro y decisión legal en certificate/create; edición legal en certificate/edit/{id} mientras no exista inspección.
2. Inspección y decisión técnica en certificate/technical/{id}, únicamente si la etapa legal es procedente.
3. Consulta y documentos originales: informe legal, informe técnico, certificado de inspección y QR.

El alta legal no exige una ubicación de campo ni convierte el centro del mapa en una captura GPS. La etapa técnica valida y confirma la ubicación y sincroniza la caseta relacionada. Las correcciones usan control de concurrencia. La edición legal no reescribe una inspección ya completada.

## Datos y actualización

Antes de modificar develop se guardó una copia en .local/before-laravel13-20261030-120257, incluida la base local anterior. Las migraciones nuevas se aplicaron a esa copia local de trabajo. No se modificaron main ni una base de producción.

Para un servidor: usar PHP 8.5.12 y las extensiones declaradas, instalar composer.lock, compilar con npm ci y npm run build, respaldar base y adjuntos, ensayar las migraciones en una copia del motor real y servir public/. Conservar el APP_KEY y la configuración del servidor. No sustituir la base real por la SQLite de pruebas ni usar migrate:fresh. Programar php artisan schedule:run cada minuto. HTTPS es necesario para GPS y cámara fuera de localhost.

Las pruebas automáticas usan bases aisladas. La precisión GPS física y la actualización con datos reales requieren el ensayo correspondiente en el entorno del servidor.

## Verificación realizada

- PHP 8.5.12 y Laravel Framework 13.38.0 comprobados; requisitos de extensiones y composer.json válidos.
- 110 pruebas PHP y 1.036 comprobaciones aprobadas; 4 pruebas JavaScript de GPS aprobadas; compilación Vite correcta.
- Sintaxis PHP y compilación Blade correctas. Ningún archivo original eliminado de app, bootstrap, config, database, resources, routes o tests.
- Login y 15 rutas revisadas en navegador a 1440 y 390 px; color base medido como rgb(44, 62, 80).
- Recorrido de alta legal, edición legal, confirmación de ubicación y etapa técnica completado desde navegador en una base temporal separada (puerto 8002). Los cuatro documentos PDF respondieron correctamente.
- No se ejecutaron pruebas con un respaldo actual de producción ni mediciones GPS físicas en campo.


## Creación de usuarios desde el panel

Solo ADMIN y JEFE_UNIDAD pueden acceder a Perfiles de acceso, crear usuarios o editar sus datos y perfiles. La autorización se comprueba también al enviar una petición directa.

En Perfiles de acceso → Crear usuario se piden nombre, CI, correo y perfil. El sistema genera el usuario a partir del nombre, añade un sufijo si ya existe y crea una contraseña aleatoria de 16 caracteres. Los campos usuario, contraseña y roles enviados fuera del formulario no sustituyen los valores generados ni el perfil validado.

Las credenciales se muestran una sola vez al creador de la cuenta. No se envían por correo. La contraseña se conserva únicamente como hash en users y la respuesta que muestra las credenciales no se almacena en caché. Al editar datos no cambian el usuario ni la contraseña existentes.

La migración 2026_10_30_000001_add_user_identity_fields añade ci y email a users con índices únicos. Ambos permiten NULL para conservar cuentas anteriores; son obligatorios para nuevas altas. La pantalla permite completar los datos de cuentas antiguas. El acceso de inspectores de la API continúa separado.

Verificación de esta ampliación: 115 pruebas PHP y 1.192 comprobaciones aprobadas; compilación Vite correcta. Navegador: alta por Administrador y Jefe de Unidad, bloqueo de Inspector, acceso con las credenciales nuevas y visualización única de contraseña, a 1440 y 390 px. Se utilizó una copia temporal de la base; no se crearon cuentas ficticias en el entorno principal.

## Administrador único y contraseñas

Solo una cuenta web puede tener ADMIN. El servidor comprueba esta regla al crear o editar cuentas y en el comando create:user, dentro de la transacción que guarda el usuario. Los roles heredados con varios valores también se revisan por tokens exactos. No se permite quitar ADMIN de la única cuenta administradora; si una base antigua contiene varias, pueden corregirse las cuentas adicionales sin borrar sus datos.

En Perfiles, Administrador y Jefe de Unidad pueden restablecer la contraseña de otra cuenta. La pantalla muestra a quién afecta. Para cuentas que no sean ADMIN, se genera una clave aleatoria sin pedir contraseña al operador. Si la cuenta afectada es ADMIN, se sigue verificando la contraseña actual del operador. La nueva clave se muestra una sola vez y no se envía por correo. Para cambiar la propia clave se utiliza Mi cuenta → Cambiar mi contraseña (también disponible con el icono de llave de la cabecera).

El cambio personal requiere la contraseña actual, una nueva de al menos 8 caracteres y su confirmación. Se conserva la sesión actual y se renueva su identificador. Se rotan los tokens de recordatorio y el middleware estándar AuthenticateSession obliga a las otras sesiones con la contraseña anterior a volver a ingresar. Los campos de contraseña nunca se vuelven a incluir en los formularios tras un error.

Esta ampliación utiliza los campos roles, password y remember_token existentes; no añade migraciones. Las pantallas conservan #2c3e50. Las cuentas de inspectores de la API siguen separadas.

Verificación final: 121 pruebas PHP y 1.337 comprobaciones aprobadas, compilación Vite y Blade correctas. Navegador con sesiones independientes: confirmación de identidad al restablecer, contraseña mostrada una sola vez, invalidación de sesiones antiguas, cambio personal con sesión conservada, rechazo de la clave anterior y acceso con la nueva. Revisado a 1440 y 390 px sin errores JavaScript ni desbordamientos. Las pruebas de contraseña se ejecutaron exclusivamente en una base temporal separada; las contraseñas reales no se modificaron.


Ajuste del restablecimiento: Administrador y Jefatura restablecen cuentas Inspector, Asesor Legal y Jefe de Unidad sin introducir contraseña. Restablecer ADMIN conserva la verificación del operador; cambiar la contraseña propia sigue requiriendo la actual. La decisión se toma con el rol almacenado, bloqueando el registro durante la operación. Verificado con 12 pruebas de gestión y seguridad de cuentas, 374 comprobaciones.

## Visibilidad por perfil

Se mantienen los perfiles y las consultas existentes. Las acciones visibles usan los mismos permisos que el servidor:

- ADMIN y JEFE_UNIDAD: alta y edición de la etapa legal de certificados, trabajo técnico y administración de cuentas.
- INSPECTOR_NOTIFICADOR: registro de campo, avisos, parqueos, atención de denuncias y etapa técnica de certificados legalmente aprobados. No inicia ni edita la etapa legal.
- ASESOR_LEGAL y cuentas heredadas sin perfil: consulta de los módulos autorizados sin controles de modificación.

En denuncias, los perfiles de consulta ven el estado como texto; no se renderizan botones ni JavaScript para cambiarlo. Las notificaciones muestran los datos como información en lugar de un formulario deshabilitado. En parqueos desaparecen los enlaces y la columna de acciones si el perfil no puede modificar. Los accesos de nueva certificación del inicio, listado y ficha de caseta se limitan al permiso legal; la edición técnica permanece disponible para Inspector.

Los controles administrativos existentes (perfiles, inspectores, avisos, sindicatos, licencias y cierre de procedimientos) conservan sus permisos. Los enlaces de consulta y los documentos PDF se mantienen disponibles para quien puede consultarlos.

Verificación: 125 pruebas PHP y 1.512 comprobaciones aprobadas. Los cuatro perfiles se revisaron en navegador a 1440 y 390 px, comprobando botones cargados por AJAX, formularios, tablas sin errores JavaScript y respuestas 403 en accesos directos no autorizados. Los datos ficticios se usaron exclusivamente en una copia temporal.
