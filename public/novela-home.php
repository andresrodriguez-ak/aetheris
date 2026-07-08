<?php
$page_title = "Catálogo de Novelas";
$accent_color = "novela";
$page_css = ['novela.css'];

require_once __DIR__ . '/../src/includes/header.php';
?>

<div class="novela-container">

    <h1 class="novela-title">
             <img src="uploads/content/icon_novelas.gif" style="width:32px; height:32px; object-fit:contain;" alt="">
        Catálogo de Novelas
    </h1>

    <div class="filter-container">

        <div class="filter-group">
            <label for="filter-search">Buscar Novela</label>
            <input type="text" id="filter-search" class="filter-input" placeholder="Escribe el nombre de una novela..." autocomplete="off">
        </div>

        <div class="filter-group">
            <label>Estado</label>
            <div class="cs-wrapper" id="cs-status-wrapper">
                <div class="cs-select" id="sel-status-btn" data-value="">
                    <span class="cs-placeholder">Todos los estados</span>
                    <span class="cs-arrow">▼</span>
                </div>
                <div class="cs-dropdown" id="sel-status-dropdown">
                    <div class="cs-option" data-value="">Todos los estados</div>
                    <div class="cs-option" data-value="En emisión">En emisión</div>
                    <div class="cs-option" data-value="Finalizado">Finalizado</div>
                    <div class="cs-option" data-value="Próximamente">Próximamente</div>
                </div>
            </div>
        </div>

        <div class="filter-group">
            <label>Géneros</label>
            <div class="cs-wrapper" id="cs-genre-wrapper">
                <div class="cs-select" id="sel-genre-btn">
                    <span>Seleccionar géneros</span>
                    <span class="cs-arrow">▼</span>
                </div>
                <div class="cs-dropdown" id="sel-genre-dropdown">
                    <?php
                    $gen_res = $conn->query("SELECT id, nombre FROM generos ORDER BY nombre ASC");
                    if ($gen_res) {
                        while ($g = $gen_res->fetch_assoc()) {
                            echo '<div class="cs-option" data-value="' . (int)$g['id'] . '">' . htmlspecialchars($g['nombre']) . '</div>';
                        }
                    }
                    ?>
                </div>
            </div>
        </div>

        <button type="button" id="btn-reset-filters" class="btn-reset" style="display: none;" onclick="resetAllFilters()">
            Limpiar Filtros
        </button>

    </div>

    <div id="selected-genres-tags" style="margin-top: -20px; margin-bottom: 25px; display: flex; flex-wrap: wrap; gap: 8px;"></div>

    <div id="novela-results">
        <div class="loading">Cargando catálogo de novelas...</div>
    </div>

</div>

<script src="js/novela-home.js"></script>

<?php
require_once __DIR__ . '/../src/includes/footer.php';
?>