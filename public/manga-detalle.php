<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(0);

$accent_color = 'manga';
$page_css     = ['manga.css'];

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

    if ($action === 'toggle_favorito') {
        $check = $conn->prepare("SELECT id, es_favorito, estado FROM favoritos WHERE user_id=? AND manga_id=? AND anime_id IS NULL AND novela_id IS NULL LIMIT 1");
        $check->bind_param("ii", $user_id, $manga_id);
        $check->execute();
        $row = $check->get_result()->fetch_assoc();
        if ($row) {
            $nuevo = $row['es_favorito'] ? 0 : 1;
            if ($nuevo === 0 && empty($row['estado'])) {
                $del = $conn->prepare("DELETE FROM favoritos WHERE id=?");
                $del->bind_param("i", $row['id']);
                $del->execute();
            } else {
                $upd = $conn->prepare("UPDATE favoritos SET es_favorito=? WHERE id=?");
                $upd->bind_param("ii", $nuevo, $row['id']);
                $upd->execute();
            }
            echo json_encode(['success' => true, 'active' => (bool)$nuevo]);
        } else {
            $ins = $conn->prepare("INSERT INTO favoritos (user_id, manga_id, anime_id, novela_id, estado, es_favorito, siguiendo, estado_seguimiento, created_at) VALUES (?, ?, NULL, NULL, '', 1, 0, '', NOW())");
            $ins->bind_param("ii", $user_id, $manga_id);
            $ins->execute();
            echo json_encode(['success' => true, 'active' => true]);
        }
        exit;
    }

    if ($action === 'toggle_siguiendo') {
        $check = $conn->prepare("SELECT id, es_favorito, estado FROM favoritos WHERE user_id=? AND manga_id=? AND anime_id IS NULL AND novela_id IS NULL LIMIT 1");
        $check->bind_param("ii", $user_id, $manga_id);
        $check->execute();
        $row = $check->get_result()->fetch_assoc();
        if ($row) {
            if ($row['estado'] === 'viendo') {
                if (!$row['es_favorito']) {
                    $del = $conn->prepare("DELETE FROM favoritos WHERE id=?");
                    $del->bind_param("i", $row['id']);
                    $del->execute();
                } else {
                    $upd = $conn->prepare("UPDATE favoritos SET estado='' WHERE id=?");
                    $upd->bind_param("i", $row['id']);
                    $upd->execute();
                }
                echo json_encode(['success' => true, 'active' => false]);
            } else {
                $upd = $conn->prepare("UPDATE favoritos SET estado='viendo' WHERE id=?");
                $upd->bind_param("i", $row['id']);
                $upd->execute();
                echo json_encode(['success' => true, 'active' => true]);
            }
        } else {
            $ins = $conn->prepare("INSERT INTO favoritos (user_id, manga_id, anime_id, novela_id, estado, es_favorito, siguiendo, estado_seguimiento, created_at) VALUES (?, ?, NULL, NULL, 'viendo', 0, 0, '', NOW())");
            $ins->bind_param("ii", $user_id, $manga_id);
            $ins->execute();
            echo json_encode(['success' => true, 'active' => true]);
        }
        exit;
    }

    if ($action === 'toggle_ver_mas_tarde') {
        $check = $conn->prepare("SELECT id, es_favorito, estado FROM favoritos WHERE user_id=? AND manga_id=? AND anime_id IS NULL AND novela_id IS NULL LIMIT 1");
        $check->bind_param("ii", $user_id, $manga_id);
        $check->execute();
        $row = $check->get_result()->fetch_assoc();
        if ($row) {
            if ($row['estado'] === 'espera') {
                if (!$row['es_favorito']) {
                    $del = $conn->prepare("DELETE FROM favoritos WHERE id=?");
                    $del->bind_param("i", $row['id']);
                    $del->execute();
                } else {
                    $upd = $conn->prepare("UPDATE favoritos SET estado='' WHERE id=?");
                    $upd->bind_param("i", $row['id']);
                    $upd->execute();
                }
                echo json_encode(['success' => true, 'active' => false]);
            } else {
                $upd = $conn->prepare("UPDATE favoritos SET estado='espera' WHERE id=?");
                $upd->bind_param("i", $row['id']);
                $upd->execute();
                echo json_encode(['success' => true, 'active' => true]);
            }
        } else {
            $ins = $conn->prepare("INSERT INTO favoritos (user_id, manga_id, anime_id, novela_id, estado, es_favorito, siguiendo, estado_seguimiento, created_at) VALUES (?, ?, NULL, NULL, 'espera', 0, 0, '', NOW())");
            $ins->bind_param("ii", $user_id, $manga_id);
            $ins->execute();
            echo json_encode(['success' => true, 'active' => true]);
        }
        exit;
    }

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

$fav_activo = $sig_activo = $vmt_activo = false;
$leidos_set = [];

if ($logged_in) {
    $user_id  = (int)$_SESSION['user_id'];
    $manga_id = $id;

    $fq = $conn->prepare("SELECT es_favorito, estado FROM favoritos WHERE user_id=? AND manga_id=? AND anime_id IS NULL AND novela_id IS NULL");
    $fq->bind_param("ii", $user_id, $manga_id);
    $fq->execute();
    $frow = $fq->get_result()->fetch_assoc();
    if ($frow) {
        $fav_activo = (bool)$frow['es_favorito'];
        $sig_activo = $frow['estado'] === 'viendo';
        $vmt_activo = $frow['estado'] === 'espera';
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

                <div class="action-buttons">
                    <button class="btn-action <?php echo $fav_activo ? 'active-fav' : ''; ?>"
                            id="btnFav" onclick="toggleAccion('toggle_favorito','btnFav')">
                        <img class="btn-icon" id="iconFav"
                             src="uploads/content/<?php echo $fav_activo ? 'icon_fav_on.png' : 'icon_fav_off.png'; ?>"
                             alt="">
                        <span id="lblFav"><?php echo $fav_activo ? 'En favoritos' : 'Favorito'; ?></span>
                    </button>

                    <button class="btn-action <?php echo $sig_activo ? 'active-sig' : ''; ?>"
                            id="btnSig" onclick="toggleAccion('toggle_siguiendo','btnSig')">
                        <img class="btn-icon" id="iconSig"
                             src="uploads/content/<?php echo $sig_activo ? 'icon_sig_on.png' : 'icon_sig_off.png'; ?>"
                             alt="">
                        <span id="lblSig"><?php echo $sig_activo ? 'Leyendo' : 'Seguir'; ?></span>
                    </button>

                    <button class="btn-action <?php echo $vmt_activo ? 'active-vmt' : ''; ?>"
                            id="btnVmt" onclick="toggleAccion('toggle_ver_mas_tarde','btnVmt')">
                        <img class="btn-icon" id="iconVmt"
                             src="uploads/content/<?php echo $vmt_activo ? 'icon_vmt_on.png' : 'icon_vmt_off.png'; ?>"
                             alt="">
                        <span id="lblVmt"><?php echo $vmt_activo ? 'Guardado' : 'Ver más tarde'; ?></span>
                    </button>
                </div>

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

                <div class="action-buttons">
                    <a href="login.php" class="btn-action">
                        <img class="btn-icon" src="uploads/content/icon_fav_off.png" alt="">
                        Inicia sesión para guardar
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
                    $titulo_cap = !empty(trim($cap['nombre_capitulo'])) ? $cap['nombre_capitulo'] : $manga['nombre'];
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