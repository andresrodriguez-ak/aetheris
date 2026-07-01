<?php
/* ═══════════════════════════════════════════════════════════════
   Aetheris — ajax_catalogo_animes.php
   Filtra y pagina el catálogo de animes (usado por anime-home.js). Devuelve HTML directo.
   ═══════════════════════════════════════════════════════════════ */
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../../../config/db_config.php';

$pagina    = max(1, (int)($_GET['pagina'] ?? 1));
$porPagina = 20;
$offset    = ($pagina - 1) * $porPagina;

$status  = trim($_GET['status'] ?? '');
$search  = trim($_GET['search'] ?? '');
$genres  = isset($_GET['genre']) && is_array($_GET['genre'])
           ? array_values(array_filter(array_map('intval', $_GET['genre'])))
           : [];

// ── WHERE ─────────────────────────────────────────────────────────
$conditions = [];
$params     = [];
$types      = '';

if ($status !== '') {
    $conditions[] = "a.estado = ?";
    $params[]     = $status;
    $types       .= 's';
}

if (!empty($genres)) {
    $placeholders = implode(',', array_fill(0, count($genres), '?'));
    $conditions[] = "a.id IN (
        SELECT anime_id FROM anime_generos
        WHERE genero_id IN ($placeholders)
        GROUP BY anime_id
        HAVING COUNT(DISTINCT genero_id) = " . count($genres) . "
    )";
    foreach ($genres as $g) { $params[] = $g; $types .= 'i'; }
}

if ($search !== '') {
    $conditions[] = "a.nombre LIKE ?";
    $params[]     = '%' . $search . '%';
    $types       .= 's';
}

$where = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

// ── CONTEO ────────────────────────────────────────────────────────
$countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM animes a $where");
if (!empty($params)) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$total        = (int)$countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();
$totalPaginas = $total > 0 ? (int)ceil($total / $porPagina) : 1;
$pagina       = min($pagina, $totalPaginas);
$offset       = ($pagina - 1) * $porPagina;

// ── CONSULTA PRINCIPAL ────────────────────────────────────────────
$mainParams = array_merge($params, [$porPagina, $offset]);
$mainTypes  = $types . 'ii';

$stmt = $conn->prepare(
    "SELECT a.id, a.nombre, a.imagen, a.estado,
            GROUP_CONCAT(g.nombre ORDER BY g.nombre SEPARATOR ', ') AS generos
     FROM animes a
     LEFT JOIN anime_generos ag ON ag.anime_id = a.id
     LEFT JOIN generos g        ON g.id = ag.genero_id
     $where
     GROUP BY a.id
     ORDER BY a.id DESC
     LIMIT ? OFFSET ?"
);
$stmt->bind_param($mainTypes, ...$mainParams);
$stmt->execute();
$result = $stmt->get_result();

// ── HTML ──────────────────────────────────────────────────────────
if ($result->num_rows === 0) {
    echo '<div class="no-results">No se encontraron animes con estos filtros.</div>';
    $stmt->close();
    exit;
}

echo '<div class="anime-grid">';
while ($anime = $result->fetch_assoc()):
    $imagen   = htmlspecialchars($anime['imagen'] ?? '');
    $nombre   = htmlspecialchars($anime['nombre']);
    $estado   = $anime['estado'] ?? '';
    $generos  = htmlspecialchars($anime['generos'] ?? 'Sin géneros');
    $id       = (int)$anime['id'];
    $clase_estado = match($estado) {
        'En emisión'    => 'emision',
        'Finalizado'    => 'finalizado',
        'Próximamente'  => 'proximo',
        default         => ''
    };
?>
    <div class="anime-card">
        <a href="anime-detalle.php?id=<?php echo $id; ?>">
            <div class="anime-cover-wrapper">
                <img class="anime-cover" src="<?php echo $imagen; ?>" alt="<?php echo $nombre; ?>" loading="lazy">
                <?php if ($estado): ?>
                    <span class="anime-status-tag <?php echo $clase_estado; ?>"><?php echo htmlspecialchars($estado); ?></span>
                <?php endif; ?>
            </div>
            <div class="anime-info">
                <h3 class="anime-name"><?php echo $nombre; ?></h3>
                <div class="anime-genres"><?php echo $generos; ?></div>
            </div>
        </a>
    </div>
<?php endwhile;
echo '</div>';

$stmt->close();

// ── PAGINACIÓN ────────────────────────────────────────────────────
if ($totalPaginas > 1):
?>
<div class="pagination-container">
    <div class="pagination-info">Página <?php echo $pagina; ?> de <?php echo $totalPaginas; ?> — <?php echo $total; ?> resultados</div>
    <div class="pagination">
        <?php if ($pagina > 1): ?>
            <button class="pagination-button" onclick="loadPage(<?php echo $pagina - 1; ?>)">‹ Anterior</button>
        <?php endif; ?>

        <?php
        $start = max(1, $pagina - 2);
        $end   = min($totalPaginas, $pagina + 2);
        if ($start > 1) {
            echo '<button class="pagination-button" onclick="loadPage(1)">1</button>';
            if ($start > 2) echo '<span class="pagination-dots">…</span>';
        }
        for ($i = $start; $i <= $end; $i++) {
            $active = $i === $pagina ? ' active' : '';
            echo "<button class=\"pagination-button{$active}\" onclick=\"loadPage({$i})\">{$i}</button>";
        }
        if ($end < $totalPaginas) {
            if ($end < $totalPaginas - 1) echo '<span class="pagination-dots">…</span>';
            echo "<button class=\"pagination-button\" onclick=\"loadPage({$totalPaginas})\">{$totalPaginas}</button>";
        }
        ?>

        <?php if ($pagina < $totalPaginas): ?>
            <button class="pagination-button" onclick="loadPage(<?php echo $pagina + 1; ?>)">Siguiente ›</button>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>