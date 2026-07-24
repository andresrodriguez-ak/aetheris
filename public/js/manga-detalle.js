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