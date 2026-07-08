/* ═══════════════════════════════════════════════════════════════
   Aetheris — novela-home.js
   Filtros del catálogo (estado, géneros, búsqueda) y carga paginada vía fetch.
   ═══════════════════════════════════════════════════════════════ */
var csValues = { 'sel-status': '' };
var selectedGenres = [];

document.addEventListener('DOMContentLoaded', function() {
    initCustomSelect('sel-status-btn', 'sel-status-dropdown', function(val) {
        csValues['sel-status'] = val;
        applyFilters();
    });

    initMultiSelectGenres();

    var searchTimeout;
    var searchInput = document.getElementById('filter-search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(applyFilters, 350);
        });
    }

    loadPage(1);
});

function initCustomSelect(btnId, dropdownId, callback) {
    var btn = document.getElementById(btnId);
    var dropdown = document.getElementById(dropdownId);
    if (!btn || !dropdown) return;

    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        closeAllDropdowns();
        dropdown.classList.toggle('open');
    });

    var options = dropdown.querySelectorAll('.cs-option');
    options.forEach(function(opt) {
        opt.addEventListener('click', function(e) {
            e.stopPropagation();
            var val = opt.getAttribute('data-value');
            var text = opt.textContent;

            btn.setAttribute('data-value', val);
            var placeholderSpan = btn.querySelector('.cs-placeholder');
            if (placeholderSpan) {
                placeholderSpan.textContent = text;
            } else {
                btn.firstChild.textContent = text + ' ';
            }

            options.forEach(function(o) { o.classList.remove('selected'); });
            opt.classList.add('selected');

            dropdown.classList.remove('open');
            if (callback) callback(val);
        });
    });
}

function initMultiSelectGenres() {
    var btn = document.getElementById('sel-genre-btn');
    var dropdown = document.getElementById('sel-genre-dropdown');
    if (!btn || !dropdown) return;

    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        closeAllDropdowns();
        dropdown.classList.toggle('open');
    });

    var options = dropdown.querySelectorAll('.cs-option');
    options.forEach(function(opt) {
        opt.addEventListener('click', function(e) {
            e.stopPropagation();
            var val = opt.getAttribute('data-value');
            var name = opt.textContent;

            var index = selectedGenres.findIndex(function(g) { return g.value === val; });
            if (index === -1) {
                selectedGenres.push({ value: val, text: name });
                opt.classList.add('selected');
            } else {
                selectedGenres.splice(index, 1);
                opt.classList.remove('selected');
            }

            renderGenreTags();
            applyFilters();
        });
    });

    document.addEventListener('click', closeAllDropdowns);
}

function closeAllDropdowns() {
    var dropdowns = document.querySelectorAll('.cs-dropdown');
    dropdowns.forEach(function(d) { d.classList.remove('open'); });
}

function renderGenreTags() {
    var container = document.getElementById('selected-genres-tags');
    var dropdown = document.getElementById('sel-genre-dropdown');
    if (!container) return;
    container.innerHTML = '';

    selectedGenres.forEach(function(g) {
        var tag = document.createElement('span');
        tag.className = 'ms-tag';
        tag.innerHTML = htmlspecialchars(g.text) + ' <span class="ms-tag-remove" data-value="' + g.value + '">×</span>';

        tag.querySelector('.ms-tag-remove').addEventListener('click', function(e) {
            e.stopPropagation();
            var val = this.getAttribute('data-value');
            selectedGenres = selectedGenres.filter(function(item) { return item.value !== val; });

            if (dropdown) {
                var opt = dropdown.querySelector('.cs-option[data-value="' + val + '"]');
                if (opt) opt.classList.remove('selected');
            }

            renderGenreTags();
            applyFilters();
        });
        container.appendChild(tag);
    });
}

function loadPage(pagina) {
    if (!pagina) pagina = 1;
    var resultsContainer = document.getElementById('novela-results');
    if (resultsContainer) {
        resultsContainer.innerHTML = '<div class="loading">Cargando novelas...</div>';
    }

    var params = new URLSearchParams();
    if (csValues['sel-status']) {
        params.append('status', csValues['sel-status']);
    }

    selectedGenres.forEach(function(g) {
        params.append('genre[]', g.value);
    });

    var searchVal = document.getElementById('filter-search').value;
    if (searchVal) {
        params.append('search', searchVal);
    }
    params.append('pagina', pagina);

    var base = (typeof window.BASE_URL !== 'undefined') ? window.BASE_URL : '../';
    fetch(`${base}src/actions/ajax/ajax_catalogo_novelas.php?${params.toString()}`)
        .then(function(response) {
            if(!response.ok) {
                throw new Error("HTTP error, status = " + response.status);
            }
            return response.text();
        })
        .then(function(html) {
            if (resultsContainer) {
                resultsContainer.innerHTML = html;
                window.scrollTo({
                    top: resultsContainer.offsetTop - 20,
                    behavior: 'smooth'
                });
            }
        })
        .catch(function(error) {
            console.error("Error al filtrar catálogo:", error);
            if (resultsContainer) {
                resultsContainer.innerHTML = '<div class="error">Error al cargar las novelas.</div>';
            }
        });
}

function applyFilters() {
    actualizarBtnReset();
    loadPage(1);
}

function actualizarBtnReset() {
    var btnReset = document.getElementById('btn-reset-filters');
    if (!btnReset) return;

    var searchVal = document.getElementById('filter-search').value;
    if (csValues['sel-status'] !== '' || selectedGenres.length > 0 || searchVal !== '') {
        btnReset.style.display = 'block';
    } else {
        btnReset.style.display = 'none';
    }
}

function resetAllFilters() {
    document.getElementById('filter-search').value = '';
    csValues['sel-status'] = '';
    selectedGenres = [];

    var statusBtn = document.getElementById('sel-status-btn');
    if (statusBtn) {
        var placeholder = statusBtn.querySelector('.cs-placeholder');
        if (placeholder) placeholder.textContent = 'Todos los estados';
    }

    var allOptions = document.querySelectorAll('.cs-option');
    allOptions.forEach(function(o) { o.classList.remove('selected'); });

    renderGenreTags();
    applyFilters();
}

function htmlspecialchars(str) {
    if (typeof str !== 'string') return '';
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}