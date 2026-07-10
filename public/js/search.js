/* ═══════════════════════════════════════════════════════════════
   Aetheris — search.js
   Todo lo relacionado a búsqueda:
   1. Buscador del header (presente en todas las páginas).
   2. Resaltado de coincidencias y filtros por tipo en busqueda.php
      (esta parte solo corre si existe #searchResults en la página).
   Requiere window.BASE_URL inyectado por PHP.
   ═══════════════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', function () {

    // ─── 1. BUSCADOR DEL HEADER ───
    var searchInput = document.getElementById('m-search');
    var searchBtn   = document.querySelector('.menu-search button');

    if (searchInput) {
        searchInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); buscarContenido(); }
        });
    }
    if (searchBtn) {
        searchBtn.addEventListener('click', function (e) {
            e.preventDefault();
            buscarContenido();
        });
    }

    // ─── 2. RESULTADOS DE BÚSQUEDA (solo corre en busqueda.php) ───
    var resultsContainer = document.getElementById('searchResults');
    if (resultsContainer) {
        var searchQuery = resultsContainer.getAttribute('data-query') || '';

        var highlightText = function (text, query) {
            if (!query) return text;
            var escaped = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            var regex = new RegExp('(' + escaped + ')', 'gi');
            return text.replace(regex, '<mark>$1</mark>');
        };

        document.querySelectorAll('.result-title[data-raw]').forEach(function (el) {
            el.innerHTML = highlightText(el.dataset.raw, searchQuery);
        });

        document.querySelectorAll('.filter-button').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.filter-button').forEach(function (b) {
                    b.classList.remove('active');
                });
                this.classList.add('active');

                var type = this.dataset.type;
                document.querySelectorAll('.result-item').forEach(function (item) {
                    item.style.display = (type === 'all' || item.dataset.type === type) ? 'flex' : 'none';
                });
            });
        });
    }
});

function buscarContenido() {
    var input = document.getElementById('m-search');
    if (input && input.value.trim() !== '') {
        var base = (typeof window.BASE_URL !== 'undefined') ? window.BASE_URL : '';
        window.location.href = base + 'busqueda.php?q=' + encodeURIComponent(input.value.trim());
    }
}