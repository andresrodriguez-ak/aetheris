<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(0);

$accent_color = 'manga';
$page_css     = ['manga.css', 'action-buttons.css'];

require_once __DIR__ . '/../config/db_config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$logged_in = isset($_SESSION['user_id']);
$username  = $logged_in ? $_SESSION['username'] : '';
$is_admin  = $logged_in && ($_SESSION['role'] ?? '') === 'admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    ob_clean();
    header('Content-Type: application/json');

    if (!$logged_in) {
        echo json_encode(['success' => false, 'msg' => 'no_login']);
        exit;
    }

    $user_id  = (int)$_SESSION['user_id'];
    $manga_id = (int)($_POST['manga_id'] ?? 0);
    $action   = $_POST['action'];

    if ($action === 'toggle_leido') {
        $cap_id = (int)$_POST['capitulo_id'];
        $check  = $conn->prepare("SELECT id, visto FROM manga_vistos WHERE user_id=? AND manga_id=? AND capitulo_id=?");
        $check->bind_param("iii", $user_id, $manga_id, $cap_id);
        $check->execute();
        $row = $check->get_result()->fetch_assoc();
        if ($row) {
            $nuevo_visto = $row['visto'] ? 0 : 1;
            $upd = $conn->prepare("UPDATE manga_vistos SET visto=? WHERE id=?");
            $upd->bind_param("ii", $nuevo_visto, $row['id']);
            $upd->execute();
            echo json_encode(['success' => true, 'leido' => (bool)$nuevo_visto]);
        } else {
            $ins = $conn->prepare("INSERT INTO manga_vistos (user_id, manga_id, capitulo_id, visto) VALUES (?,?,?,1)");
            $ins->bind_param("iii", $user_id, $manga_id, $cap_id);
            $ins->execute();
            echo json_encode(['success' => true, 'leido' => true]);
        }
        exit;
    }

    echo json_encode(['success' => false, 'msg' => 'accion_invalida']);
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header("Location: manga-home.php"); exit; }

