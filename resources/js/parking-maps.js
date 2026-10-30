import {createMap, validCoordinates, captureBestPosition, locationError} from './map-support.js';

const ready = () => {
    document.querySelectorAll('[data-parking-map]').forEach(root => {
        const canvas = root.querySelector('[data-parking-canvas], [data-location-map]');
        const map = createMap(canvas, 15);
        if (!map) return;
        const status = root.querySelector('[data-parking-status]');
        let markers = L.layerGroup().addTo(map), request, gpsStop, userMarker, userCircle;
        const icons = {car: root.dataset.carIcon || root.dataset.iconUrl, moto: root.dataset.motoIcon || root.dataset.iconUrl};
        let generation = 0;
        const showPoints = async (url, kind = 'car', title = '') => {
            const current = ++generation;
            request?.abort(); request = new AbortController();
            status.textContent = 'Consultando puntos de parqueo…';
            try {
                const response = await fetch(url, {credentials: 'same-origin', headers: {'Accept': 'application/json'}, signal: request.signal});
                if (!response.ok) throw new Error();
                const rows = await response.json();
                if (current !== generation) return;
                if (!Array.isArray(rows)) throw new Error();
                const next = L.featureGroup(); let skipped = 0;
                rows.forEach(point => {
                    if (root.dataset.excludeId && String(point.id) === root.dataset.excludeId) return;
                    if (!validCoordinates(point.lat, point.lng)) { skipped++; return; }
                    const popup = document.createElement('div');
                    [point.name, point.description, 'Zona: ' + (point.zone || 'Sin registrar'),
                        'Espacios: ' + (point.quantity ?? 'Sin registrar'), 'Libres: ' + (point.free ?? 'Sin información')].forEach(value => {
                        const p = document.createElement('p'); p.textContent = value; popup.append(p);
                    });
                    const options = {title: 'Parqueo ' + point.name};
                    if (icons[kind]) options.icon = L.icon({iconUrl: icons[kind], iconSize: [32, 32], iconAnchor: [16, 32]});
                    L.marker([Number(point.lat), Number(point.lng)], options).bindPopup(popup).addTo(next);
                });
                map.removeLayer(markers); markers = next.addTo(map);
                if (root.dataset.fitBounds === '1' && next.getLayers().length && !userMarker) map.fitBounds(next.getBounds(), {padding:[32,32], maxZoom:17});
                status.textContent = next.getLayers().length + ' puntos de parqueo.' + (skipped ? ' Hay ubicaciones históricas inválidas pendientes de revisión.' : '');
                if (title) root.querySelector('[data-parking-title]').textContent = title;
                root.querySelector('[data-perimeter]')?.setAttribute('aria-pressed', 'false');
                root.querySelectorAll('[data-parking-kind]').forEach(button => button.setAttribute('aria-pressed', String(button.dataset.parkingKind === kind)));
            } catch (error) {
                if (error.name !== 'AbortError' && current === generation) status.textContent = 'No se pudieron actualizar los puntos. Se conserva la última consulta; reintente.';
            }
        };
        root.querySelectorAll('[data-parking-kind]').forEach(button => button.addEventListener('click', () => showPoints(button.dataset.url, button.dataset.parkingKind, button.dataset.title)));
        root.querySelector('[data-parking-retry]')?.addEventListener('click', () => {
            const perimeter = root.querySelector('[data-perimeter]');
            if (perimeter?.getAttribute('aria-pressed') === 'true') { perimeter.click(); return; }
            const selected = root.querySelector('[data-parking-kind][aria-pressed=true]') || root.querySelector('[data-parking-kind]');
            showPoints(selected?.dataset.url || root.dataset.pointsUrl, selected?.dataset.parkingKind || root.dataset.kind, selected?.dataset.title || '');
        });
        root.querySelector('[data-user-location]')?.addEventListener('click', () => {
            gpsStop?.(); status.textContent = 'Buscando una ubicación reciente…';
            gpsStop = captureBestPosition({
                onPosition: position => {
                    const xy = [position.coords.latitude, position.coords.longitude];
                    if (!userMarker) userMarker = L.circleMarker(xy, {radius:7, color:'#175d95', fillOpacity:1}).bindTooltip('Su ubicación').addTo(map);
                    else userMarker.setLatLng(xy);
                    if (userCircle) map.removeLayer(userCircle);
                    userCircle = L.circle(xy, {radius:position.coords.accuracy, color:'#175d95', weight:1, fillOpacity:.1}).addTo(map);
                    map.setView(xy, 17);
                    status.textContent = 'Su ubicación · precisión estimada ±' + Math.round(position.coords.accuracy) + ' m.';
                },
                onError: (error, best) => { status.textContent = locationError(error) + (best ? ' Se conserva la mejor lectura obtenida.' : ' Puede seguir consultando el mapa.'); }
            });
        });
        root.querySelector('[data-perimeter]')?.addEventListener('click', async buttonEvent => {
            const current = ++generation;
            request?.abort(); request = new AbortController();
            status.textContent = 'Cargando perímetro de restricción…';
            try {
                const response = await fetch(buttonEvent.currentTarget.dataset.url, {signal:request.signal});
                if (!response.ok) throw new Error();
                const geojson = await response.json();
                if (current !== generation) return;
                const next = L.geoJSON(geojson, {
                    style: {color:'#bd343b', weight:3, fillOpacity:.12},
                    onEachFeature: (feature, layer) => {
                        const title = document.createElement('p');
                        title.textContent = feature.properties?.name || 'Perímetro de restricción vehicular';
                        layer.bindPopup(title);
                    }
                });
                map.removeLayer(markers); markers = next.addTo(map);
                if (next.getLayers().length) map.fitBounds(next.getBounds(), {padding:[24,24]});
                root.querySelector('[data-parking-title]').textContent = 'RESTRICCIÓN VEHICULAR · PERÍMETRO';
                root.querySelectorAll('[data-parking-kind]').forEach(button => button.setAttribute('aria-pressed', 'false'));
                root.querySelector('[data-perimeter]').setAttribute('aria-pressed', 'true');
                status.textContent = 'Perímetro municipal del mapa anterior, conservado en el sistema.';
            } catch (error) {
                if (error.name !== 'AbortError' && current === generation) status.textContent = 'No se pudo cargar el perímetro. Reintente.';
            }
        });
        if (root.dataset.initialLayer === 'perimeter' && root.querySelector('[data-perimeter]')) { root.querySelector('[data-perimeter]').click(); }
        else { showPoints(root.dataset.pointsUrl, root.dataset.kind || 'car'); }
        window.addEventListener('pagehide', () => { request?.abort(); gpsStop?.(); });
    });
};
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ready); else ready();
