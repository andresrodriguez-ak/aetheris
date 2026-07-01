/* ═══════════════════════════════════════════════════════════════
   Aetheris — animations.js
   JS de efectos visuales: partículas del banner, estrellas en tarjetas y easter egg de sakura.
   ═══════════════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', function() {
    
    // ─── 1. PARTÍCULAS DEL BANNER ───
    var canvasPart = document.getElementById('particles-canvas');
    var sectionPart = document.getElementById('bannerSection');
    
    if (canvasPart && sectionPart) {
        var ctxPart = canvasPart.getContext('2d');
        var particles = [];
        var numParticles = 40;

        function resizePartCanvas() {
            canvasPart.width = sectionPart.offsetWidth;
            canvasPart.height = sectionPart.offsetHeight;
        }
        resizePartCanvas();
        window.addEventListener('resize', resizePartCanvas);

        for (var i = 0; i < numParticles; i++) {
            particles.push({
                x: Math.random() * canvasPart.width,
                y: Math.random() * canvasPart.height,
                vx: (Math.random() - 0.5) * 0.4,
                vy: (Math.random() - 0.5) * 0.4,
                radius: Math.random() * 2 + 0.5,
                alpha: Math.random() * 0.5 + 0.2
            });
        }

        function animatePart() {
            ctxPart.clearRect(0, 0, canvasPart.width, canvasPart.height);
            for (var i = 0; i < particles.length; i++) {
                var p = particles[i];
                p.x += p.vx;
                p.y += p.vy;

                if (p.x < 0 || p.x > canvasPart.width) p.vx *= -1;
                if (p.y < 0 || p.y > canvasPart.height) p.vy *= -1;

                ctxPart.beginPath();
                ctxPart.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
                ctxPart.fillStyle = 'rgba(74, 142, 255, ' + p.alpha + ')';
                ctxPart.fill();
            }
            requestAnimationFrame(animatePart);
        }
        animatePart();
    }

    // ─── 2. ESTRELLAS EN TARJETAS DE CATEGORÍAS ───
    var cardCanvases = document.querySelectorAll('.card-stars-canvas');
    if (cardCanvases && cardCanvases.length > 0) {
        cardCanvases.forEach(function(canvas) {
            var ctx = canvas.getContext('2d');
            if (!ctx) return;
            
            var stars = [];
            var numStars = 15;
            var animId = null;
            var color = canvas.getAttribute('data-color') || '#4a8eff';

            function initStars() {
                if (!canvas.parentElement) return;
                canvas.width = canvas.parentElement.offsetWidth;
                canvas.height = canvas.parentElement.offsetHeight;
                stars = [];
                for (var i = 0; i < numStars; i++) {
                    stars.push({
                        x: Math.random() * canvas.width,
                        y: Math.random() * canvas.height,
                        size: Math.random() * 1.5 + 0.5,
                        alpha: Math.random(),
                        speed: Math.random() * 0.02 + 0.005
                    });
                }
            }

            function drawStars() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                for (var i = 0; i < stars.length; i++) {
                    var s = stars[i];
                    s.alpha += s.speed;
                    if (s.alpha > 1 || s.alpha < 0) s.speed *= -1;
                    if (s.alpha < 0) s.alpha = 0;
                    if (s.alpha > 1) s.alpha = 1;

                    ctx.beginPath();
                    ctx.arc(s.x, s.y, s.size, 0, Math.PI * 2);
                    ctx.fillStyle = hexToRgba(color, s.alpha);
                    ctx.fill();
                }
                animId = requestAnimationFrame(drawStars);
            }

            if (canvas.parentElement) {
                canvas.parentElement.addEventListener('mouseenter', function() {
                    initStars();
                    drawStars();
                });

                canvas.parentElement.addEventListener('mouseleave', function() {
                    if (animId) cancelAnimationFrame(animId);
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                });
            }
        });
    }

    function hexToRgba(hex, alpha) {
        hex = hex.replace('#', '');
        var r = parseInt(hex.substring(0, 2), 16);
        var g = parseInt(hex.substring(2, 4), 16);
        var b = parseInt(hex.substring(4, 6), 16);
        return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
    }

    // ─── 3. EASTER EGG: OVERLAY DE SAKURA ───
    var canvasSakura = document.getElementById('sakura-overlay');
    if (canvasSakura) {
        var ctxSakura = canvasSakura.getContext('2d');
        var petals = [];
        var maxPetals = 30;
        var sakuraAnimId = null;
        var sakuraActive = false;

        function resizeSakura() {
            canvasSakura.width = window.innerWidth;
            canvasSakura.height = window.innerHeight;
        }

        function createPetal() {
            return {
                x: Math.random() * canvasSakura.width,
                y: Math.random() * -20 - 10,
                r: Math.random() * 4 + 2,
                d: Math.random() * maxPetals,
                horizontalSpeed: Math.random() * 1 - 0.5,
                verticalSpeed: Math.random() * 1 + 0.5,
                rotation: Math.random() * 360,
                rotationSpeed: Math.random() * 2 - 1
            };
        }

        function drawSakura() {
            if (!ctxSakura) return;
            ctxSakura.clearRect(0, 0, canvasSakura.width, canvasSakura.height);
            
            if (petals.length < maxPetals && Math.random() < 0.1) {
                petals.push(createPetal());
            }

            for (var i = 0; i < petals.length; i++) {
                var p = petals[i];
                p.y += p.verticalSpeed;
                p.x += p.horizontalSpeed + Math.sin(p.d / 10) * 0.5;
                p.rotation += p.rotationSpeed;

                ctxSakura.save();
                ctxSakura.translate(p.x, p.y);
                ctxSakura.rotate(p.rotation * Math.PI / 180);
                
                ctxSakura.beginPath();
                ctxSakura.ellipse(0, 0, p.r * 1.5, p.r, 0, 0, Math.PI * 2);
                ctxSakura.fillStyle = 'rgba(255, 183, 197, 0.7)';
                ctxSakura.fill();
                ctxSakura.restore();

                if (p.y > canvasSakura.height || p.x < -10 || p.x > canvasSakura.width + 10) {
                    petals[i] = createPetal();
                }
            }
            sakuraAnimId = requestAnimationFrame(drawSakura);
        }

        var sakuraBuffer = "";
        document.addEventListener('keydown', function(e) {
            // Evitar interferencias con inputs de texto abiertos
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                return;
            }

            sakuraBuffer += e.key.toLowerCase();
            if (sakuraBuffer.endsWith('sakura')) {
                if (!sakuraActive) {
                    canvasSakura.style.display = 'block';
                    resizeSakura();
                    window.addEventListener('resize', resizeSakura);
                    petals = [];
                    drawSakura();
                    sakuraActive = true;
                    console.log("🌸 Modo Sakura Activado!");
                } else {
                    if (sakuraAnimId) cancelAnimationFrame(sakuraAnimId);
                    if (ctxSakura) ctxSakura.clearRect(0, 0, canvasSakura.width, canvasSakura.height);
                    canvasSakura.style.display = 'none';
                    window.removeEventListener('resize', resizeSakura);
                    sakuraActive = false;
                    console.log("🌸 Modo Sakura Desactivado.");
                }
                sakuraBuffer = "";
            }
            if (sakuraBuffer.length > 20) sakuraBuffer = sakuraBuffer.substring(10);
        });
    }
});