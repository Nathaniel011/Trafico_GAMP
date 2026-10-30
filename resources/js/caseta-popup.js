const states = new Set(['white', 'neutral', 'yellow', 'blue', 'green', 'red']);
const text = (tag, className, value) => {
    const node = document.createElement(tag);
    node.className = className;
    node.textContent = value;
    return node;
};
const badge = (label, color) => text('span', 'state-badge state-' + (states.has(color) ? color : 'neutral'), label);
const detail = (list, label, value) => {
    list.append(text('dt', '', label), text('dd', '', value || 'Sin registrar'));
};
const date = value => {
    const parsed = new Date(value);
    return Number.isNaN(parsed.getTime()) ? 'Fecha por verificar' :
        new Intl.DateTimeFormat('es-BO', {timeZone:'America/La_Paz', dateStyle:'short', timeStyle:'short'}).format(parsed);
};

export function casetaPopup(point, legacy = false) {
    const card = document.createElement('article');
    card.className = 'caseta-popup';
    const header = text('header', 'caseta-popup-header', '');
    const icon = text('span', 'caseta-popup-icon', '');
    const glyph = document.createElement('i');
    glyph.className = legacy ? 'fas fa-file-alt' : 'fas fa-store';
    glyph.setAttribute('aria-hidden', 'true'); icon.append(glyph);
    const title = document.createElement('div');
    title.append(text('span', 'caseta-popup-eyebrow', legacy ? 'Documento histórico' : 'Control municipal'));
    title.append(text('h3', 'caseta-popup-title', legacy ? point.label : 'Caseta ' + point.number));
    header.append(icon, title); card.append(header);

    const body = text('div', 'caseta-popup-body', '');
    const statuses = text('div', 'caseta-popup-states', '');
    statuses.setAttribute('aria-label', 'Estado de licencia y notificaciones');
    statuses.append(badge(legacy ? 'Por verificar' : point.license, legacy ? 'neutral' : point.license_color));
    if (!legacy) statuses.append(badge(point.control.label, point.control.color));
    body.append(statuses);
    if (point.declared_no_license) body.append(text('p', 'caseta-popup-declaration', 'X · El propietario declara no tener licencia'));
    const details = document.createElement('dl'); details.className = 'caseta-popup-details';
    detail(details, 'Propietario', point.owner);
    detail(details, 'Dirección', point.address);
    if (!legacy) {
        detail(details, 'Notificaciones', String(point.notice_count ?? 0) + ' registradas');
        if (point.last_notice_at) detail(details, 'Último registro', point.last_notice_at);
        if (point.deadline_at) detail(details, 'Vencimiento', date(point.deadline_at));
    }
    body.append(details);
    const note = legacy ? 'Documento pendiente de vincular. Su ubicación y licencia requieren revisión.' : point.observations;
    if (note) {
        const observations = text('div', 'caseta-popup-note', '');
        observations.append(text('strong', '', legacy ? 'Revisión pendiente' : 'Observaciones'), text('p', '', note));
        body.append(observations);
    }
    const action = text('a', 'caseta-popup-action', legacy ? 'Ver documento original' : 'Ver ficha de la caseta');
    const target = new URL(point.url, window.location.href);
    if (target.origin === window.location.origin) action.href = target.href;
    const arrow = document.createElement('i'); arrow.className = 'fas fa-arrow-right'; arrow.setAttribute('aria-hidden','true');
    action.append(arrow); body.append(action); card.append(body);
    return card;
}
