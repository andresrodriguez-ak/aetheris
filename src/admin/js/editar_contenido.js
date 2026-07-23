/* ═══════════════════════════════════════════════════════════════
   Aetheris Admin — editar_contenido.js
   Pestañas de tipo de contenido + selector con buscador que carga
   la lista y el detalle de cada ítem vía AJAX 
   ═══════════════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', function () {
    var tabs        = document.querySelectorAll('.type-tab-btn');
    var wrapper     = document.querySelector('.custom-select');
    var selected    = wrapper.querySelector('.select-selected');
    var items       = wrapper.querySelector('.select-items');
    var form        = document.getElementById('editForm');
    var emptyState  = document.getElementById('emptyState');
    var fallbackImg = wrapper.dataset.fallbackImg;
    var tipoActual  = 'anime';

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabs.forEach(function (t) { t.classList.remove('active'); });
            this.classList.add('active');
            tipoActual = this.dataset.tipo;
            resetSelector();
            cargarLista();
        });
    });

    selected.addEventListener('click', function (e) {
        e.stopPropagation();
        var isOpen = items.classList.toggle('open');
        selected.classList.toggle('open', isOpen);
    });

    document.addEventListener('click', function (e) {
        if (!wrapper.contains(e.target)) {
            items.classList.remove('open');
            selected.classList.remove('open');
        }
    });

    items.addEventListener('click', function (e) {
        var opt = e.target.closest('.select-item');
        if (!opt) return;
        seleccionarItem(opt.dataset.value, opt.dataset.name, opt.dataset.img);
        items.classList.remove('open');
        selected.classList.remove('open');
    });

    function resetSelector() {
        selected.innerHTML = '<img class="img-placeholder" src="' + fallbackImg + '" alt=""><span>Selecciona un ' + tipoActual + '...</span>';
        form.style.display = 'none';
        emptyState.style.display = 'block';
    }

    function bindSearch(input) {
        input.addEventListener('input', function () {
            var q = this.value.toLowerCase();
            items.querySelectorAll('.select-item').forEach(function (opt) {
                var name = (opt.dataset.name || '').toLowerCase();
                opt.style.display = name.indexOf(q) !== -1 ? 'flex' : 'none';
            });
        });
        input.addEventListener('click', function (e) { e.stopPropagation(); });
    }

    function cargarLista() {
        items.innerHTML = '<div class="no-results">Cargando...</div>';

        fetch('editar_contenido.php?ajax=get_list&tipo=' + tipoActual)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.length) {
                    items.innerHTML = '<div class="no-results">No hay registros todavía.</div>';
                    return;
                }

                var html = '<div class="select-search"><input type="text" placeholder="Buscar..."></div>';
                html += data.map(function (item) {
                    var nombreAttr = String(item.nombre).replace(/"/g, '&quot;');
                    return '<div class="select-item" data-value="' + item.id + '" data-name="' + nombreAttr + '" data-img="' + item.imagen + '">' +
                        '<img src="' + item.imagen + '" alt="" onerror="this.onerror=null; this.src=\'' + fallbackImg + '\'; this.classList.add(\'img-placeholder\');">' +
                        '<span>' + item.nombre + '</span>' +
                        '</div>';
                }).join('');

                items.innerHTML = html;
                bindSearch(items.querySelector('.select-search input'));
            })
            .catch(function () {
                items.innerHTML = '<div class="no-results">Error de conexión con el servidor.</div>';
            });
    }

    function seleccionarItem(id, nombre, imagen) {
        selected.innerHTML = '<img src="' + imagen + '" alt="' + nombre + '" ' +
            'onerror="this.onerror=null; this.src=\'' + fallbackImg + '\'; this.classList.add(\'img-placeholder\');">' +
            '<span>' + nombre + '</span>';

        fetch('editar_contenido.php?ajax=get_item&tipo=' + tipoActual + '&id=' + id)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                document.getElementById('f_tipo').value = tipoActual;
                document.getElementById('f_id').value = data.id;
                document.getElementById('f_nombre').value = data.nombre || '';
                document.getElementById('f_descripcion').value = data.descripcion || '';
                document.getElementById('f_estado').value = data.estado || '';

                setPreview('imagen', data.imagen);

                var portadaGroup = document.getElementById('portada-group');
                if (tipoActual === 'novela') {
                    portadaGroup.style.display = 'none';
                } else {
                    portadaGroup.style.display = 'block';
                    setPreview('portada', data.portada);
                }

                var ids = (data.genero_ids || '').split(',');
                document.querySelectorAll('.generos-grid input').forEach(function (cb) {
                    cb.checked = ids.indexOf(cb.value) !== -1;
                });

                form.style.display = 'block';
                emptyState.style.display = 'none';
            })
            .catch(function () {
                alert('Error al obtener los detalles del item.');
            });
    }

    function setPreview(key, rutaAbsoluta) {
        var area = document.querySelector('.file-upload-area[data-name-target="' + key + '-name"]');
        if (!area) return;
        var preview = area.querySelector('.area-preview-img');
        var input   = area.querySelector('input[type="file"]');
        if (input) input.value = '';

        if (rutaAbsoluta) {
            preview.src = rutaAbsoluta;
            area.classList.add('has-file');
        } else {
            preview.src = '';
            area.classList.remove('has-file');
        }
    }

    document.querySelectorAll('.file-upload-area').forEach(function (area) {
        var input   = area.querySelector('input[type="file"]');
        var preview = area.querySelector('.area-preview-img');
        if (!input || !preview) return;

        input.addEventListener('change', function () {
            if (!this.files[0]) return;
            var reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
                area.classList.add('has-file');
            };
            reader.readAsDataURL(this.files[0]);
        });
    });

    cargarLista();
});