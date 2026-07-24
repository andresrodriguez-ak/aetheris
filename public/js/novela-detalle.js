function actualizarProgreso(delta) {
    window.LEIDOS_COUNT = Math.max(0, Math.min(window.TOTAL_VOLS, (window.LEIDOS_COUNT || 0) + delta));
    const pct = window.TOTAL_VOLS > 0 ? Math.round((window.LEIDOS_COUNT / window.TOTAL_VOLS) * 100) : 0;
    const bar = document.getElementById('progressBar');
    const txt = document.getElementById('progressText');
    if (bar) bar.style.width = pct + '%';
    if (txt) txt.textContent = `${window.LEIDOS_COUNT} / ${window.TOTAL_VOLS} volúmenes (${pct}%)`;
}

async function toggleLeido(checkbox, volumenId) {
    checkbox.disabled = true;
    const card = document.getElementById('vol-card-' + volumenId);

    try {
        const resp = await fetch(window.NOVELA_DETALLE_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=toggle_leido&novela_id=${window.NOVELA_ID}&volumen_id=${volumenId}`
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