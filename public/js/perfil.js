/* ═══════════════════════════════════════════════════════════════
   Aetheris — perfil.js
   Modales de editar perfil / eliminar cuenta, y filtro de tipo en las listas.
   El menú y el buscador globales los maneja main.js / search.js.
   ═══════════════════════════════════════════════════════════════ */

function toggleModal(show) {
    document.getElementById('editModal').style.display = show ? 'flex' : 'none';
}

function toggleDeleteModal(show) {
    document.getElementById('deleteModal').style.display = show ? 'flex' : 'none';
}

function filtrar(tipo, btn) {
    document.querySelectorAll('.filter-btn').forEach(function (b) { b.classList.remove('active'); });
    btn.classList.add('active');
    document.querySelectorAll('.item-type').forEach(function (card) {
        if (tipo === 'todos' || card.getAttribute('data-type') === tipo) {
            card.classList.remove('hide');
        } else {
            card.classList.add('hide');
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    var fileInput = document.getElementById('profile_image');
    var fileName  = document.getElementById('fileInputName');

    if (fileInput && fileName) {
        fileInput.addEventListener('change', function () {
            fileName.textContent = fileInput.files.length ? fileInput.files[0].name : 'Ningún archivo seleccionado';
        });
    }
    var editModal = document.getElementById('editModal');
    if (editModal) {
        editModal.addEventListener('click', function (e) {
            if (e.target === this) toggleModal(false);
        });
    }

    var deleteModal = document.getElementById('deleteModal');
    if (deleteModal) {
        deleteModal.addEventListener('click', function (e) {
            if (e.target === this) toggleDeleteModal(false);
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            toggleModal(false);
            toggleDeleteModal(false);
        }
    });
});