<?php
$page_title = "Catálogo de Animes";
$accent_color = "anime";
$page_css = ['catalogo.css', 'anime.css'];

require_once __DIR__ . '/../src/includes/header.php';
?>

<div class="anime-container">

    <h1 class="anime-title">
        <img src="uploads/content/anni.gif" style="width:32px; height:32px; object-fit:contain;" alt="">
        Catálogo de Animes
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
            <input type="text" id="filter-search" class="Filter-Input" placeholder="Escribe el nombre de un anime..." autocomplete="off">
        </div>

        <!-- Reset -->
        <div class="Filter-Group filter-reset">
            <button class="reset-btn" id="resetBtn" style="display: none;" onclick="window.resetFilters()">✕ Quitar filtros</button>
        </div>

    </div>

    <div id="anime-results">
        <div class="loading">Cargando catálogo de animes...</div>
    </div>

</div>

<script src="js/catalogo-filtros.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        initCatalogFilters({
            endpoint:           'src/actions/ajax/ajax_catalogo_animes.php',
            resultsContainerId: 'anime-results',
            hasTypeFilter:      false,
            readUrlParams:      false
        });
    });
</script>

<?php
require_once __DIR__ . '/../src/includes/footer.php';
?>