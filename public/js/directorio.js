/* ═══════════════════════════════════════════════════════════════
   Aetheris — directorio.js
   Filtros (tipo/estado/géneros/búsqueda) + paginación del directorio general.
   ═══════════════════════════════════════════════════════════════ */

/* ── Selects simples ── */
const csValues = { 'sel-type': '', 'sel-status': '' };

/* ── Géneros seleccionados ── */
let selectedGenres = [];

/* ════════════════════════════════════════
   Custom Select simple (Tipo / Estado)
════════════════════════════════════════ */
function toggleCS(id) {
    const el = document.getElementById(id);
    const isOpen = el.classList.contains('open');
    closeAllDropdowns();
    if (!isOpen) el.classList.add('open');
}

function initCS(id, onChange) {
    const el = document.getElementById(id);
    el.querySelector('.cs-selected').addEventListener('click', function (e) {
        e.stopPropagation();
        toggleCS(id);
    });
    el.querySelectorAll('.cs-option').forEach(opt => {
        opt.addEventListener('click', function (e) {
            e.stopPropagation();
            csValues[id] = this.dataset.value;
            el.querySelector('.cs-selected').textContent = this.textContent;
            el.querySelectorAll('.cs-option').forEach(o => o.classList.remove('selected'));
            this.classList.add('selected');
            el.classList.remove('open');
            onChange();
        });
    });
}

/* ════════════════════════════════════════
   Multi-Select (Géneros)
════════════════════════════════════════ */
function initMultiSelect() {
    const ms       = document.getElementById('ms-genre');
    const trigger  = ms.querySelector('.ms-trigger');
    const searchIn = ms.querySelector('.ms-search');
    const list     = ms.querySelector('.ms-list');
    const countEl  = ms.querySelector('.ms-count');
    const clearBtn = ms.querySelector('.ms-clear');

    trigger.addEventListener('click', function (e) {
        e.stopPropagation();
        const isOpen = ms.classList.contains('open');
        closeAllDropdowns();
        if (!isOpen) { ms.classList.add('open'); searchIn.focus(); }
    });

    list.addEventListener('click', function (e) {
        const item = e.target.closest('.ms-item');
        if (!item) return;
        e.stopPropagation();
        const val   = item.dataset.value;
        const label = item.dataset.label;
        const idx   = selectedGenres.findIndex(g => g.value === val);
        if (idx === -1) {
            selectedGenres.push({ value: val, label: label });
            item.classList.add('checked');
        } else {
            selectedGenres.splice(idx, 1);
            item.classList.remove('checked');
        }
        updateMSTrigger();
        updateMSCount();
        applyFilters();
    });

    searchIn.addEventListener('input', function (e) {
        e.stopPropagation();
        const q = this.value.toLowerCase();
        list.querySelectorAll('.ms-item').forEach(item => {
            item.style.display = item.dataset.label.toLowerCase().includes(q) ? '' : 'none';
        });
    });
    searchIn.addEventListener('click',   e => e.stopPropagation());
    searchIn.addEventListener('keydown', e => e.stopPropagation());

    clearBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        clearGenres();
        applyFilters();
    });

    function updateMSTrigger() {
        if (selectedGenres.length === 0) {
            trigger.innerHTML = '<span class="ms-placeholder">Todos los géneros</span>';
        } else {
            const tagsHtml = selectedGenres.map(g =>
                `<span class="ms-tag" data-val="${g.value}">
                    ${g.label}
                    <span class="ms-tag-remove" data-val="${g.value}">×</span>
                </span>`
            ).join('');
            trigger.innerHTML = `<span class="ms-tags">${tagsHtml}</span>`;
            trigger.querySelectorAll('.ms-tag-remove').forEach(btn => {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    const v = this.dataset.val;
                    selectedGenres = selectedGenres.filter(g => g.value !== v);
                    const item = list.querySelector(`.ms-item[data-value="${v}"]`);
                    if (item) item.classList.remove('checked');
                    updateMSTrigger();
                    updateMSCount();
                    applyFilters();
                });
            });
        }
    }

    function updateMSCount() {
        countEl.textContent = selectedGenres.length + ' seleccionado' + (selectedGenres.length !== 1 ? 's' : '');
    }
}

function clearGenres() {
    selectedGenres = [];
    const ms = document.getElementById('ms-genre');
    ms.querySelector('.ms-list').querySelectorAll('.ms-item').forEach(i => i.classList.remove('checked'));
    ms.querySelector('.ms-trigger').innerHTML = '<span class="ms-placeholder">Todos los géneros</span>';
    ms.querySelector('.ms-count').textContent = '0 seleccionados';
}

/* ════════════════════════════════════════
   Cerrar todos los dropdowns
════════════════════════════════════════ */
function closeAllDropdowns() {
    document.querySelectorAll('.custom-select').forEach(s => s.classList.remove('open'));
    document.getElementById('ms-genre').classList.remove('open');
}

document.addEventListener('click', function (e) {
    if (!e.target.closest('.custom-select') && !e.target.closest('.multi-select'))
        closeAllDropdowns();
});

/* ── Reset button visibility ── */
function actualizarBtnReset() {
    const hayFiltro =
        csValues['sel-type']   !== '' ||
        csValues['sel-status'] !== '' ||
        selectedGenres.length > 0 ||
        document.getElementById('filter-search').value.trim() !== '';
    document.getElementById('resetBtn').style.display = hayFiltro ? 'block' : 'none';
}

