<?php
/* ═══════════════════════════════════════════════════════════════
   Aetheris — ajax_catalogo_mangas.php
   Filtra y pagina el catálogo de mangas. Devuelve HTML directo.
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
    $conditions[] = "m.estado = ?";
    $params[]     = $status;
    $types       .= 's';
}

if (!empty($genres)) {
    // OR: alcanza con al menos uno de los géneros seleccionados
    $placeholders = implode(',', array_fill(0, count($genres), '?'));
    $conditions[] = "EXISTS (
        SELECT 1 FROM manga_generos gx
        WHERE gx.manga_id = m.id
          AND gx.genero_id IN ($placeholders)
    )";
    foreach ($genres as $g) { $params[] = $g; $types .= 'i'; }
}

if ($search !== '') {
    $conditions[] = "m.nombre LIKE ?";
    $params[]     = '%' . $search . '%';
    $types       .= 's';
}

$where = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

// ── CONTEO ────────────────────────────────────────────────────────
$countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM mangas m $where");
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
    "SELECT m.id, m.nombre, m.imagen, m.estado,
            GROUP_CONCAT(g.nombre ORDER BY g.nombre SEPARATOR ', ') AS generos
     FROM mangas m
     LEFT JOIN manga_generos mg ON mg.manga_id = m.id
     LEFT JOIN generos g        ON g.id = mg.genero_id
     $where
     GROUP BY m.id
     ORDER BY m.id DESC
     LIMIT ? OFFSET ?"
);
$stmt->bind_param($mainTypes, ...$mainParams);
$stmt->execute();
$result = $stmt->get_result();

// ── HTML ──────────────────────────────────────────────────────────
if ($result->num_rows === 0) {
    echo '<div class="no-results">No se encontraron mangas con estos filtros.</div>';
    $stmt->close();
    exit;
}

echo '<div class="List-Animes">';
while ($manga = $result->fetch_assoc()):
    $imagen   = htmlspecialchars($manga['imagen'] ?? '');
    $nombre   = htmlspecialchars($manga['nombre']);
    $estado   = $manga['estado'] ?? '';
    $id       = (int)$manga['id'];
?>
    <div class="Anime-Card">
        <a href="manga-detalle.php?id=<?php echo $id; ?>">
            <div class="Anime-Image">
                <img src="<?php echo $imagen; ?>" alt="<?php echo $nombre; ?>" loading="lazy">
                <span class="Type-Badge" data-type="manga">Manga</span>
                <?php if ($estado): ?>
                    <span class="Status-Badge" data-status="<?php echo htmlspecialchars($estado); ?>"><?php echo htmlspecialchars($estado); ?></span>
                <?php endif; ?>
            </div>
            <div class="Anime-Info">
                <h3 class="Anime-Title"><?php echo $nombre; ?></h3>
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
            <button class="pagination-button" onclick="loadCatalogPage(<?php echo $pagina - 1; ?>)">‹ Anterior</button>
        <?php endif; ?>

        <?php
        $start = max(1, $pagina - 2);
        $end   = min($totalPaginas, $pagina + 2);
        if ($start > 1) {
            echo '<button class="pagination-button" onclick="loadCatalogPage(1)">1</button>';
            if ($start > 2) echo '<span class="pagination-dots">…</span>';
        }
        for ($i = $start; $i <= $end; $i++) {
            $active = $i === $pagina ? ' active' : '';
            echo "<button class=\"pagination-button{$active}\" onclick=\"loadCatalogPage({$i})\">{$i}</button>";
        }
        if ($end < $totalPaginas) {
            if ($end < $totalPaginas - 1) echo '<span class="pagination-dots">…</span>';
            echo "<button class=\"pagination-button\" onclick=\"loadCatalogPage({$totalPaginas})\">{$totalPaginas}</button>";
        }
        ?>

        <?php if ($pagina < $totalPaginas): ?>
            <button class="pagination-button" onclick="loadCatalogPage(<?php echo $pagina + 1; ?>)">Siguiente ›</button>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>