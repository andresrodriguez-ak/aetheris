/* ═══════════════════════════════════════════════════════════════
   Aetheris — ajax-actions.js
   Llamadas asíncronas (fetch) para favoritos, seguimiento y episodios vistos.
   ═══════════════════════════════════════════════════════════════ */
console.log("Modulo ajax-actions.js listo para enlazar endpoints en la Fase 3.");

document.addEventListener('DOMContentLoaded', () => {
    // Localizar el contenedor de contexto de la vista de anime-detalle
    const contextEl = document.getElementById('anime-detail-context');
    if (!contextEl) return;

    // Extraer de forma segura las variables inyectadas mediante atributos data
    const ANIME_ID = parseInt(contextEl.getAttribute('data-anime-id')) || 0;
    const TOTAL_EPS = parseInt(contextEl.getAttribute('data-total-eps')) || 0;
    let vistosCount = parseInt(contextEl.getAttribute('data-vistos-count')) || 0;

    // Referencias a los componentes interactivos del DOM
    const btnFavorito = document.getElementById('btn-favorito');
    const selectEstado = document.getElementById('select-estado');
    const checkboxesVisto = document.querySelectorAll('.visto-checkbox');

    /**
     * Funcion interna para recalcular dinamicamente el progreso en la interfaz
      @param {number} modificador - Valor de cambio (+1 o -1)
     */
    function actualizarProgreso(modificador) {
        vistosCount += modificador;
        if (vistosCount < 0) vistosCount = 0;
        if (vistosCount > TOTAL_EPS) vistosCount = TOTAL_EPS;

        const pct = TOTAL_EPS > 0 ? Math.round((vistosCount / TOTAL_EPS) * 100) : 0;
        const bar = document.getElementById('progress-bar-fill');
        const txt = document.getElementById('progress-text');

        if (bar) {
            bar.style.width = pct + '%';
        }
        if (txt) {
            txt.textContent = vistosCount + ' / ' + TOTAL_EPS + ' episodios (' + pct + '%)';
        }
    }

    // GESTION DE ACCION: ALTERNAR FAVORITO
    if (btnFavorito) {
        btnFavorito.addEventListener('click', async () => {
            btnFavorito.disabled = true;
            try {
                const resp = await fetch('../src/actions/ajax/acciones-anime.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=toggle_favorito&anime_id=' + ANIME_ID
                });

                const data = await resp.json();

                if (data.success) {
                    if (data.favorito) {
                        btnFavorito.classList.add('is-fav');
                        btnFavorito.textContent = 'Quitar de Favoritos';
                    } else {
                        btnFavorito.classList.remove('is-fav');
                        btnFavorito.textContent = 'Anadir a Favoritos';
                    }
                } else {
                    if (data.msg === 'no_login') {
                        alert('Debes iniciar sesion para realizar esta accion.');
                    }
                }
            } catch (err) {
                console.error('Error al procesar favoritos:', err);
            } finally {
                btnFavorito.disabled = false;
            }
        });
    }

    // GESTION DE ACCION: MODIFICAR ESTADO DE SEGUIMIENTO
    if (selectEstado) {
        selectEstado.addEventListener('change', async (e) => {
            const nuevoEstado = e.target.value;
            try {
                const resp = await fetch('../src/actions/ajax/acciones-anime.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=cambiar_estado&anime_id=' + ANIME_ID + '&estado=' + encodeURIComponent(nuevoEstado)
                });

                const data = await resp.json();
                if (!data.success && data.msg === 'no_login') {
                    alert('Debes iniciar sesion para guardar tu estado.');
                }
            } catch (err) {
                console.error('Error al cambiar el estado de seguimiento:', err);
            }
        });
    }

    // GESTION DE ACCION: MARCAR EPISODIOS COMO VISTOS
    checkboxesVisto.forEach(checkbox => {
        checkbox.addEventListener('change', async (e) => {
            const cb = e.target;
            const numEp = parseInt(cb.getAttribute('data-episodio'));
            const card = document.getElementById('ep-card-' + numEp);

            cb.disabled = true;

            try {
                const resp = await fetch('../src/actions/ajax/acciones-anime.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=toggle_visto&anime_id=' + ANIME_ID + '&numero_episodio=' + numEp
                });

                const data = await resp.json();

                if (data.success) {
                    if (data.visto) {
                        if (card) {
                            card.classList.add('visto-card');
                        }
                        cb.checked = true;
                        actualizarProgreso(1);
                    } else {
                        if (card) {
                            card.classList.remove('visto-card');
                        }
                        cb.checked = false;
                        actualizarProgreso(-1);
                    }
                } else {
                    cb.checked = !cb.checked;
                    if (data.msg === 'no_login') {
                        alert('Debes iniciar sesion para marcar episodios vistos.');
                    }
                }
            } catch (err) {
                cb.checked = !cb.checked;
                console.error('Error al modificar estado del episodio:', err);
            } finally {
                cb.disabled = false;
            }
        });
    });
});