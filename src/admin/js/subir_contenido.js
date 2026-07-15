/* ═══════════════════════════════════════════════════════════════
   Aetheris Admin — subir_contenido.js
   Lógica compartida para los formularios de carga/edición de
   contenido individual: subir_anime.php, subir_episodio.php, y a
   futuro subir_manga.php, subir_capitulo.php, subir_novela.php,
   subir_volumen.php.
   ═══════════════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.file-upload-area').forEach(function (area) {
        setupFileInput(area);
    });

    document.querySelectorAll('.custom-select').forEach(function (wrapper) {
        setupImageSelect(wrapper);
    });
});

/* ── Carga de imagen: la preview reemplaza el placeholder del recuadro ── */
function setupFileInput(area) {
    var input   = area.querySelector('input[type="file"]');
    var preview = area.querySelector('.area-preview-img');
    var nameEl  = area.dataset.nameTarget ? document.getElementById(area.dataset.nameTarget) : null;

    if (!input || !preview) return;

    input.addEventListener('change', function () {
        if (!this.files[0]) return;

        if (nameEl) {
            nameEl.textContent = this.files[0].name;
            nameEl.style.display = 'block';
        }

        var reader = new FileReader();
        reader.onload = function (e) {
            preview.src = e.target.result;
            area.classList.add('has-file');
        };
        reader.readAsDataURL(this.files[0]);
    });
}

/* ── Select con imagen + buscador (elegir anime/manga/novela) ── */
function setupImageSelect(wrapper) {
    var selected    = wrapper.querySelector('.select-selected');
    var items       = wrapper.querySelector('.select-items');
    var hiddenInput = document.getElementById(wrapper.dataset.hiddenInput || 'id_anime');
    var fallbackImg = wrapper.dataset.fallbackImg || '';

    if (!selected || !items || !hiddenInput) return;

    var search = document.createElement('div');
    search.className = 'select-search';
    search.innerHTML = '<input type="text" placeholder="Buscar...">';
    items.insertBefore(search, items.firstChild);
    var searchInput = search.querySelector('input');

    var allItems = items.querySelectorAll('.select-item');

    selected.addEventListener('click', function (e) {
        e.stopPropagation();
        var isOpen = items.classList.toggle('open');
        selected.classList.toggle('open', isOpen);
        if (isOpen) {
            searchInput.focus();
            searchInput.value = '';
            allItems.forEach(function (i) { i.style.display = 'flex'; });
        }
    });

    document.addEventListener('click', function (e) {
        if (!wrapper.contains(e.target)) {
            items.classList.remove('open');
            selected.classList.remove('open');
        }
    });

    allItems.forEach(function (item) {
        item.addEventListener('click', function () {
            hiddenInput.value = this.dataset.value;
            selected.innerHTML =
                '<img src="' + this.dataset.img + '" alt="' + this.dataset.name + '" ' +
                'onerror="this.onerror=null; this.src=\'' + fallbackImg + '\'; this.classList.add(\'img-placeholder\');">' +
                '<span>' + this.dataset.name + '</span>';
            allItems.forEach(function (i) { i.classList.remove('selected'); });
            this.classList.add('selected');
            items.classList.remove('open');
            selected.classList.remove('open');
        });
    });

    searchInput.addEventListener('input', function (e) {
        e.stopPropagation();
        var q = this.value.toLowerCase();
        var visible = 0;
        allItems.forEach(function (item) {
            var match = (item.dataset.name || '').toLowerCase().indexOf(q) !== -1;
            item.style.display = match ? 'flex' : 'none';
            if (match) visible++;
        });
        var noRes = items.querySelector('.no-results');
        if (visible === 0) {
            if (!noRes) {
                noRes = document.createElement('div');
                noRes.className = 'no-results';
                noRes.textContent = 'Sin resultados';
                items.appendChild(noRes);
            }
        } else if (noRes) {
            noRes.remove();
        }
    });

    searchInput.addEventListener('click', function (e) { e.stopPropagation(); });
}