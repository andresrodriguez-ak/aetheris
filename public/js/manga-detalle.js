/* ═══════════════════════════════════════════════════════════════
   Aetheris — manga-detalle.js
   JS de la página de detalle: acciones (favorito/seguir/ver más tarde) y capítulos leídos. Usa MANGA_ID y TOTAL_CAPS inyectados por PHP.
   ═══════════════════════════════════════════════════════════════ */

const btnConfig = {
    btnFav: {
        activeClass: 'active-fav',
        iconOn:  'uploads/content/icon_fav_on.png',
        iconOff: 'uploads/content/icon_fav_off.png',
        labelOn: 'En favoritos', labelOff: 'Favorito',
        labelId: 'lblFav', iconId: 'iconFav',
        independiente: true
    },
    btnSig: {
        activeClass: 'active-sig',
        iconOn:  'uploads/content/icon_sig_on.png',
        iconOff: 'uploads/content/icon_sig_off.png',
        labelOn: 'Leyendo', labelOff: 'Seguir',
        labelId: 'lblSig', iconId: 'iconSig',
        independiente: false
    },
    btnVmt: {
        activeClass: 'active-vmt',
        iconOn:  'uploads/content/icon_vmt_on.png',
        iconOff: 'uploads/content/icon_vmt_off.png',
        labelOn: 'Guardado', labelOff: 'Ver más tarde',
        labelId: 'lblVmt', iconId: 'iconVmt',
        independiente: false
    }
};

async function toggleAccion(action, btnId) {
    const btn = document.getElementById(btnId);
    if (!btn) return;
    btn.disabled = true;
    const cfg = btnConfig[btnId];

    try {
        const resp = await fetch(window.MANGA_DETALLE_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=${action}&manga_id=${window.MANGA_ID}`
        });
        const data = await resp.json();

        if (data.success) {

            if (!cfg.independiente) {
                Object.entries(btnConfig).forEach(([id, c]) => {
                    if (!c.independiente && id !== btnId) {
                        const b = document.getElementById(id);
                        if (!b) return;
                        b.classList.remove(c.activeClass);
                        document.getElementById(c.iconId).src = c.iconOff;
                        document.getElementById(c.labelId).textContent = c.labelOff;
                    }
                });
            }

            if (data.active) {
                btn.classList.add(cfg.activeClass);
                document.getElementById(cfg.iconId).src = cfg.iconOn;
                document.getElementById(cfg.labelId).textContent = cfg.labelOn;
            } else {
                btn.classList.remove(cfg.activeClass);
                document.getElementById(cfg.iconId).src = cfg.iconOff;
                document.getElementById(cfg.labelId).textContent = cfg.labelOff;
            }
        } else if (data.msg === 'no_login') {
            window.location.href = 'login.php';
        }
    } catch (err) {
        console.error('Error en toggleAccion:', err);
    }

    btn.disabled = false;
}

function actualizarProgreso(delta) {
    window.LEIDOS_COUNT = Math.max(0, Math.min(window.TOTAL_CAPS, (window.LEIDOS_COUNT || 0) + delta));
    const pct = window.TOTAL_CAPS > 0 ? Math.round((window.LEIDOS_COUNT / window.TOTAL_CAPS) * 100) : 0;
    const bar = document.getElementById('progressBar');
    const txt = document.getElementById('progressText');
    if (bar) bar.style.width = pct + '%';
    if (txt) txt.textContent = `${window.LEIDOS_COUNT} / ${window.TOTAL_CAPS} capítulos (${pct}%)`;
}

async function toggleLeido(checkbox, capId) {
    checkbox.disabled = true;
    const card = document.getElementById('cap-card-' + capId);

    try {
        const resp = await fetch(window.MANGA_DETALLE_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=toggle_leido&manga_id=${window.MANGA_ID}&capitulo_id=${capId}`
        });
        const data = await resp.json();

        if (data.success) {
            if (data.leido) {
                card && card.classList.add('leido-card');
                checkbox.checked = true;
                actualizarProgreso(+1);
            } else {
                card && card.classList.remove('leido-card');
                checkbox.checked = false;
                actualizarProgreso(-1);
            }
        } else {
            checkbox.checked = !checkbox.checked;
        }
    } catch (err) {
        checkbox.checked = !checkbox.checked;
        console.error('Error en toggleLeido:', err);
    }

    checkbox.disabled = false;
}