/* ── Reset completo ── */
window.resetFilters = function () {
    csValues['sel-type']   = '';
    csValues['sel-status'] = '';
    document.querySelectorAll('.custom-select').forEach(sel => {
        const first = sel.querySelector('.cs-option');
        sel.querySelector('.cs-selected').textContent = first.textContent;
        sel.querySelectorAll('.cs-option').forEach(o => o.classList.remove('selected'));
        first.classList.add('selected');
    });
    clearGenres();
    document.getElementById('filter-search').value = '';
    actualizarBtnReset();
    // Limpiar URL sin recargar
    history.replaceState(null, '', 'directorio.php');
    loadDirectoryPage(1);
};

/* ── Cargar página ── */
function loadDirectoryPage(pagina = 1) {
    document.getElementById('directory-results').innerHTML = '<div class="loading">Cargando contenido...</div>';

    const params = new URLSearchParams();
    if (csValues['sel-type'])   params.append('type',   csValues['sel-type']);
    if (csValues['sel-status']) params.append('status', csValues['sel-status']);
    selectedGenres.forEach(g => params.append('genre[]', g.value));
    const search = document.getElementById('filter-search').value;
    if (search) params.append('search', search);
    params.append('pagina', pagina);

    const base = (typeof window.BASE_URL !== 'undefined') ? window.BASE_URL : '../';

    fetch(`${base}src/actions/ajax/ajax_filtrar_directorio.php?${params.toString()}`)
        .then(r => { if (!r.ok) throw new Error(); return r.text(); })
        .then(html => {
            document.getElementById('directory-results').innerHTML = html;
            window.scrollTo({
                top: document.getElementById('directory-results').offsetTop - 20,
                behavior: 'smooth'
            });
        })
        .catch(() => {
            document.getElementById('directory-results').innerHTML =
                '<div class="error">Error al cargar el contenido. Inténtalo de nuevo.</div>';
        });
}

function applyFilters() {
    actualizarBtnReset();
    loadDirectoryPage(1);
}

/* ════════════════════════════════════════
   Leer parámetros GET de la URL al cargar
════════════════════════════════════════ */
function applyUrlParams() {
    const urlParams = new URLSearchParams(window.location.search);

    /* -- Tipo -- */
    const typeParam = urlParams.get('type');
    if (typeParam) {
        csValues['sel-type'] = typeParam;
        const sel = document.getElementById('sel-type');
        const opt = sel.querySelector(`.cs-option[data-value="${typeParam}"]`);
        if (opt) {
            sel.querySelector('.cs-selected').textContent = opt.textContent;
            sel.querySelectorAll('.cs-option').forEach(o => o.classList.remove('selected'));
            opt.classList.add('selected');
        }
    }

    /* -- Estado -- */
    const statusParam = urlParams.get('status');
    if (statusParam) {
        csValues['sel-status'] = statusParam;
        const sel = document.getElementById('sel-status');
        const opt = sel.querySelector(`.cs-option[data-value="${statusParam}"]`);
        if (opt) {
            sel.querySelector('.cs-selected').textContent = opt.textContent;
            sel.querySelectorAll('.cs-option').forEach(o => o.classList.remove('selected'));
            opt.classList.add('selected');
        }
    }

    /* -- Género(s): soporta ?genre=2  y  ?genre[]=2&genre[]=5 -- */
    const genreSimple = urlParams.get('genre');
    const genreMulti  = urlParams.getAll('genre[]');
    const genresToLoad = genreSimple ? [genreSimple] : genreMulti;

    if (genresToLoad.length > 0) {
        const list    = document.querySelector('#ms-genre .ms-list');
        const trigger = document.querySelector('#ms-genre .ms-trigger');
        const countEl = document.querySelector('#ms-genre .ms-count');

        genresToLoad.forEach(val => {
            const item = list.querySelector(`.ms-item[data-value="${val}"]`);
            if (!item) return;
            item.classList.add('checked');
            selectedGenres.push({ value: val, label: item.dataset.label });
        });

        if (selectedGenres.length > 0) {
            const tagsHtml = selectedGenres.map(g =>
                `<span class="ms-tag" data-val="${g.value}">
                    ${g.label}
                    <span class="ms-tag-remove" data-val="${g.value}">×</span>
                </span>`
            ).join('');
            trigger.innerHTML = `<span class="ms-tags">${tagsHtml}</span>`;

            /* Reasignar listeners a los botones × */
            trigger.querySelectorAll('.ms-tag-remove').forEach(btn => {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    const v = this.dataset.val;
                    selectedGenres = selectedGenres.filter(g => g.value !== v);
                    const item = list.querySelector(`.ms-item[data-value="${v}"]`);
                    if (item) item.classList.remove('checked');
                    /* Re-render trigger */
                    if (selectedGenres.length === 0) {
                        trigger.innerHTML = '<span class="ms-placeholder">Todos los géneros</span>';
                    } else {
                        const th = selectedGenres.map(g =>
                            `<span class="ms-tag" data-val="${g.value}">
                                ${g.label}
                                <span class="ms-tag-remove" data-val="${g.value}">×</span>
                            </span>`
                        ).join('');
                        trigger.innerHTML = `<span class="ms-tags">${th}</span>`;
                    }
                    countEl.textContent = selectedGenres.length + ' seleccionado' + (selectedGenres.length !== 1 ? 's' : '');
                    applyFilters();
                });
            });

            countEl.textContent = selectedGenres.length + ' seleccionado' + (selectedGenres.length !== 1 ? 's' : '');
        }
    }
}

/* ── Init ── */
document.addEventListener('DOMContentLoaded', () => {
    initCS('sel-type',   applyFilters);
    initCS('sel-status', applyFilters);
    initMultiSelect();

    /* Leer parámetros de la URL ANTES de cargar */
    applyUrlParams();

    let searchTimeout;
    document.getElementById('filter-search').addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 350);
    });

    actualizarBtnReset();
    loadDirectoryPage(1);
});