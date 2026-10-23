# Mapas, coordenadas y precisión GPS

Revisión: 23 de octubre de 2026.

## Decisión aplicada

Se incorporó Leaflet 1.9.4, versión estable indicada por su [sitio oficial](https://leafletjs.com/download.html), a todos los mapas interactivos: casetas, notificaciones, certificados, parqueos de vehículos y motos y mapa público. La biblioteca y su licencia se sirven desde el proyecto; los recursos propios se compilan con Vite.

El mapa base inicial usa OpenStreetMap. La URL de teselas y la atribución se configuran en config/maps.php mediante MAP_TILES_URL y MAP_ATTRIBUTION. Los puntos de casetas se consultan en una ruta autenticada de la aplicación. El navegador solicita al proveedor las teselas de la zona visible.

El mapa público usa Leaflet para vehículos, motocicletas y perímetro. El polígono anterior de restricción se exportó y se conserva como GeoJSON local con sus 53 vértices en public/maps/restriccion-vehicular.geojson. Se eliminaron el SDK, las claves insertadas y el iframe de Google Maps. El mapa estático de los PDF conserva Mapbox y su configuración existente, ya que es una imagen generada en el servidor.

## Mapa y precisión son cosas distintas

La [geolocalización del navegador](https://developer.mozilla.org/en-US/docs/Web/API/Geolocation/getCurrentPosition) obtiene la posición del dispositivo; el mapa la dibuja. Solicitar alta precisión no garantiza exactitud ni permite elegir desde esta web un receptor GNSS concreto.

La propiedad [accuracy](https://developer.mozilla.org/en-US/docs/Web/API/GeolocationCoordinates/accuracy) expresa precisión estimada en metros. Debe distinguirse del error real respecto a un punto verificado en terreno. Una dirección geocodificada o una imagen de satélite tampoco demuestra que se haya medido correctamente la caseta.

El código solicita lecturas recientes sin caché, conserva la mejor precisión informada entre las muestras, dibuja su radio y permite reintentar y confirmar. Registra fuente e instante de captura. Una corrección manual exige motivo y no conserva una precisión GPS que ya no corresponde al punto.

GPS_ACCURACY_WARNING_METERS, inicialmente 25 metros, configura una advertencia para el piloto. No es una tolerancia aprobada para distinguir casetas contiguas. Se rechazan valores fuera de rango, lecturas antiguas y registros sin confirmar. El centro visual del mapa no se guarda como una captura. La reutilización de un punto registrado requiere confirmación y comprobación de coincidencia en el servidor.

El GPS en campo necesita HTTPS y permiso del usuario. Las pruebas de navegador usaron localhost y GPS simulado; no miden la precisión física en Potosí. Se verificaron los cambios entre vehículos, motos y perímetro, la ubicación pública y el alta/edición móvil sin peticiones a Google Maps.

## Alternativas estudiadas

### Leaflet y teselas raster

Es la opción aplicada para puntos, círculos y fichas en móvil. Admite cambiar el mapa base por un servicio compatible comercial o municipal sin cambiar la captura GPS.

El servidor público de OpenStreetMap tiene capacidad limitada y disponibilidad sin garantía. Su [política](https://operations.osmfoundation.org/policies/tiles/) exige atribución y caché normal y prohíbe descarga masiva y precarga. No se implementó descarga sin conexión. Para uso institucional sostenido, evaluar volumen y disponibilidad y elegir proveedor o infraestructura adecuados.

### MapLibre GL JS

[MapLibre](https://maplibre.org/projects/gl-js/) renderiza mapas vectoriales mediante WebGL. Es una alternativa para estilos, capas y mayores necesidades cartográficas. Hay que seleccionar una fuente de mapas y comprobar los teléfonos de destino. No mejora la medición GPS; no se añadió una segunda biblioteca sin una necesidad funcional concreta.

### MapTiler

[MapTiler](https://docs.maptiler.com/cloud/api/reference/) ofrece teselas raster compatibles con Leaflet y opciones vectoriales. Sus servicios usan claves y condiciones propias. Permitiría cambiar el proveedor sin rehacer formularios. Se necesitan cuenta, atribución, restricciones de la clave y validación de cobertura y consumo. No se añadió una clave ni contratación.

### Mapbox

[Mapbox GL JS](https://docs.mapbox.com/mapbox-gl-js/guides/) ofrece mapas interactivos y personalización con token de acceso. Tampoco sustituye al receptor. El proyecto conserva Mapbox como proveedor opcional de imágenes estáticas de certificados mediante MAPBOX_ACCESS_TOKEN. Cuando falta o falla el proveedor, el PDF sigue generándose con las coordenadas y la indicación de consultar el mapa interactivo.

### GNSS externo y RTK

Si el teléfono no distingue casetas próximas, evaluar un receptor externo y, cuando corresponda, correcciones RTK. La [documentación de QField](https://docs.qfield.org/how-to/navigation-and-positioning/gnss/) describe receptores NMEA y correcciones NTRIP, con sus requisitos de coordenadas WGS 84.

Es una alternativa de captura, diferente del mapa. Requiere comprobar equipo, aplicación compatible, servicio de correcciones y precisión real. Esta web no implementa conexión Bluetooth/NMEA ni RTK; no se promete precisión centimétrica con el teléfono actual.

## Piloto recomendado

1. Elegir casetas con puntos de referencia verificados por el municipio, incluyendo casetas próximas.
2. Capturar varias lecturas con cada modelo de teléfono.
3. Comparar desplazamiento real, precisión informada y condiciones de captura.
4. Comprobar calles y referencias del mapa base frente al terreno.
5. Definir el umbral operativo y cuándo exigir corrección manual o medición externa.
6. Seleccionar proveedor según cobertura local, disponibilidad, condiciones y volumen.

La implementación permite realizar este piloto guardando precisión y fuente, sin confundir calidad cartográfica con calidad de medición.
