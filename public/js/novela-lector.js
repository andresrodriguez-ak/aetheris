/* ═══════════════════════════════════════════════════════════════
   Aetheris — novela-lector.js
   Fullscreen del visor de PDF y navegación entre volúmenes con flechas.
   ═══════════════════════════════════════════════════════════════ */

function toggleFullscreen() {
    const el = document.getElementById('pdfContainer');
    if (!document.fullscreenElement) {
        el.requestFullscreen().catch(e => console.warn(e));
    } else {
        document.exitFullscreen();
    }
}

document.addEventListener('keydown', function (e) {
    const btns = [...document.querySelectorAll('.nav-btn:not(.disabled)')];
    if (e.key === 'ArrowLeft'  && btns[0]) btns[0].click();
    if (e.key === 'ArrowRight' && btns[1]) btns[1].click();
});