/* ═══════════════════════════════════════════════════════════════
   Aetheris — catalogo-filtros.js
   Filtros y paginación compartidos entre directorio.php, anime-home.php
   y catálogos futuros. Config vía initCatalogFilters({ endpoint,
   resultsContainerId, hasTypeFilter, readUrlParams }).
   ═══════════════════════════════════════════════════════════════ */

let _catalogConfig = null;
const csValues = { 'sel-type': '', 'sel-status': '' };
let selectedGenres = [];

function initCatalogFilters(config) {
    _catalogConfig = Object.assign({
        endpoint:           '',
        resultsContainerId: '',
        hasTypeFilter:      false,
        readUrlParams:      false
    }, config);

    if (!_catalogConfig.endpoint || !_catalogConfig.resultsContainerId) {
        console.error('initCatalogFilters: falta endpoint o resultsContainerId.');
        return;
    }

    if (_catalogConfig.hasTypeFilter && document.getElementById('sel-type')) {
        initCS('sel-type', applyFilters);
    }
    if (document.getElementById('sel-status')) {
        initCS('sel-status', applyFilters);
    }
    if (document.getElementById('ms-genre')) {
        initMultiSelect();
    }

    if (_catalogConfig.readUrlParams) {
        applyUrlParams();
    }

    let searchTimeout;
    const searchInput = document.getElementById('filter-search');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(applyFilters, 350);
        });
    }

    actualizarBtnReset();
    loadCatalogPage(1);
}

/* Select simple: Tipo / Estado */
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

/* Multi-select: Géneros */
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

function closeAllDropdowns() {
    document.querySelectorAll('.custom-select').forEach(s => s.classList.remove('open'));
    const ms = document.getElementById('ms-genre');
    if (ms) ms.classList.remove('open');
}

document.addEventListener('click', function (e) {
    if (!e.target.closest('.custom-select') && !e.target.closest('.multi-select'))
        closeAllDropdowns();
});

function actualizarBtnReset() {
    const searchEl = document.getElementById('filter-search');
    const hayFiltro =
        (_catalogConfig.hasTypeFilter && csValues['sel-type'] !== '') ||
        csValues['sel-status'] !== '' ||
        selectedGenres.length > 0 ||
        (searchEl && searchEl.value.trim() !== '');
    const btnReset = document.getElementById('resetBtn');
    if (btnReset) btnReset.style.display = hayFiltro ? 'block' : 'none';
}

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
    const searchEl = document.getElementById('filter-search');
    if (searchEl) searchEl.value = '';
    actualizarBtnReset();
    // Limpiar querystring de la URL sin recargar
    history.replaceState(null, '', window.location.pathname);
    loadCatalogPage(1);
};

/* Paginación llama a esta función global desde el HTML del ajax */
function loadCatalogPage(pagina = 1) {
    const resultsContainer = document.getElementById(_catalogConfig.resultsContainerId);
    if (resultsContainer) {
        resultsContainer.innerHTML = '<div class="loading">Cargando contenido...</div>';
    }

    const params = new URLSearchParams();
    if (_catalogConfig.hasTypeFilter && csValues['sel-type']) params.append('type', csValues['sel-type']);
    if (csValues['sel-status']) params.append('status', csValues['sel-status']);
    selectedGenres.forEach(g => params.append('genre[]', g.value));
    const searchEl = document.getElementById('filter-search');
    const search = searchEl ? searchEl.value : '';
    if (search) params.append('search', search);
    params.append('pagina', pagina);

    const base = (typeof window.BASE_URL !== 'undefined') ? window.BASE_URL : '../';

    fetch(`${base}${_catalogConfig.endpoint}?${params.toString()}`)
        .then(r => { if (!r.ok) throw new Error(); return r.text(); })
        .then(html => {
            if (!resultsContainer) return;
            resultsContainer.innerHTML = html;
            window.scrollTo({
                top: resultsContainer.offsetTop - 20,
                behavior: 'smooth'
            });
        })
        .catch(() => {
            if (resultsContainer) {
                resultsContainer.innerHTML =
                    '<div class="error">Error al cargar el contenido. Inténtalo de nuevo.</div>';
            }
        });
}

function applyFilters() {
    actualizarBtnReset();
    loadCatalogPage(1);
}

/* Lee ?type=, ?status=, ?genre[]= de la URL (solo si readUrlParams: true) */
function applyUrlParams() {
    const urlParams = new URLSearchParams(window.location.search);

    if (_catalogConfig.hasTypeFilter) {
        const typeParam = urlParams.get('type');
        if (typeParam) {
            csValues['sel-type'] = typeParam;
            const sel = document.getElementById('sel-type');
            const opt = sel && sel.querySelector(`.cs-option[data-value="${typeParam}"]`);
            if (opt) {
                sel.querySelector('.cs-selected').textContent = opt.textContent;
                sel.querySelectorAll('.cs-option').forEach(o => o.classList.remove('selected'));
                opt.classList.add('selected');
            }
        }
    }

    const statusParam = urlParams.get('status');
    if (statusParam) {
        csValues['sel-status'] = statusParam;
        const sel = document.getElementById('sel-status');
        const opt = sel && sel.querySelector(`.cs-option[data-value="${statusParam}"]`);
        if (opt) {
            sel.querySelector('.cs-selected').textContent = opt.textContent;
            sel.querySelectorAll('.cs-option').forEach(o => o.classList.remove('selected'));
            opt.classList.add('selected');
        }
    }

    // soporta ?genre=2 y ?genre[]=2&genre[]=5
    const genreSimple = urlParams.get('genre');
    const genreMulti  = urlParams.getAll('genre[]');
    const genresToLoad = genreSimple ? [genreSimple] : genreMulti;

    if (genresToLoad.length > 0) {
        const ms = document.getElementById('ms-genre');
        if (!ms) return;
        const list    = ms.querySelector('.ms-list');
        const trigger = ms.querySelector('.ms-trigger');
        const countEl = ms.querySelector('.ms-count');

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

            trigger.querySelectorAll('.ms-tag-remove').forEach(btn => {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    const v = this.dataset.val;
                    selectedGenres = selectedGenres.filter(g => g.value !== v);
                    const item = list.querySelector(`.ms-item[data-value="${v}"]`);
                    if (item) item.classList.remove('checked');
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