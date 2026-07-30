/* ═══════════════════════════════════════════════════════════════
   Aetheris — main.js
   JS global: navbar horizontal (dropdown de usuario + colapso mobile).
   Se carga en todas las páginas via header.php.
   ═══════════════════════════════════════════════════════════════ */

document.addEventListener('DOMContentLoaded', function () {
    initUserMenu();
    initNavToggle();
});

function initUserMenu() {
    var btn  = document.getElementById('userMenuBtn');
    var menu = document.getElementById('userMenuContent');
    if (!btn || !menu) return;

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        var open = menu.classList.toggle('open');
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    document.addEventListener('click', function (e) {
        if (!menu.contains(e.target) && !btn.contains(e.target)) {
            menu.classList.remove('open');
            btn.setAttribute('aria-expanded', 'false');
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            menu.classList.remove('open');
            btn.setAttribute('aria-expanded', 'false');
        }
    });
}

function initNavToggle() {
    var toggle = document.getElementById('navToggle');
    var links  = document.getElementById('navLinks');
    if (!toggle || !links) return;

    toggle.addEventListener('click', function () {
        var open = links.classList.toggle('open');
        toggle.classList.toggle('open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
}