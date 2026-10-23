import {createMap as makeMap, captureBestPosition, locationError} from './map-support.js';
(() => {
    'use strict';
    const ready = () => {
        const cfg = window.TraficoMaps || {};
        document.querySelectorAll('[data-location]').forEach(root => {
            const lat = root.querySelector('[data-lat]'), lng = root.querySelector('[data-lng]');
            const map = makeMap(root.querySelector('[data-location-map]'));
            const readonly = root.dataset.readonly === '1';
            const source = root.querySelector('[data-source]'), accuracy = root.querySelector('[data-accuracy]');
            const located = root.querySelector('[data-located-at]'), confirmed = root.querySelector('[data-confirmed]');
            const status = root.querySelector('[data-location-status]'), reason = root.querySelector('[data-location-reason]');
            let marker, circle, watch = null;
            const valid = () => lat.value !== '' && lng.value !== '' && Number.isFinite(+lat.value) && Number.isFinite(+lng.value) && Math.abs(+lat.value) <= 90 && Math.abs(+lng.value) <= 180;
            const draw = (center = false) => {
                if (!map || !valid()) return;
                const position = [+lat.value, +lng.value];
                if (!marker) marker = L.circleMarker(position, {radius: 8, color: '#175d95', fillOpacity: 1}).addTo(map);
                else marker.setLatLng(position);
                if (circle) { map.removeLayer(circle); circle = null; }
                if (accuracy && +accuracy.value > 0) circle = L.circle(position, {radius: +accuracy.value, color: '#175d95', weight: 1, fillOpacity: .1}).addTo(map);
                if (center) map.setView(position, 18);
            };
            const stop = () => { if (watch !== null) watch(); watch = null; };
            draw(true);
            if (readonly) return;
            const manual = center => {
                stop(); source.value = 'MANUAL'; accuracy.value = ''; located.value = ''; confirmed.value = '0';
                status.textContent = 'Punto manual. Indique el motivo y confirme la ubicación.'; draw(center);
            };
            lat.addEventListener('input', () => manual(false));
            lng.addEventListener('input', () => manual(false));
            if (map) map.on('click', e => { lat.value = e.latlng.lat.toFixed(7); lng.value = e.latlng.lng.toFixed(7); manual(false); });
            root.querySelector('[data-gps]').addEventListener('click', () => {
                stop(); confirmed.value = '0'; source.value = ''; accuracy.value = ''; located.value = ''; draw(false);
                status.textContent = 'Buscando una lectura GPS reciente… Mantenga el teléfono frente al punto que desea registrar.';
                watch = captureBestPosition({
                    onPosition: position => {
                        source.value = 'GPS';
                        lat.value = position.coords.latitude.toFixed(7); lng.value = position.coords.longitude.toFixed(7);
                        accuracy.value = position.coords.accuracy; located.value = new Date(position.timestamp).toISOString(); confirmed.value = '0';
                        const warning = position.coords.accuracy > (cfg.accuracy_warning_meters || 25);
                        status.textContent = 'Precisión estimada: ±' + Math.round(position.coords.accuracy) + ' m. ' + (warning ? 'Precisión baja: reintente o corrija el punto con un motivo.' : 'Verifique el punto y confirme la ubicación.');
                        draw(true);
                    },
                    onError: (error, reading) => { status.textContent = locationError(error) + (reading ? ' Se conserva la mejor lectura; confirme o reintente.' : ' Puede seleccionar un punto manual con su motivo.'); },
                    onComplete: () => { if (confirmed.value !== '1') status.textContent += ' Captura finalizada; confirme el punto o reintente.'; }
                });
            });
            root.querySelector('[data-confirm-location]').addEventListener('click', () => {
                if (!valid()) { status.textContent = 'Ingrese coordenadas válidas o capture el GPS.'; return; }
                if (!source.value) source.value = 'MANUAL';
                if (source.value === 'MANUAL' && !reason.value.trim()) { status.textContent = 'Indique el motivo de la ubicación manual.'; reason.focus(); return; }
                if (source.value === 'GPS' && (!located.value || Date.now() - Date.parse(located.value) > 600000)) { status.textContent = 'La lectura GPS es antigua. Capture una ubicación nueva.'; return; }
                stop(); confirmed.value = '1'; status.textContent = 'Ubicación confirmada · ' + (source.value === 'GPS' ? 'GPS ±' + Math.round(+accuracy.value) + ' m' : source.value === 'EXISTING' ? 'Ubicación registrada' : 'Punto manual');
            });
            root.closest('form')?.addEventListener('submit', e => {
                if (confirmed.value !== '1') { e.preventDefault(); status.textContent = 'Confirme la ubicación antes de guardar.'; root.scrollIntoView({block: 'center'}); }
            });
            window.addEventListener('pagehide', stop);
        });

        document.querySelectorAll('[data-photo-upload]').forEach(root => {
            const input = root.querySelector('input[type=file]'), token = root.querySelector('[name=photo_id]');
            const preview = root.querySelector('img'), status = root.querySelector('[role=status]'), retry = root.querySelector('[data-photo-retry]');
            let generation = 0, pending = false, objectUrl = null;
            const upload = async () => {
                const file = input.files[0]; if (!file) return;
                const current = ++generation; token.value = ''; pending = true; retry.hidden = true;
                if (objectUrl) URL.revokeObjectURL(objectUrl);
                objectUrl = URL.createObjectURL(file); preview.src = objectUrl; preview.hidden = false;
                if (file.size > 10 * 1024 * 1024) { pending = false; status.textContent = 'La imagen supera 10 MB. Seleccione una más pequeña.'; return; }
                status.textContent = 'Cargando fotografía al servidor…';
                try {
                    const body = new FormData(); body.append('file', file);
                    const result = await fetch(root.dataset.uploadUrl, {method: 'POST', body, credentials: 'same-origin', headers: {'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json'}});
                    const data = await result.json();
                    if (!result.ok) throw new Error(data.errors?.file?.[0] || data.message || 'No se pudo cargar la fotografía.');
                    if (current !== generation) return;
                    token.value = data.id; status.textContent = 'Fotografía almacenada. Ya puede guardar el registro.';
                } catch (e) {
                    if (current !== generation) return;
                    status.textContent = e.message + ' Conserve esta pantalla y reintente la carga.'; retry.hidden = false;
                } finally { if (current === generation) pending = false; }
            };
            input.addEventListener('change', upload); retry.addEventListener('click', upload);
            preview.addEventListener('error', () => { if (!objectUrl) { token.value = ''; status.textContent = 'La fotografía temporal ha vencido. Selecciónela otra vez.'; } });
            root.closest('form').addEventListener('submit', e => {
                if (pending || !token.value) { e.preventDefault(); status.textContent = pending ? 'Espere a que termine la carga de la fotografía.' : 'Cargue una fotografía antes de guardar.'; }
                else if (!e.defaultPrevented) input.disabled = true;
            });
            window.addEventListener('pagehide', () => { if (objectUrl) URL.revokeObjectURL(objectUrl); });
        });

        document.querySelectorAll('[data-caseta-map-panel]').forEach(panel => {
            const map = makeMap(panel.querySelector('[data-caseta-map]')); if (!map) return;
            const status = panel.querySelector('[data-map-status]'), filters = panel.querySelector('form');
            const markers = L.layerGroup().addTo(map); let aborter, timer;
            const load = async () => {
                aborter?.abort(); aborter = new AbortController();
                const params = new URLSearchParams(new FormData(filters)), bounds = map.getBounds();
                params.set('south', Math.max(-90, bounds.getSouth())); params.set('north', Math.min(90, bounds.getNorth()));
                params.set('west', Math.max(-180, bounds.getWest())); params.set('east', Math.min(180, bounds.getEast()));
                try {
                    const res = await fetch(panel.dataset.pointsUrl + '?' + params, {credentials: 'same-origin', headers: {'Accept': 'application/json'}, signal: aborter.signal});
                    if (!res.ok) throw new Error('No se pudieron consultar las casetas.');
                    const data = await res.json(); markers.clearLayers();
                    data.points.forEach(point => {
                        const pin = document.createElement('div'); pin.className = 'caseta-pin state-' + point.license_color;
                        const badge = document.createElement('span'); badge.className = 'state-badge state-' + point.control.color; badge.textContent = point.control.sequence || '·'; pin.append(badge);
                        if (point.declared_no_license) { const x = document.createElement('span'); x.className = 'caseta-x'; x.textContent = 'X'; pin.append(x); }
                        const pop = document.createElement('div');
                        [ 'Caseta ' + point.number, point.owner, point.address, point.license, point.control.label, point.notice_count + ' notificaciones registradas', point.last_notice_at ? 'Último registro: ' + point.last_notice_at : 'Sin notificaciones registradas', point.deadline_at ? 'Vencimiento: ' + new Intl.DateTimeFormat('es-BO', {timeZone: 'America/La_Paz', dateStyle: 'short', timeStyle: 'short'}).format(new Date(point.deadline_at)) : '', point.observations || '' ].forEach(value => { const p = document.createElement('p'); p.textContent = value; pop.append(p); });
                        const link = document.createElement('a'); link.href = point.url; link.textContent = 'Ver caseta'; pop.append(link);
                        L.marker([point.lat, point.lng], {icon: L.divIcon({html: pin, className: '', iconSize: [38, 38], iconAnchor: [19, 38]}), title: 'Caseta ' + point.number + ' · ' + point.license + ' · ' + point.control.label}).bindPopup(pop).addTo(markers);
                    });
                    (data.legacy_points || []).forEach(point => {
                        const pop = document.createElement('div');
                        [point.label, point.owner || '', point.address || '', 'Documento histórico pendiente de vincular. Ubicación y licencia por verificar.'].forEach(value => {
                            const p = document.createElement('p'); p.textContent = value; pop.append(p);
                        });
                        const link = document.createElement('a'); link.href = point.url; link.textContent = 'Revisar documento original'; pop.append(link);
                        L.circleMarker([point.lat, point.lng], {radius: 7, color: '#4b5563', fillColor: '#d1d5db', fillOpacity: .8, dashArray: '3 3'})
                            .bindPopup(pop).addTo(markers);
                    });
                    status.textContent = data.points.length + ' casetas y ' + (data.legacy_points || []).length + ' documentos históricos pendientes en esta zona.' + (data.truncated ? ' Acerque el mapa para ver las restantes.' : '') + ' Actualización automática cada minuto.';
                } catch (e) { if (e.name !== 'AbortError') status.textContent = 'No se pudo actualizar el mapa. Los puntos visibles corresponden a la última consulta; reintente.'; }
            };
            filters.addEventListener('submit', e => { e.preventDefault(); load(); });
            map.on('moveend', () => { clearTimeout(timer); timer = setTimeout(load, 300); });
            load(); const interval = setInterval(load, 60000);
            window.addEventListener('pagehide', () => { clearInterval(interval); clearTimeout(timer); aborter?.abort(); });
        });
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ready); else ready();
})();
