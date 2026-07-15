/* ═══════════════════════════════════════════════════════════════
   Aetheris Admin — admin.js
   Menú desplegable + buscador del header (mismo comportamiento
   que public/js/main.js + search.js, pero admin vive fuera de
   public/, así que el redirect de búsqueda necesita BASE_URL).
   ═══════════════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', function () {
    var menuBtn     = document.getElementById('menuBtn');
    var menuContent = document.getElementById('menuContent');

    if (menuBtn && menuContent) {
        menuBtn.addEventListener('click', function (e) {
            menuContent.classList.toggle('open');
            e.stopPropagation();
        });

        document.addEventListener('click', function (e) {
            if (!menuBtn.contains(e.target) && !menuContent.contains(e.target)) {
                menuContent.classList.remove('open');
            }
        });
    }

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
    if (input && input.value.trim().length > 2) {
        var base = (typeof window.BASE_URL !== 'undefined') ? window.BASE_URL : '';
        window.location.href = base + 'public/busqueda.php?q=' + encodeURIComponent(input.value.trim());
    }
}