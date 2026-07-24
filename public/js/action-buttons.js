/* ═══════════════════════════════════════════════════════════════
   Aetheris — action-buttons.js
   Lógica de: favorito y dropdown de estado (anime/manga/novela).
   ═══════════════════════════════════════════════════════════════ */

const ESTADOS = [
    { value: 'viendo',      label: 'Viendo',      css: 'c-viendo',
      icon: '<circle cx="12" cy="12" r="3"/><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/>' },
    { value: 'por_ver',     label: 'Por ver',     css: 'c-por_ver',
      icon: '<path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/>' },
    { value: 'completado',  label: 'Completado',  css: 'c-completado',
      icon: '<path d="M20 6 9 17l-5-5"/>' },
    { value: 'pausado',     label: 'Pausado',     css: 'c-pausado',
      icon: '<rect x="6" y="4" width="4" height="16" rx="1"/><rect x="14" y="4" width="4" height="16" rx="1"/>' },
    { value: 'descartado',  label: 'Descartado',  css: 'c-descartado',
      icon: '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>' }
];

function svgIcon(pathData) {
    return '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" ' +
           'stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' + pathData + '</svg>';
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.content-actions').forEach(initActionBox);
});

function initActionBox(box) {
    const tipo = box.dataset.tipo;
    const id = box.dataset.id;
    if (!tipo || !id) return;

    const favBtn = box.querySelector('[data-fav-btn]');
    if (favBtn) {
        favBtn.addEventListener('click', async () => {
            favBtn.disabled = true;
            try {
                const resp = await fetch('../src/actions/ajax/acciones-contenido.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=toggle_favorito&tipo=${tipo}&id=${id}`
                });
                const data = await resp.json();
                if (data.success) {
                    favBtn.classList.toggle('is-active', data.active);
                    const icon = favBtn.querySelector('.fav-icon');
                    icon.setAttribute('fill', data.active ? 'currentColor' : 'none');
                    icon.style.animation = 'none';
                    void icon.offsetWidth;
                    if (data.active) icon.style.animation = '';
                    favBtn.querySelector('.fav-label').textContent = data.active ? 'En favoritos' : 'Favorito';
                } else if (data.msg === 'no_login') {
                    window.location.href = 'login.php';
                }
            } catch (err) {
                console.error('Error al alternar favorito:', err);
            }
            favBtn.disabled = false;
        });
    }

    const dropdown = box.querySelector('[data-estado-dropdown]');
    if (dropdown) initEstadoDropdown(dropdown, tipo, id);
}

function initEstadoDropdown(dropdown, tipo, id) {
    const trigger = dropdown.querySelector('[data-estado-trigger]');
    const label = dropdown.querySelector('[data-estado-label]');
    const menu = dropdown.querySelector('[data-estado-menu]');

    ESTADOS.forEach(e => {
        const opt = document.createElement('div');
        opt.className = 'estado-option';
        opt.dataset.value = e.value;
        opt.innerHTML = `<span class="${e.css}">${svgIcon(e.icon)}</span><span>${e.label}</span>`;
        opt.addEventListener('click', () => seleccionarEstado(dropdown, tipo, id, e));
        menu.appendChild(opt);
    });

    const actual = dropdown.dataset.current;
    const estadoActual = ESTADOS.find(e => e.value === actual);
    if (estadoActual) aplicarEstadoVisual(trigger, label, estadoActual);

    trigger.addEventListener('click', () => {
        dropdown.classList.toggle('open');
    });

    document.addEventListener('click', (e) => {
        if (!dropdown.contains(e.target)) dropdown.classList.remove('open');
    });
}

function aplicarEstadoVisual(trigger, label, estado) {
    ESTADOS.forEach(e => trigger.classList.remove('is-' + e.value));
    trigger.classList.add('has-value', 'is-' + estado.value);
    label.innerHTML = `<span class="${estado.css}">${svgIcon(estado.icon)}</span>${estado.label}`;
}

async function seleccionarEstado(dropdown, tipo, id, estado) {
    dropdown.classList.remove('open');
    const trigger = dropdown.querySelector('[data-estado-trigger]');
    const label = dropdown.querySelector('[data-estado-label]');

    try {
        const resp = await fetch('../src/actions/ajax/acciones-contenido.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=cambiar_estado&tipo=${tipo}&id=${id}&estado=${estado.value}`
        });
        const data = await resp.json();
        if (data.success) {
            aplicarEstadoVisual(trigger, label, estado);
        } else if (data.msg === 'no_login') {
            window.location.href = 'login.php';
        }
    } catch (err) {
        console.error('Error al cambiar estado:', err);
    }
}