/* ═══════════════════════════════════════════════════════════════
   Aetheris — anime-detalle.js
   JS de episodios vistos / progreso. Usa ANIME_ID y TOTAL_EPS
   inyectados por PHP. El favorito y el estado de seguimiento ahora
   viven en action-buttons.js (componente compartido anime/manga/novela).
   ═══════════════════════════════════════════════════════════════ */

function actualizarProgreso(delta) {
    window.VISTOS_COUNT = Math.max(0, Math.min(window.TOTAL_EPS, (window.VISTOS_COUNT || 0) + delta));
    const pct = window.TOTAL_EPS > 0 ? Math.round((window.VISTOS_COUNT / window.TOTAL_EPS) * 100) : 0;
    const bar = document.getElementById('progressBar');
    const txt = document.getElementById('progressText');
    if (bar) bar.style.width = pct + '%';
    if (txt) txt.textContent = `${window.VISTOS_COUNT} / ${window.TOTAL_EPS} episodios (${pct}%)`;
}

async function toggleVisto(checkbox, numEp) {
    checkbox.disabled = true;
    const card = document.getElementById('ep-card-' + numEp);

    try {
        const resp = await fetch(window.ANIME_DETALLE_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=toggle_visto&anime_id=${window.ANIME_ID}&numero_episodio=${numEp}`
        });
        const data = await resp.json();

        if (data.success) {
            if (data.visto) {
                card && card.classList.add('visto-card');
                checkbox.checked = true;
                actualizarProgreso(+1);
            } else {
                card && card.classList.remove('visto-card');
                checkbox.checked = false;
                actualizarProgreso(-1);
            }
        } else {
            checkbox.checked = !checkbox.checked;
        }
    } catch (err) {
        checkbox.checked = !checkbox.checked;
        console.error('Error en toggleVisto:', err);
    }

    checkbox.disabled = false;
}