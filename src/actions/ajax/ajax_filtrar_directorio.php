<?php
require_once __DIR__ . '/../../../config/db_config.php';

// Parámetros de filtrado
$type   = $_GET['type']   ?? '';
$status = $_GET['status'] ?? '';
$genres = $_GET['genre']  ?? [];
$search = $_GET['search'] ?? '';
$pagina = max(1, intval($_GET['pagina'] ?? 1));
$porPagina = 20;

// Sanitizar géneros
if (!is_array($genres)) $genres = [$genres];
$genres = array_values(array_filter(array_map('intval', $genres)));

// ── Construir queries por tipo ──
$queries = [];
$params  = [];
$types   = '';

function buildQuery(string $table, string $alias, string $tipo,
                    string $status, array $genres, string $search,
                    string $genreTable, string $genreFK,
                    array &$params, string &$types): string
{
    $q = "SELECT {$alias}.id, {$alias}.nombre AS titulo, {$alias}.estado,
                 {$alias}.imagen, '{$tipo}' AS tipo
          FROM {$table} {$alias} WHERE 1=1";

    if ($status) {
        $q       .= " AND {$alias}.estado = ?";
        $params[] = $status;
        $types   .= 's';
    }

    if (!empty($genres)) {
        $placeholders = implode(',', array_fill(0, count($genres), '?'));
        $q .= " AND EXISTS (
            SELECT 1 FROM {$genreTable} gx
            WHERE gx.{$genreFK} = {$alias}.id
              AND gx.genero_id IN ($placeholders)
        )";
        foreach ($genres as $gid) {
            $params[] = $gid;
            $types   .= 'i';
        }
    }

    if ($search) {
        $q       .= " AND {$alias}.nombre LIKE ?";
        $params[] = "%$search%";
        $types   .= 's';
    }

    return $q;
}

if ($type) {
    switch ($type) {
        case 'anime':
            $queries[] = buildQuery('animes',  'a', 'anime',  $status, $genres, $search, 'anime_generos',  'anime_id',  $params, $types);
            break;
        case 'manga':
            $queries[] = buildQuery('mangas',  'm', 'manga',  $status, $genres, $search, 'manga_generos',  'manga_id',  $params, $types);
            break;
        case 'novela':
            $queries[] = buildQuery('novelas', 'n', 'novela', $status, $genres, $search, 'novela_generos', 'novela_id', $params, $types);
            break;
    }
} else {
    $queries[] = buildQuery('animes',  'a', 'anime',  $status, $genres, $search, 'anime_generos',  'anime_id',  $params, $types);
    $queries[] = buildQuery('mangas',  'm', 'manga',  $status, $genres, $search, 'manga_generos',  'manga_id',  $params, $types);
    $queries[] = buildQuery('novelas', 'n', 'novela', $status, $genres, $search, 'novela_generos', 'novela_id', $params, $types);
}

$unionQuery = implode(" UNION ALL ", $queries);

// ── Contar total ──
$countQuery = "SELECT COUNT(*) AS total FROM ($unionQuery) AS total_query";
$countStmt  = $conn->prepare($countQuery);
if (!empty($types)) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$total   = $countStmt->get_result()->fetch_assoc()['total'];
$paginas = ceil($total / $porPagina);

// ── Consulta principal con paginación ──
$mainQuery    = "$unionQuery ORDER BY titulo LIMIT ?, ?";
$mainParams   = array_merge($params, [($pagina - 1) * $porPagina, $porPagina]);
$mainTypes    = $types . 'ii';

$stmt = $conn->prepare($mainQuery);
if (!empty($mainTypes)) $stmt->bind_param($mainTypes, ...$mainParams);
$stmt->execute();
$result = $stmt->get_result();

// ── Generar HTML ──
if ($result->num_rows > 0) {
    echo '<div class="List-Animes">';

    while ($row = $result->fetch_assoc()) {
        $detalle_url = match($row['tipo']) {
            'anime'  => 'anime-detalle.php?id='  . $row['id'],
            'manga'  => 'manga-detalle.php?id='  . $row['id'],
            'novela' => 'novela-detalle.php?id=' . $row['id'],
            default  => '#'
        };

        echo '
        <div class="Anime-Card">
            <a href="' . $detalle_url . '">
                <div class="Anime-Image">
                    <img src="' . htmlspecialchars($row['imagen']) . '"
                         alt="' . htmlspecialchars($row['titulo']) . '"
                         loading="lazy">
                    <span class="Type-Badge" data-type="' . $row['tipo'] . '">'
                        . ucfirst($row['tipo']) .
                    '</span>';

        if (!empty($row['estado'])) {
            echo '<span class="Status-Badge" data-status="' . htmlspecialchars($row['estado']) . '">'
                . htmlspecialchars($row['estado']) .
                '</span>';
        }

        echo '      </div>
                <div class="Anime-Info">
                    <h3 class="Anime-Title">' . htmlspecialchars($row['titulo']) . '</h3>
                </div>
            </a>
        </div>';
    }

    echo '</div>';

    // Paginación
    if ($paginas > 1) {
        $desde = ($porPagina * ($pagina - 1)) + 1;
        $hasta = min($porPagina * $pagina, $total);

        echo '<div class="pagination-container">';
        echo '<div class="pagination-info">Mostrando ' . $desde . ' - ' . $hasta . ' de ' . $total . ' resultados</div>';
        echo '<div class="pagination">';

        if ($pagina > 1)
            echo '<button class="pagination-button" onclick="loadCatalogPage(' . ($pagina - 1) . ')">«</button>';

        $inicio = max(1, $pagina - 2);
        $fin    = min($paginas, $pagina + 2);

        if ($inicio > 1) {
            echo '<button class="pagination-button" onclick="loadCatalogPage(1)">1</button>';
            if ($inicio > 2) echo '<span class="pagination-dots">...</span>';
        }

        for ($i = $inicio; $i <= $fin; $i++) {
            $active = ($i == $pagina) ? ' active' : '';
            echo '<button class="pagination-button' . $active . '" onclick="loadCatalogPage(' . $i . ')">' . $i . '</button>';
        }

        if ($fin < $paginas) {
            if ($fin < $paginas - 1) echo '<span class="pagination-dots">...</span>';
            echo '<button class="pagination-button" onclick="loadCatalogPage(' . $paginas . ')">' . $paginas . '</button>';
        }

        if ($pagina < $paginas)
            echo '<button class="pagination-button" onclick="loadCatalogPage(' . ($pagina + 1) . ')">»</button>';

        echo '</div></div>';
    }

} else {
    echo '<div class="no-results">No se encontraron resultados con los filtros seleccionados</div>';
}