import '../css/institutional.css';

const ready = () => {
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && document.body.classList.contains('sidebar-open')) {
            const toggle = document.querySelector('[data-widget=pushmenu]');
            toggle?.click();
            toggle?.focus();
        }
    });
    const search = document.querySelector('[data-menu-search]');
    if (search) {
        const normalize = value => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
        search.addEventListener('input', () => {
            const query = normalize(search.value.trim());
            let found = false;
            document.querySelectorAll('[data-menu-section]').forEach(section => {
                let visible = false;
                section.querySelectorAll('[data-menu-item]').forEach(item => {
                    item.hidden = !normalize(item.textContent).includes(query);
                    visible ||= !item.hidden;
                });
                section.hidden = !visible;
                found ||= visible;
            });
            document.querySelector('[data-menu-empty]').hidden = found;
        });
    }

    const navigation = document.querySelector('[data-public-navigation]');
    if (navigation) {
        const toggle = navigation.querySelector('[data-public-toggle]');
        const links = navigation.querySelector('[data-public-links]');
        navigation.dataset.enhanced = '1';
        const setOpen = open => {
            toggle.setAttribute('aria-expanded', String(open));
            links.toggleAttribute('data-open', open);
        };
        toggle.addEventListener('click', () => setOpen(toggle.getAttribute('aria-expanded') !== 'true'));
        links.querySelectorAll('a').forEach(link => link.addEventListener('click', () => setOpen(false)));
        navigation.addEventListener('keydown', event => {
            if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') { setOpen(false); toggle.focus(); }
        });
    }

    document.querySelectorAll('[data-password-toggle]').forEach(button => {
        const input = document.getElementById(button.getAttribute('aria-controls'));
        button.addEventListener('click', () => {
            const visible = input.type === 'password';
            input.type = visible ? 'text' : 'password';
            button.setAttribute('aria-pressed', String(visible));
            const label = button.querySelector('[data-password-label]');
            if (label) label.textContent = visible ? 'Ocultar contraseña' : 'Mostrar contraseña';
            else button.textContent = visible ? 'Ocultar' : 'Mostrar';
            const icon = button.querySelector('[data-password-icon]');
            icon?.classList.toggle('fa-eye', !visible);
            icon?.classList.toggle('fa-eye-slash', visible);
        });
    });

    if (window.jQuery) {
        window.jQuery(document).on('shown.lte.pushmenu collapsed.lte.pushmenu', () => {
            const open = window.innerWidth < 992 ? document.body.classList.contains('sidebar-open') : !document.body.classList.contains('sidebar-collapse');
            document.querySelector('[data-widget=pushmenu]')?.setAttribute('aria-expanded', String(open));
        });
        document.querySelector('[data-widget=pushmenu]')?.setAttribute('aria-expanded', String(window.innerWidth >= 992));
    }
};

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ready); else ready();
