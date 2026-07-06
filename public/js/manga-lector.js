/* ═══════════════════════════════════════════════════════════════
   Aetheris — manga-lector.js
   Control de pantalla completa del visor de PDF (capítulos de manga).
   ═══════════════════════════════════════════════════════════════ */

let isFullscreen = false;
const fsBtn      = document.getElementById('fsBtn');
const container  = document.getElementById('pdfContainer');
const iframe     = container ? container.querySelector('iframe') : null;

function enterFullscreen() {
    container.style.position = "fixed";
    container.style.top = "0";
    container.style.left = "0";
    container.style.width = "100%";
    container.style.height = "100vh";
    container.style.zIndex = "9999";
    container.style.borderRadius = "0";
    container.style.margin = "0";
    container.style.padding = "0";

    iframe.style.height = "100vh";
    iframe.style.borderRadius = "0";
    iframe.style.maxWidth = "100%";

    fsBtn.textContent = "⛶ Salir Pantalla Completa";
    isFullscreen = true;
    history.pushState({fullscreen: true}, '');
}

function exitFullscreen() {
    container.style.position = "";
    container.style.top = "";
    container.style.left = "";
    container.style.width = "";
    container.style.height = "";
    container.style.zIndex = "";
    container.style.borderRadius = "";
    container.style.margin = "";
    container.style.padding = "";

    iframe.style.height = "720px";
    iframe.style.borderRadius = "8px";
    iframe.style.maxWidth = "85%";

    fsBtn.textContent = "⛶ Pantalla Completa";
    isFullscreen = false;
}

if (fsBtn && container && iframe) {
    fsBtn.addEventListener('click', function() {
        if (!isFullscreen) {
            enterFullscreen();
        } else {
            exitFullscreen();
        }
    });

    window.addEventListener('popstate', function() {
        if (isFullscreen) {
            exitFullscreen();
        }
    });

    let lastTap = 0;
    container.addEventListener('touchend', function() {
        const currentTime = new Date().getTime();
        const tapLength = currentTime - lastTap;
        if (tapLength < 500 && tapLength > 0 && isFullscreen) {
            exitFullscreen();
        }
        lastTap = currentTime;
    });
}