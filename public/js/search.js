/* ═══════════════════════════════════════════════════════════════
   Aetheris — search.js
   Carrusel 3D orbital de portadas en el banner de bienvenida.
   ═══════════════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', function() {
    // Portadas del carrusel (inyectadas por PHP)
    var covers = window.AetherisBannerCovers || [];
    var stage = document.getElementById('orbitStage');
    
    
    if (!stage) {
        return;
    }

   
    stage.innerHTML = '';


    if (covers.length === 0) {
        for (var i = 1; i <= 9; i++) {
            covers.push("uploads/banner/" + i + ".jpg");
        }
    }


    covers.forEach(function(src) {
        var el = document.createElement('div');
        el.className = 'orbit-cover';
        var img = document.createElement('img');
        img.src = src;
        img.alt = "";
        img.loading = "lazy";
        el.appendChild(img);
        stage.appendChild(el);
    });

    var items = stage.querySelectorAll('.orbit-cover');
    var N = items.length;
    var angle = 0;


    if (N === 0) return;

    function tick() {
        angle += 0.4;
        var step = (Math.PI * 2) / N;
        var W = stage.offsetWidth || 1200;
        var RX = W / 2 - 60;
        var RY = 38;
        var CX = W / 2;
        var CY = 110;

        items.forEach(function(el, i) {
            var a = i * step + (angle * Math.PI / 180);
            var x = Math.sin(a) * RX;
            var z = Math.cos(a);
            var y = Math.cos(a) * RY;
            var scale = 0.55 + 0.45 * ((z + 1) / 2);

            el.style.left = (CX + x - 40) + 'px';
            el.style.top = (CY + y - 55) + 'px';
            el.style.transform = 'scale(' + scale + ')';
            el.style.zIndex = Math.round((z + 1) * 100);

            el.classList.remove('is-front', 'is-side', 'is-back');
            if (z > 0.6) {
                el.classList.add('is-front');
            } else if (z < -0.4) {
                el.classList.add('is-back');
            } else {
                el.classList.add('is-side');
            }
        });

        requestAnimationFrame(tick);
    }
    
    tick();
});