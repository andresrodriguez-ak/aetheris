<?php
$accent_color = 'manga';
$page_css     = ['manga.css'];

require_once __DIR__ . '/../config/db_config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$logged_in = isset($_SESSION['user_id']);
$username  = $logged_in ? $_SESSION['username'] : '';
$is_admin  = $logged_in && ($_SESSION['role'] ?? '') === 'admin';

$manga_id    = isset($_GET['manga_id']) ? (int)$_GET['manga_id'] : 0;
$capitulo_id = isset($_GET['cap']) ? (int)$_GET['cap'] : 0;
if (!$manga_id || !$capitulo_id) { header("Location: manga-home.php"); exit; }

$stmt_manga = $conn->prepare("SELECT id, nombre, imagen FROM mangas WHERE id = ?");
$stmt_manga->bind_param("i", $manga_id);
$stmt_manga->execute();
$manga = $stmt_manga->get_result()->fetch_assoc();
if (!$manga) { header("Location: manga-home.php"); exit; }

$stmt_cap = $conn->prepare("SELECT * FROM manga_capitulos WHERE id_manga = ? AND id = ?");
$stmt_cap->bind_param("ii", $manga_id, $capitulo_id);
$stmt_cap->execute();
$capitulo = $stmt_cap->get_result()->fetch_assoc();
if (!$capitulo || empty($capitulo['enlace_pdf'])) { header("Location: manga-detalle.php?id=$manga_id"); exit; }

function getDrivePreviewUrl($url) {
    if (preg_match('/file\/d\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return "https://drive.google.com/file/d/" . $matches[1] . "/preview";
    }
    return $url;
}
$preview_url = getDrivePreviewUrl($capitulo['enlace_pdf']);

$prev_stmt = $conn->prepare("SELECT id FROM manga_capitulos WHERE id_manga = ? AND capitulo_inicio < ? ORDER BY capitulo_inicio DESC LIMIT 1");
$prev_stmt->bind_param("id", $manga_id, $capitulo['capitulo_inicio']);
$prev_stmt->execute();
$prev_cap = $prev_stmt->get_result()->fetch_assoc()['id'] ?? null;

$next_stmt = $conn->prepare("SELECT id FROM manga_capitulos WHERE id_manga = ? AND capitulo_inicio > ? ORDER BY capitulo_inicio ASC LIMIT 1");
$next_stmt->bind_param("id", $manga_id, $capitulo['capitulo_inicio']);
$next_stmt->execute();
$next_cap = $next_stmt->get_result()->fetch_assoc()['id'] ?? null;

$stmt_all = $conn->prepare("SELECT id, capitulo_inicio, capitulo_fin, nombre_capitulo FROM manga_capitulos WHERE id_manga = ? ORDER BY capitulo_inicio ASC");
$stmt_all->bind_param("i", $manga_id);
$stmt_all->execute();
$all_chapters = $stmt_all->get_result();

$titulo_cap = $capitulo['capitulo_inicio'] == $capitulo['capitulo_fin']
    ? 'Cap. ' . $capitulo['capitulo_inicio']
    : 'Cap. ' . $capitulo['capitulo_inicio'] . '-' . $capitulo['capitulo_fin'];

$page_title = htmlspecialchars($manga['nombre']) . ' - ' . $titulo_cap;

require_once __DIR__ . '/../src/includes/header.php';
?>

<div class="lector-container">

    <div class="lector-header">
        <img src="<?php echo htmlspecialchars($manga['imagen'] ?? 'uploads/default.jpg'); ?>"
             alt="<?php echo htmlspecialchars($manga['nombre']); ?>" class="lector-thumb">
        <div>
            <h1><?php echo htmlspecialchars($manga['nombre']); ?></h1>
            <h2>Capítulo <?php echo $capitulo['capitulo_inicio'];
                if ($capitulo['capitulo_inicio'] != $capitulo['capitulo_fin']) {
                    echo " - " . $capitulo['capitulo_fin'];
                }
            ?></h2>
        </div>
    </div>

    <div class="lector-player">
        <div class="chapter-selector">
            <select onchange="location = this.value;">
                <option value="">Seleccionar capítulo...</option>
                <?php while ($ch = $all_chapters->fetch_assoc()): ?>
                    <option value="manga-lector.php?manga_id=<?php echo $manga_id; ?>&cap=<?php echo $ch['id']; ?>"
                        <?php echo ($ch['id'] == $capitulo_id) ? 'selected' : ''; ?>>
                        Capítulo <?php echo $ch['capitulo_inicio'];
                        if ($ch['capitulo_inicio'] != $ch['capitulo_fin']) echo " - " . $ch['capitulo_fin'];
                        if (!empty($ch['nombre_capitulo'])) echo " - " . htmlspecialchars($ch['nombre_capitulo']);
                        ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="pdf-container" id="pdfContainer">
            <button class="fullscreen-btn" id="fsBtn">⛶ Pantalla Completa</button>
            <iframe src="<?php echo htmlspecialchars($preview_url); ?>"
                    width="100%" height="720" allowfullscreen></iframe>
        </div>

        <div class="chapter-nav">
            <?php if ($prev_cap): ?>
                <a href="manga-lector.php?manga_id=<?php echo $manga_id; ?>&cap=<?php echo $prev_cap; ?>" class="nav-btn">
                    ← Capítulo Anterior
                </a>
            <?php else: ?>
                <span class="nav-btn disabled">← No hay anterior</span>
            <?php endif; ?>

            <?php if ($next_cap): ?>
                <a href="manga-lector.php?manga_id=<?php echo $manga_id; ?>&cap=<?php echo $next_cap; ?>" class="nav-btn">
                    Siguiente Capítulo →
                </a>
            <?php else: ?>
                <span class="nav-btn disabled">No hay siguiente →</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="chapter-info">
        <h2 class="chapter-title">
            <?php echo !empty($capitulo['nombre_capitulo'])
                ? htmlspecialchars($capitulo['nombre_capitulo'])
                : "Capítulo " . $capitulo['capitulo_inicio']; ?>
        </h2>
    </div>

    <a href="manga-detalle.php?id=<?php echo $manga_id; ?>" class="back-btn">
        ← Volver al manga
    </a>
</div>

<script src="js/main.js"></script>
<script src="js/manga-lector.js"></script>

<?php
require_once __DIR__ . '/../src/includes/footer.php';
$conn->close();
?>