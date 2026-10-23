export function createMap(element, zoom = 16) {
    if (element._traficoMap) return element._traficoMap;
    if (!window.L) {
        element.textContent = 'No se pudo cargar el mapa. Las coordenadas siguen disponibles.';
        return null;
    }
    const cfg = window.TraficoMaps || {};
    const map = L.map(element).setView(cfg.center || [-19.5889181, -65.7539971], zoom);
    L.tileLayer(cfg.tiles_url, {attribution: cfg.attribution, maxZoom: cfg.max_zoom || 19}).addTo(map)
        .on('tileerror', () => element.setAttribute('aria-label', 'Cartografía sin conexión; las coordenadas siguen disponibles'));
    L.control.scale({imperial: false}).addTo(map);
    // Sidebar and responsive layout changes resize the container without a window resize.
    if (typeof ResizeObserver !== 'undefined') {
        let frame;
        const observer = new ResizeObserver(() => {
            cancelAnimationFrame(frame);
            frame = requestAnimationFrame(() => map.invalidateSize({pan: false}));
        });
        observer.observe(element);
        map.on('unload', () => { observer.disconnect(); cancelAnimationFrame(frame); });
    }
    element._traficoMap = map;
    return map;
}

export function validCoordinates(lat, lng) {
    return lat !== null && lng !== null && lat !== '' && lng !== '' &&
        Number.isFinite(Number(lat)) && Number.isFinite(Number(lng)) &&
        Math.abs(Number(lat)) <= 90 && Math.abs(Number(lng)) <= 180;
}

export function captureBestPosition({onPosition, onError, onComplete = () => {}}, duration = 30000) {
    let watch = null, timer = null, stopped = false, best = null;
    const stop = () => {
        stopped = true;
        clearTimeout(timer);
        if (watch !== null) navigator.geolocation.clearWatch(watch);
        watch = null;
    };
    if (!window.isSecureContext || !navigator.geolocation) {
        onError({code: 0, message: !window.isSecureContext ? 'La ubicación requiere HTTPS.' : 'Este dispositivo no ofrece geolocalización.'}, null);
        return stop;
    }
    watch = navigator.geolocation.watchPosition(position => {
        if (stopped || !validCoordinates(position.coords.latitude, position.coords.longitude) ||
            !Number.isFinite(position.coords.accuracy) || position.coords.accuracy <= 0 ||
            !Number.isFinite(position.timestamp) || Date.now() - position.timestamp > 10000 || position.timestamp - Date.now() > 60000) return;
        if (best && position.coords.accuracy > best.coords.accuracy) return;
        best = position;
        onPosition(position);
    }, error => {
        if (stopped) return;
        stop(); onError(error, best);
    }, {enableHighAccuracy: true, maximumAge: 0, timeout: duration});
    timer = setTimeout(() => {
        if (stopped) return;
        stop();
        if (!best) onError({code: 3}, null);
        else onComplete(best);
    }, duration + 1000);
    return stop;
}

export function locationError(error) {
    return error.message && error.code === 0 ? error.message :
        ({1: 'Permiso de ubicación denegado. Habilítelo en el navegador.',
            2: 'Ubicación no disponible. Reintente en el exterior.',
            3: 'Se agotó el tiempo de captura. Reintente.'}[error.code] || 'No se pudo capturar la ubicación.');
}
