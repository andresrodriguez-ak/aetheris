<?php
$accent_color = 'novela';
$page_css     = ['novela.css'];

require_once __DIR__ . '/../config/db_config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$logged_in = isset($_SESSION['user_id']);
$username  = $logged_in ? $_SESSION['username'] : '';
$is_admin  = $logged_in && ($_SESSION['role'] ?? '') === 'admin';

$novela_id  = isset($_GET['novela_id']) ? (int)$_GET['novela_id'] : 0;
$volumen_id = isset($_GET['vol'])       ? (int)$_GET['vol']       : 0;

if (!$novela_id || !$volumen_id) { header("Location: novela-home.php"); exit; }

$stmt_nov = $conn->prepare("SELECT id, nombre, imagen FROM novelas WHERE id = ?");
$stmt_nov->bind_param("i", $novela_id);
$stmt_nov->execute();
$novela = $stmt_nov->get_result()->fetch_assoc();
if (!$novela) { header("Location: novela-home.php"); exit; }

$stmt_vol = $conn->prepare("SELECT * FROM volumenes WHERE id_novela = ? AND id_volumen = ?");
$stmt_vol->bind_param("ii", $novela_id, $volumen_id);
$stmt_vol->execute();
$volumen = $stmt_vol->get_result()->fetch_assoc();
if (!$volumen || empty($volumen['ruta_volumen'])) { header("Location: novela-detalle.php?id=" . $novela_id); exit; }

function getDrivePreviewUrl(string $url): string {
    if (preg_match('/file\/d\/([a-zA-Z0-9_-]+)/', $url, $m))
        return "https://drive.google.com/file/d/{$m[1]}/preview";
    return $url;
}
$preview_url = getDrivePreviewUrl($volumen['ruta_volumen']);

$pq = $conn->prepare(
    "SELECT id_volumen FROM volumenes
     WHERE id_novela = ? AND numero_volumen < ?
     ORDER BY numero_volumen DESC LIMIT 1"
);
$pq->bind_param("ii", $novela_id, $volumen['numero_volumen']);
$pq->execute();
$prev_vol = $pq->get_result()->fetch_assoc()['id_volumen'] ?? null;

$nq = $conn->prepare(
    "SELECT id_volumen FROM volumenes
     WHERE id_novela = ? AND numero_volumen > ?
     ORDER BY numero_volumen ASC LIMIT 1"
);
$nq->bind_param("ii", $novela_id, $volumen['numero_volumen']);
$nq->execute();
$next_vol = $nq->get_result()->fetch_assoc()['id_volumen'] ?? null;

$aq = $conn->prepare(
    "SELECT id_volumen, numero_volumen FROM volumenes
     WHERE id_novela = ? ORDER BY numero_volumen ASC"
);
$aq->bind_param("i", $novela_id);
$aq->execute();
$all_volumes = $aq->get_result()->fetch_all(MYSQLI_ASSOC);

$page_title = htmlspecialchars($novela['nombre']) . ' — Vol. ' . (int)$volumen['numero_volumen'];

require_once __DIR__ . '/../src/includes/header.php';
?>

<div class="lector-container">

    <div class="reader-header">
        <img src="<?php echo htmlspecialchars($novela['imagen'] ?? 'uploads/default.jpg'); ?>"
             alt="<?php echo htmlspecialchars($novela['nombre']); ?>" class="novel-thumb">
        <div class="reader-header-info">
            <h1><?php echo htmlspecialchars($novela['nombre']); ?></h1>
            <h2>Volumen <?php echo (int)$volumen['numero_volumen']; ?></h2>
        </div>
    </div>

    <div class="player-wrap">

        <div class="volume-selector">
            <select onchange="location=this.value;">
                <option value="">Seleccionar volumen...</option>
                <?php foreach ($all_volumes as $v): ?>
                    <option value="novela-lector.php?novela_id=<?php echo $novela_id; ?>&vol=<?php echo $v['id_volumen']; ?>"
                        <?php echo ($v['id_volumen'] == $volumen_id) ? 'selected' : ''; ?>>
                        Volumen <?php echo (int)$v['numero_volumen']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="pdf-wrapper" id="pdfContainer">
            <button class="fullscreen-btn" onclick="toggleFullscreen()">⛶ Pantalla Completa</button>
            <iframe src="<?php echo htmlspecialchars($preview_url); ?>" allowfullscreen allow="autoplay"></iframe>
        </div>

        <div class="chapter-nav">
            <?php if ($prev_vol): ?>
                <a href="novela-lector.php?novela_id=<?php echo $novela_id; ?>&vol=<?php echo $prev_vol; ?>" class="nav-btn">← Volumen Anterior</a>
            <?php else: ?>
                <span class="nav-btn disabled">← No hay anterior</span>
            <?php endif; ?>
            <?php if ($next_vol): ?>
                <a href="novela-lector.php?novela_id=<?php echo $novela_id; ?>&vol=<?php echo $next_vol; ?>" class="nav-btn">Siguiente Volumen →</a>
            <?php else: ?>
                <span class="nav-btn disabled">No hay siguiente →</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="volume-info">
        <h2>Volumen <?php echo (int)$volumen['numero_volumen']; ?></h2>
    </div>

    <a href="novela-detalle.php?id=<?php echo $novela_id; ?>" class="back-btn">← Volver a la novela</a>

</div>

<script src="js/novela-lector.js"></script>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?>