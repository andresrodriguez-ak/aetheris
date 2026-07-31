<?php
$accent_color = 'general';
$page_css     = ['catalogo.css'];

require_once __DIR__ . '/../config/db_config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$logged_in = isset($_SESSION['user_id']);
$username  = $logged_in ? $_SESSION['username'] : '';
$is_admin  = $logged_in && ($_SESSION['role'] ?? '') === 'admin';

$page_title = 'Directorio';

require_once __DIR__ . '/../src/includes/header.php';
?>
    <h1 class="novela-title">
             <img src="uploads/content/icon_folder.gif" style="width:32px; height:32px; object-fit:contain;" alt="">
        Directorio
    </h1>
        <div class="Filters">
            <!-- Tipo -->
            <div class="Filter-Group">
                <div class="custom-select" id="sel-type">
                    <div class="cs-selected">Todos los tipos</div>
                    <div class="cs-options">
                        <div class="cs-option selected" data-value="">Todos los tipos</div>
                        <div class="cs-option" data-value="anime">Anime</div>
                        <div class="cs-option" data-value="manga">Manga</div>
                        <div class="cs-option" data-value="novela">Novelas</div>
                    </div>
                </div>
            </div>

            <!-- Estado -->
            <div class="Filter-Group">
                <div class="custom-select" id="sel-status">
                    <div class="cs-selected">Todos los estados</div>
                    <div class="cs-options">
                        <div class="cs-option selected" data-value="">Todos los estados</div>
                        <div class="cs-option" data-value="En emisión">En emisión</div>
                        <div class="cs-option" data-value="Finalizado">Finalizado</div>
                        <div class="cs-option" data-value="Próximamente">Próximamente</div>
                        <div class="cs-option" data-value="En progreso">En progreso</div>
                        <div class="cs-option" data-value="Abandonado">Abandonado</div>
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
                            $generos = $conn->query("SELECT id, nombre FROM generos ORDER BY nombre");
                            while ($genero = $generos->fetch_assoc()) {
                                echo '<div class="ms-item" data-value="' . $genero['id'] . '" data-label="' . htmlspecialchars($genero['nombre']) . '">
                                        <span class="ms-checkbox"></span>
                                        <span>' . htmlspecialchars($genero['nombre']) . '</span>
                                      </div>';
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
                <input type="text" id="filter-search" class="Filter-Input" placeholder="Buscar por nombre...">
            </div>

            <!-- Reset -->
            <div class="Filter-Group filter-reset">
                <button class="reset-btn" id="resetBtn" onclick="window.resetFilters()">✕ Quitar filtros</button>
            </div>
        </div>

        <div id="directory-results">
            <div class="loading">Cargando contenido...</div>
        </div>

<script src="js/catalogo-filtros.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        initCatalogFilters({
            endpoint:           'src/actions/ajax/ajax_filtrar_directorio.php',
            resultsContainerId: 'directory-results',
            hasTypeFilter:      true,
            readUrlParams:      true
        });
    });
</script>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?>