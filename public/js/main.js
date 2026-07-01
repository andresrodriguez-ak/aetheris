/* ═══════════════════════════════════════════════════════════════
   Aetheris — main.js
   JS global: menú desplegable y buscador. Requiere window.BASE_URL inyectado por PHP.
   ═══════════════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', function () {

    // ── Menú desplegable ──────────────────────────────────────────
    var menuBtn     = document.getElementById('menuBtn');
    var menuContent = document.getElementById('menuContent');

    if (menuBtn && menuContent) {
        menuBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            menuContent.classList.toggle('open');
        });

        document.addEventListener('click', function (e) {
            if (!menuBtn.contains(e.target) && !menuContent.contains(e.target)) {
                menuContent.classList.remove('open');
            }
        });
    }

    // ── Buscador del menú ─────────────────────────────────────────
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
});

function buscarContenido() {
    var input = document.getElementById('m-search');
    if (input && input.value.trim() !== '') {
        var base = (typeof window.BASE_URL !== 'undefined') ? window.BASE_URL : '';
        window.location.href = base + 'busqueda.php?q=' + encodeURIComponent(input.value.trim());
    }
}