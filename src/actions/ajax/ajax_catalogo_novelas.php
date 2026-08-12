<?php
/* ═══════════════════════════════════════════════════════════════
   Aetheris — ajax_catalogo_novelas.php
   Filtra y pagina el catálogo de novelas. Devuelve HTML directo.
   ═══════════════════════════════════════════════════════════════ */
require_once __DIR__ . '/../../../config/db_config.php';

// Parámetros de filtrado con valores por defecto
$status = $_GET['status'] ?? '';
$genres = $_GET['genre'] ?? [];
if (!is_array($genres)) {
    $genres = $genres !== '' ? [$genres] : [];
}
$search = $_GET['search'] ?? '';
$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$porPagina = 20;
$offset = ($pagina - 1) * $porPagina;

// Consulta base segura
$query = "SELECT a.*, GROUP_CONCAT(DISTINCT g.nombre SEPARATOR ', ') AS generos
          FROM novelas a
          LEFT JOIN novela_generos ag ON a.id = ag.novela_id
          LEFT JOIN generos g ON ag.genero_id = g.id
          WHERE 1=1";

$conditions = [];
$params = [];
$types = '';

if (!empty($status)) {
    $conditions[] = "a.estado = ?";
    $params[] = $status;
    $types .= 's';
}

if (!empty($genres)) {
    // OR: alcanza con al menos uno de los géneros seleccionados
    // (el LEFT JOIN + WHERE ya filtra así, igual que anime/manga/directorio)
    $placeholders = implode(',', array_fill(0, count($genres), '?'));
    $conditions[] = "ag.genero_id IN ($placeholders)";
    foreach ($genres as $g) {
        $params[] = (int)$g;
        $types .= 'i';
    }
}

if (!empty($search)) {
    $conditions[] = "a.nombre LIKE ?";
    $params[] = "%$search%";
    $types .= 's';
}

if (!empty($conditions)) {
    $query .= " AND " . implode(" AND ", $conditions);
}

$query .= " GROUP BY a.id ORDER BY a.id DESC LIMIT ? OFFSET ?";
$params[] = $porPagina;
$params[] = $offset;
$types .= 'ii';

// Preparar y ejecutar
$stmt = $conn->prepare($query);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Consulta para contar el total de resultados (sin LIMIT)
$countQuery = "SELECT COUNT(DISTINCT a.id) as total
               FROM novelas a
               LEFT JOIN novela_generos ag ON a.id = ag.novela_id
               WHERE 1=1";

if (!empty($conditions)) {
    $countQuery .= " AND " . implode(" AND ", $conditions);
}

$countStmt = $conn->prepare($countQuery);
$typesWithoutLimit = substr($types, 0, -2);
if (!empty($typesWithoutLimit)) {
    $countStmt->bind_param($typesWithoutLimit, ...array_slice($params, 0, -2));
}
$countStmt->execute();
$totalResult = $countStmt->get_result()->fetch_assoc();
$totalNovelas = $totalResult['total'];
$totalPaginas = ceil($totalNovelas / $porPagina);

// Generar HTML de resultados
if ($result->num_rows > 0) {
    echo '<div class="List-Animes">';
    while ($novela = $result->fetch_assoc()) {
        $estado = $novela['estado'] ?? '';

        echo '
        <div class="Anime-Card">
            <a href="novela-detalle.php?id=' . (int)$novela['id'] . '">
                <div class="Anime-Image">
                    <img src="' . htmlspecialchars($novela['imagen']) . '" alt="' . htmlspecialchars($novela['nombre']) . '" loading="lazy">
                    <span class="Type-Badge" data-type="novela">Novela</span>';
        if (!empty($estado)) {
            echo '<span class="Status-Badge" data-status="' . htmlspecialchars($estado) . '">' . htmlspecialchars($estado) . '</span>';
        }
        echo '      </div>
                <div class="Anime-Info">
                    <h3 class="Anime-Title">' . htmlspecialchars($novela['nombre']) . '</h3>
                </div>
            </a>
        </div>';
    }
    echo '</div>';

    // Mostrar controles de paginación solo si hay más de una página
    if ($totalPaginas > 1) {
        echo '<div class="pagination-container">';
        echo '<div class="pagination-info">Página ' . $pagina . ' de ' . $totalPaginas . '</div>';
        echo '<div class="pagination">';

        if ($pagina > 1) {
            echo '<button class="pagination-button" onclick="loadCatalogPage(' . ($pagina - 1) . ')">&laquo; Anterior</button>';
        }

        $start = max(1, $pagina - 2);
        $end = min($totalPaginas, $pagina + 2);

        if ($start > 1) {
            echo '<button class="pagination-button" onclick="loadCatalogPage(1)">1</button>';
            if ($start > 2) echo '<span class="pagination-dots">...</span>';
        }

        for ($i = $start; $i <= $end; $i++) {
            $active = $i == $pagina ? ' active' : '';
            echo '<button class="pagination-button' . $active . '" onclick="loadCatalogPage(' . $i . ')">' . $i . '</button>';
        }

        if ($end < $totalPaginas) {
            if ($end < $totalPaginas - 1) echo '<span class="pagination-dots">...</span>';
            echo '<button class="pagination-button" onclick="loadCatalogPage(' . $totalPaginas . ')">' . $totalPaginas . '</button>';
        }

        if ($pagina < $totalPaginas) {
            echo '<button class="pagination-button" onclick="loadCatalogPage(' . ($pagina + 1) . ')">Siguiente &raquo;</button>';
        }

        echo '</div></div>';
    }
} else {
    echo '<div class="no-results">No se encontraron novelas con estos filtros.</div>';
}

$stmt->close();
$countStmt->close();
$conn->close();