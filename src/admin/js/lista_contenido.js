document.addEventListener('DOMContentLoaded', function () {
    var wrapper   = document.getElementById('contentSelect');
    var selectBtn = document.getElementById('contentSelectBtn');
    var panel     = document.getElementById('contentSelectPanel');
    var filterInput = document.getElementById('content-filter');
    var list = document.getElementById('contentGrid');

    if (wrapper && selectBtn && panel) {
        selectBtn.addEventListener('click', function (e) {
            wrapper.classList.toggle('open');
            e.stopPropagation();
            if (wrapper.classList.contains('open') && filterInput) {
                filterInput.focus();
            }
        });

        document.addEventListener('click', function (e) {
            if (!wrapper.contains(e.target)) {
                wrapper.classList.remove('open');
            }
        });
    }

    if (filterInput && list) {
        filterInput.addEventListener('input', function () {
            var term = this.value.trim().toLowerCase();
            list.querySelectorAll('.content-option').forEach(function (item) {
                var name = item.dataset.name || '';
                item.style.display = name.indexOf(term) !== -1 ? '' : 'none';
            });
        });
    }
});