$stmt = $conn->prepare("SELECT * FROM mangas WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$manga = $stmt->get_result()->fetch_assoc();
if (!$manga) { header("Location: manga-home.php"); exit; }

$gq = $conn->prepare("SELECT g.id, g.nombre FROM generos g JOIN manga_generos mg ON g.id = mg.genero_id WHERE mg.manga_id = ? ORDER BY g.nombre");
$gq->bind_param("i", $id);
$gq->execute();
$generos = $gq->get_result()->fetch_all(MYSQLI_ASSOC);

$es_favorito   = false;
$estado_actual = '';
$leidos_set = [];

if ($logged_in) {
    $user_id  = (int)$_SESSION['user_id'];
    $manga_id = $id;

    $fq = $conn->prepare("SELECT es_favorito, estado_seguimiento FROM favoritos WHERE user_id=? AND manga_id=?");
    $fq->bind_param("ii", $user_id, $manga_id);
    $fq->execute();
    $frow = $fq->get_result()->fetch_assoc();
    if ($frow) {
        $es_favorito   = (bool)$frow['es_favorito'];
        $estado_actual = $frow['estado_seguimiento'] ?? '';
    }

    $lq = $conn->prepare("SELECT capitulo_id FROM manga_vistos WHERE user_id=? AND manga_id=? AND visto=1");
    $lq->bind_param("ii", $user_id, $manga_id);
    $lq->execute();
    $lres = $lq->get_result();
    while ($lrow = $lres->fetch_assoc()) $leidos_set[] = (int)$lrow['capitulo_id'];
}

$tq = $conn->prepare("SELECT COUNT(*) as total FROM manga_capitulos WHERE id_manga=?");
$tq->bind_param("i", $id);
$tq->execute();
$total_caps   = (int)$tq->get_result()->fetch_assoc()['total'];
$leidos_count = count($leidos_set);
$progreso_pct = $total_caps > 0 ? round(($leidos_count / $total_caps) * 100) : 0;

$page_title = htmlspecialchars($manga['nombre']);

require_once __DIR__ . '/../src/includes/header.php';
?>

<div class="manga-banner"
     style="background-image: url('<?php echo htmlspecialchars($manga['portada'] ?? ''); ?>')">
</div>

<div class="manga-detail-container">

    <div class="manga-detail-card">

        <div class="manga-poster">
            <img src="<?php echo htmlspecialchars($manga['imagen']); ?>"
                 alt="Portada de <?php echo htmlspecialchars($manga['nombre']); ?>">
        </div>

        <div class="manga-detail-info">

            <h1 class="manga-detail-title"><?php echo htmlspecialchars($manga['nombre']); ?></h1>

            <div class="manga-meta">
                <span class="meta-item estado"><?php echo htmlspecialchars($manga['estado'] ?? ''); ?></span>
                <?php foreach ($generos as $g): ?>
                    <a class="meta-item" href="directorio.php?type=manga&genre=<?php echo (int)$g['id']; ?>">
                        <?php echo htmlspecialchars($g['nombre']); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="manga-description">
                <h3>Sinopsis</h3>
                <p><?php echo nl2br(htmlspecialchars($manga['descripcion'])); ?></p>
            </div>

            <?php if ($logged_in): ?>

                <?php
                $tipo = 'manga';
                $content_id = $id;
                require __DIR__ . '/../src/includes/components/action_buttons.php';
                ?>

                <?php if ($total_caps > 0): ?>
                <div class="progress-section">
                    <div class="progress-label">
                        <span>Progreso</span>
                        <span id="progressText">
                            <?php echo $leidos_count; ?> / <?php echo $total_caps; ?> capítulos (<?php echo $progreso_pct; ?>%)
                        </span>
                    </div>
                    <div class="progress-bar-bg">
                        <div class="progress-bar-fill" id="progressBar"
                             style="width:<?php echo $progreso_pct; ?>%"></div>
                    </div>
                </div>
                <?php endif; ?>

            <?php else: ?>

                <div class="content-actions">
                    <a href="login.php" class="btn-favorito">
                        <svg class="fav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
                        </svg>
                        <span>Inicia sesión para guardar</span>
                    </a>
                </div>

            <?php endif; ?>

        </div>
    </div>

    <div class="chapters-section">
        <h2 class="section-title">Lista de Capítulos</h2>
        <div class="chapters-list">
            <?php
            $stmt_cp = $conn->prepare("SELECT id, nombre_capitulo, enlace_pdf, capitulo_inicio, capitulo_fin FROM manga_capitulos WHERE id_manga = ? ORDER BY capitulo_inicio DESC");
            $stmt_cp->bind_param("i", $id);
            $stmt_cp->execute();
            $capitulos = $stmt_cp->get_result();

            if ($capitulos->num_rows > 0):
                while ($cap = $capitulos->fetch_assoc()):
                    $cap_id     = (int)$cap['id'];
                    $leido      = in_array($cap_id, $leidos_set);
                    $titulo_cap = !empty(trim((string)($cap['nombre_capitulo'] ?? ''))) ? $cap['nombre_capitulo'] : $manga['nombre'];
                    $num_texto  = $cap['capitulo_inicio'] == $cap['capitulo_fin']
                        ? 'Capítulo ' . $cap['capitulo_inicio']
                        : 'Capítulos ' . $cap['capitulo_inicio'] . ' - ' . $cap['capitulo_fin'];
            ?>
                <div class="chapter-card <?php echo $leido ? 'leido-card' : ''; ?>"
                     id="cap-card-<?php echo $cap_id; ?>">
                    <div>
                        <div class="chapter-number"><?php echo htmlspecialchars($num_texto); ?></div>
                        <div class="chapter-title-text"><?php echo htmlspecialchars($titulo_cap); ?></div>
                    </div>
                    <div class="cap-right">
                        <?php if ($logged_in): ?>
                        <label class="switch-label">
                            <label class="switch">
                                <input type="checkbox" <?php echo $leido ? 'checked' : ''; ?>
                                       onchange="toggleLeido(this, <?php echo $cap_id; ?>)">
                                <span class="slider"></span>
                            </label>
                            Leído
                        </label>
                        <?php endif; ?>
                        <?php if (!empty($cap['enlace_pdf'])): ?>
                        <a href="manga-lector.php?manga_id=<?php echo $id; ?>&cap=<?php echo $cap_id; ?>"
                           class="read-btn">LEER</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php
                endwhile;
            else: ?>
                <p class="no-episodes">Próximamente más capítulos...</p>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
    window.MANGA_ID          = <?php echo (int)$id; ?>;
    window.TOTAL_CAPS        = <?php echo $total_caps; ?>;
    window.LEIDOS_COUNT      = <?php echo $leidos_count; ?>;
    window.MANGA_DETALLE_URL = 'manga-detalle.php?id=<?php echo (int)$id; ?>';
</script>
<script src="js/main.js"></script>
<script src="js/manga-detalle.js"></script>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?>