import {validCoordinates} from './map-support.js';

const ready = () => document.querySelectorAll('[data-certificate-capture]').forEach(root => {
    const status = root.querySelector('[data-capture-status]');
    const preview = root.querySelector('[data-capture-preview]');
    const element = root.querySelector('[data-capture-map]');
    const button = root.querySelector('[data-capture-retry]');
    let map;
    const capture = async () => {
        button.disabled = true;
        preview.hidden = false;
        status.textContent = 'Preparando el croquis con Leaflet y OpenStreetMap…';
        try {
            if (!window.L || !validCoordinates(root.dataset.lat, root.dataset.lng)) throw new Error('Coordenadas no válidas.');
            map?.remove();
            map = L.map(element, {zoomControl:false, attributionControl:true, dragging:false, zoomAnimation:false, fadeAnimation:false}).setView([Number(root.dataset.lat), Number(root.dataset.lng)], 16);
            const cfg = window.TraficoMaps;
            const tiles = L.tileLayer(cfg.tiles_url, {crossOrigin:'anonymous', attribution:cfg.attribution, maxZoom:cfg.max_zoom || 19});
            await new Promise((resolve,reject) => {
                const timer = setTimeout(() => reject(new Error('Tiempo de carga agotado.')), 15000);
                tiles.once('load', () => {clearTimeout(timer); resolve();});
                tiles.once('tileerror', () => {clearTimeout(timer); reject(new Error('No se pudo cargar la cartografía.'));});
                tiles.addTo(map);
            });
            await new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)));
            const canvas = document.createElement('canvas');
            canvas.width = 640; canvas.height = 400;
            const ctx = canvas.getContext('2d');
            const bounds = element.getBoundingClientRect();
            element.querySelectorAll('.leaflet-tile').forEach(tile => {
                const box = tile.getBoundingClientRect();
                ctx.drawImage(tile, box.left - bounds.left, box.top - bounds.top, box.width, box.height);
            });
            const point = map.latLngToContainerPoint(map.getCenter());
            ctx.fillStyle = '#2c3e50'; ctx.strokeStyle = '#fff'; ctx.lineWidth = 3;
            ctx.beginPath(); ctx.arc(point.x, point.y, 10, 0, Math.PI*2); ctx.fill(); ctx.stroke();
            ctx.fillStyle = '#fff'; ctx.fillRect(0, 378, 640, 22);
            ctx.fillStyle = '#233541'; ctx.font = '12px Arial';
            ctx.fillText('Leaflet | © OpenStreetMap contributors · openstreetmap.org/copyright', 8, 393);
            const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
            if (!blob) throw new Error('No se pudo preparar la imagen.');
            const body = new FormData();
            body.append('image', blob, 'croquis.png'); body.append('version', root.dataset.version);
            const response = await fetch(root.dataset.upload, {method:'POST', credentials:'same-origin', headers:{'X-CSRF-TOKEN':root.dataset.token, Accept:'application/json'}, body});
            if (!response.ok) throw new Error('No se pudo guardar la captura. Recargue la ficha si fue modificada.');
            const result = await response.json();
            root.querySelector('[data-certificate-pdf]').src = result.url + '?croquis=' + Date.now();
            preview.hidden = true;
            status.textContent = 'Croquis de Leaflet y OpenStreetMap incluido en el PDF.';
        } catch (error) {
            status.textContent = error.message + ' Puede reintentar con Actualizar croquis. El PDF conserva las coordenadas.';
        } finally {button.disabled = false;}
    };
    button.addEventListener('click', capture);
    capture();
});
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ready); else ready();
