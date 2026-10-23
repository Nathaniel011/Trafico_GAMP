# Recaudaciones — módulo aplazado

Estado vigente: desactivado por decisión del usuario, junto con el punto 7. No hay menús, consultas, verificaciones manuales ni importaciones disponibles. Se conservan los datos previos. El resto de este documento describe únicamente el código en reserva para una posible reanudación, no funciones activas.

La aplicación original no contiene una API ni una correspondencia documentada con otra base de Recaudaciones. Se incorporó una consulta de verificaciones documentadas y una importación revisada por archivo. La conexión automática externa queda pendiente del formato o acceso real; el sistema no simula una conexión existente.

## Consulta

Abrir /recaudaciones o acceder desde la ficha de una caseta. Se puede filtrar por número exacto de caseta o licencia, gestión y estado de pago. La consulta está disponible para los perfiles autenticados; solo ADMIN y JEFE_UNIDAD registran o importan verificaciones.

Cada licencia conserva su propio historial de pagos, separado de la vigencia por fechas. El estado actual corresponde a la verificación más reciente según la fecha de consulta, no al orden en que se importaron los archivos. En igualdad de fecha, prevalece la última verificación registrada. Una corrección se registra como una nueva verificación con su referencia y explicación; el historial anterior se conserva.

Estados admitidos:

- PAID: pagado, según el respaldo consultado.
- PENDING: pago pendiente, según el respaldo consultado.
- EXEMPT: exento, según el respaldo consultado.
- UNVERIFIED: por verificar. No equivale a pendiente de pago.

La fuente, la referencia del respaldo y la fecha de consulta son obligatorias. La fecha se introduce en hora de Bolivia y se almacena en UTC. No se aceptan verificaciones futuras. Un fallo de importación no cambia los estados existentes.

## Importación CSV

Jefatura descarga la plantilla desde la pantalla, adapta una exportación autorizada y carga el archivo para validar y revisar. Después confirma la vista previa, que vence en 15 minutos y pertenece a su sesión. La confirmación utiliza los datos ya validados del servidor y vuelve a comprobar las referencias; modificar los datos enviados por el navegador no cambia el contenido de la vista previa.

El archivo debe estar en UTF-8, separado por comas, con un máximo de 1000 filas y 2 MB. Las columnas, en este orden, son:

    caseta,licencia,gestion,estado,fuente,referencia,verificado_el,observaciones

Ejemplo ficticio, que solo sirve si existen previamente esa caseta y esa licencia:

    PRUEBA-001,PRUEBA-L001,2026,PAID,Exportación autorizada,PRUEBA-REC-001,2026-10-22 10:00:00,Comprobante de prueba

El número de caseta y licencia se conserva como texto, incluidos sus ceros iniciales. La gestión identifica la licencia, no la fecha de importación. La fecha verificado_el tiene formato AAAA-MM-DD HH:MM:SS y representa el momento en que se consultó la fuente. Las observaciones son opcionales. Si un campo contiene comas, usar las comillas dobles habituales de CSV.

Se exige una coincidencia exacta de número de caseta, número de licencia y gestión. Las casetas y licencias desconocidas deben revisarse y registrarse antes; la importación no las crea ni deduce equivalencias por CI o cercanía. La vigencia y las fechas de una licencia no se modifican al importar pagos.

Todas las filas se incorporan en una transacción. Las verificaciones idénticas se registran una sola vez, incluso al volver a importar el mismo archivo. Las verificaciones antiguas quedan en el historial y no sustituyen una más reciente. El historial de la caseta registra el operador, la fuente, la referencia y la fecha de cada actuación.

Para conectar el sistema externo se necesitan sus identificadores, formato o documentación, permisos de consulta y significado de sus estados. El importador ya define un contrato explícito para recibir una exportación, pero no acredita la autenticidad del archivo: jefatura debe revisar su origen y respaldo.
