/* ═══════════════════════════════════════════════════════════════
   Aetheris — main.js
   JS global: menú desplegable.
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
});