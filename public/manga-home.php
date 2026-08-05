<?php
$page_title = "Catálogo de Mangas";
$accent_color = "manga";
$page_css = ['catalogo.css', 'manga.css'];

require_once __DIR__ . '/../src/includes/header.php';
?>

<div class="manga-container">

    <h1 class="manga-title">
        <img src="uploads/content/icon_manga.gif" style="width:32px; height:32px; object-fit:contain;" alt="">
        Catálogo de Mangas
    </h1>

    <div class="Filters">

        <!-- Estado -->
        <div class="Filter-Group">
            <div class="custom-select" id="sel-status">
                <div class="cs-selected">Todos los estados</div>
                <div class="cs-options">
                    <div class="cs-option selected" data-value="">Todos los estados</div>
                    <div class="cs-option" data-value="En emisión">En emisión</div>
                    <div class="cs-option" data-value="Finalizado">Finalizado</div>
                    <div class="cs-option" data-value="Próximamente">Próximamente</div>
                </div>
            </div>
        </div>

        <!-- Géneros (multi-select) -->
        <div class="Filter-Group">
            <div class="multi-select" id="ms-genre">
                <div class="ms-trigger">
                    <span class="ms-placeholder">Todos los géneros</span>
                </div>
                <div class="ms-dropdown">
                    <div class="ms-search-wrap">
                        <input type="text" class="ms-search" placeholder="Buscar género...">
                    </div>
                    <div class="ms-list">
                        <?php
                        $gen_res = $conn->query("SELECT id, nombre FROM generos ORDER BY nombre ASC");
                        if ($gen_res) {
                            while ($g = $gen_res->fetch_assoc()) {
                                echo '<div class="ms-item" data-value="' . (int)$g['id'] . '" data-label="' . htmlspecialchars($g['nombre']) . '">
                                        <span class="ms-checkbox"></span>
                                        <span>' . htmlspecialchars($g['nombre']) . '</span>
                                      </div>';
                            }
                        }
                        ?>
                    </div>
                    <div class="ms-footer">
                        <span class="ms-count">0 seleccionados</span>
                        <button class="ms-clear">Limpiar</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Búsqueda -->
        <div class="Filter-Group">
            <input type="text" id="filter-search" class="Filter-Input" placeholder="Escribe el nombre de un manga..." autocomplete="off">
        </div>

        <!-- Reset -->
        <div class="Filter-Group filter-reset">
            <button class="reset-btn" id="resetBtn" style="display: none;" onclick="window.resetFilters()">✕ Quitar filtros</button>
        </div>

    </div>

    <div id="manga-results">
        <div class="loading">Cargando catálogo de mangas...</div>
    </div>

</div>

<script src="js/catalogo-filtros.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        initCatalogFilters({
            endpoint:           'src/actions/ajax/ajax_catalogo_mangas.php',
            resultsContainerId: 'manga-results',
            hasTypeFilter:      false,
            readUrlParams:      false
        });
    });
</script>

<?php
require_once __DIR__ . '/../src/includes/footer.php';
?>