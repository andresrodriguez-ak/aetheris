<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(0);

$accent_color = 'anime';
$page_css     = ['anime.css'];

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
    $anime_id = (int)($_POST['anime_id'] ?? 0);
    $action   = $_POST['action'];

    if ($action === 'toggle_favorito') {
        $check = $conn->prepare("SELECT es_favorito, estado_seguimiento FROM favoritos WHERE user_id=? AND anime_id=?");
        $check->bind_param("ii", $user_id, $anime_id);
        $check->execute();
        $row = $check->get_result()->fetch_assoc();
        if ($row) {
            $nuevo = $row['es_favorito'] ? 0 : 1;
            if ($nuevo === 0 && empty($row['estado_seguimiento'])) {
                $del = $conn->prepare("DELETE FROM favoritos WHERE user_id=? AND anime_id=?");
                $del->bind_param("ii", $user_id, $anime_id);
                $del->execute();
            } else {
                $upd = $conn->prepare("UPDATE favoritos SET es_favorito=? WHERE user_id=? AND anime_id=?");
                $upd->bind_param("iii", $nuevo, $user_id, $anime_id);
                $upd->execute();
            }
            echo json_encode(['success' => true, 'active' => (bool)$nuevo]);
        } else {
            $ins = $conn->prepare("INSERT INTO favoritos (user_id, anime_id, es_favorito, estado_seguimiento) VALUES (?,?,1,'')");
            $ins->bind_param("ii", $user_id, $anime_id);
            $ins->execute();
            echo json_encode(['success' => true, 'active' => true]);
        }
        exit;
    }

    if ($action === 'toggle_siguiendo') {
        $check = $conn->prepare("SELECT es_favorito, estado_seguimiento FROM favoritos WHERE user_id=? AND anime_id=?");
        $check->bind_param("ii", $user_id, $anime_id);
        $check->execute();
        $row = $check->get_result()->fetch_assoc();
        if ($row) {
            if ($row['estado_seguimiento'] === 'viendo') {
                if (!$row['es_favorito']) {
                    $del = $conn->prepare("DELETE FROM favoritos WHERE user_id=? AND anime_id=?");
                    $del->bind_param("ii", $user_id, $anime_id);
                    $del->execute();
                } else {
                    $upd = $conn->prepare("UPDATE favoritos SET estado_seguimiento='' WHERE user_id=? AND anime_id=?");
                    $upd->bind_param("ii", $user_id, $anime_id);
                    $upd->execute();
                }
                echo json_encode(['success' => true, 'active' => false]);
            } else {
                $upd = $conn->prepare("UPDATE favoritos SET estado_seguimiento='viendo' WHERE user_id=? AND anime_id=?");
                $upd->bind_param("ii", $user_id, $anime_id);
                $upd->execute();
                echo json_encode(['success' => true, 'active' => true]);
            }
        } else {
            $ins = $conn->prepare("INSERT INTO favoritos (user_id, anime_id, es_favorito, estado_seguimiento) VALUES (?,?,0,'viendo')");
            $ins->bind_param("ii", $user_id, $anime_id);
            $ins->execute();
            echo json_encode(['success' => true, 'active' => true]);
        }
        exit;
    }

    if ($action === 'toggle_ver_mas_tarde') {
        $check = $conn->prepare("SELECT es_favorito, estado_seguimiento FROM favoritos WHERE user_id=? AND anime_id=?");
        $check->bind_param("ii", $user_id, $anime_id);
        $check->execute();
        $row = $check->get_result()->fetch_assoc();
        if ($row) {
            if ($row['estado_seguimiento'] === 'espera') {
                if (!$row['es_favorito']) {
                    $del = $conn->prepare("DELETE FROM favoritos WHERE user_id=? AND anime_id=?");
                    $del->bind_param("ii", $user_id, $anime_id);
                    $del->execute();
                } else {
                    $upd = $conn->prepare("UPDATE favoritos SET estado_seguimiento='' WHERE user_id=? AND anime_id=?");
                    $upd->bind_param("ii", $user_id, $anime_id);
                    $upd->execute();
                }
                echo json_encode(['success' => true, 'active' => false]);
            } else {
                $upd = $conn->prepare("UPDATE favoritos SET estado_seguimiento='espera' WHERE user_id=? AND anime_id=?");
                $upd->bind_param("ii", $user_id, $anime_id);
                $upd->execute();
                echo json_encode(['success' => true, 'active' => true]);
            }
        } else {
            $ins = $conn->prepare("INSERT INTO favoritos (user_id, anime_id, es_favorito, estado_seguimiento) VALUES (?,?,0,'espera')");
            $ins->bind_param("ii", $user_id, $anime_id);
            $ins->execute();
            echo json_encode(['success' => true, 'active' => true]);
        }
        exit;
    }

    if ($action === 'toggle_visto') {
        $num_ep = (int)$_POST['numero_episodio'];
        $check  = $conn->prepare("SELECT id FROM episodios_vistos WHERE user_id=? AND anime_id=? AND numero_episodio=?");
        $check->bind_param("iii", $user_id, $anime_id, $num_ep);
        $check->execute();
        if ($check->get_result()->fetch_assoc()) {
            $del = $conn->prepare("DELETE FROM episodios_vistos WHERE user_id=? AND anime_id=? AND numero_episodio=?");
            $del->bind_param("iii", $user_id, $anime_id, $num_ep);
            $del->execute();
            echo json_encode(['success' => true, 'visto' => false]);
        } else {
            $ins = $conn->prepare("INSERT INTO episodios_vistos (user_id, anime_id, numero_episodio) VALUES (?,?,?)");
            $ins->bind_param("iii", $user_id, $anime_id, $num_ep);
            $ins->execute();
            echo json_encode(['success' => true, 'visto' => true]);
        }
        exit;
    }

    echo json_encode(['success' => false, 'msg' => 'accion_invalida']);
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header("Location: anime-home.php"); exit; }

$stmt = $conn->prepare("SELECT * FROM animes WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$anime = $stmt->get_result()->fetch_assoc();
if (!$anime) { header("Location: anime-home.php"); exit; }

$gq = $conn->prepare("SELECT g.id, g.nombre FROM generos g JOIN anime_generos ag ON g.id = ag.genero_id WHERE ag.anime_id = ? ORDER BY g.nombre");
$gq->bind_param("i", $id);
$gq->execute();
$generos = $gq->get_result()->fetch_all(MYSQLI_ASSOC);

$fav_activo = $sig_activo = $vmt_activo = false;
$vistos_set = [];

if ($logged_in) {
    $user_id  = (int)$_SESSION['user_id'];
    $anime_id = $id;

    $fq = $conn->prepare("SELECT es_favorito, estado_seguimiento FROM favoritos WHERE user_id=? AND anime_id=?");
    $fq->bind_param("ii", $user_id, $anime_id);
    $fq->execute();
    $frow = $fq->get_result()->fetch_assoc();
    if ($frow) {
        $fav_activo = (bool)$frow['es_favorito'];
        $sig_activo = $frow['estado_seguimiento'] === 'viendo';
        $vmt_activo = $frow['estado_seguimiento'] === 'espera';
    }

    $vq = $conn->prepare("SELECT numero_episodio FROM episodios_vistos WHERE user_id=? AND anime_id=?");
    $vq->bind_param("ii", $user_id, $anime_id);
    $vq->execute();
    $vres = $vq->get_result();
    while ($vrow = $vres->fetch_assoc()) $vistos_set[] = (int)$vrow['numero_episodio'];
}

$tq = $conn->prepare("SELECT COUNT(*) as total FROM episodios WHERE id_anime=?");
$tq->bind_param("i", $id);
$tq->execute();
$total_episodios = (int)$tq->get_result()->fetch_assoc()['total'];
$vistos_count    = count($vistos_set);
$progreso_pct    = $total_episodios > 0 ? round(($vistos_count / $total_episodios) * 100) : 0;

$page_title = htmlspecialchars($anime['nombre']);

require_once __DIR__ . '/../src/includes/header.php';
?>

<div class="anime-banner"
     style="background-image: url('<?php echo htmlspecialchars($anime['portada'] ?? ''); ?>')">
</div>

<div class="anime-container">

    <div class="anime-detail-card">

        <div class="anime-poster">
            <img src="<?php echo htmlspecialchars($anime['imagen']); ?>"
                 alt="Poster de <?php echo htmlspecialchars($anime['nombre']); ?>">
        </div>

        <div class="anime-detail-info">

            <h1 class="anime-detail-title"><?php echo htmlspecialchars($anime['nombre']); ?></h1>

            <div class="anime-meta">
                <span class="meta-item estado"><?php echo htmlspecialchars($anime['estado'] ?? ''); ?></span>
                <?php foreach ($generos as $g): ?>
                    <a class="meta-item" href="directorio.php?type=anime&genre=<?php echo (int)$g['id']; ?>">
                        <?php echo htmlspecialchars($g['nombre']); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="anime-synopsis">
                <h3>Sinopsis</h3>
                <p><?php echo nl2br(htmlspecialchars($anime['descripcion'])); ?></p>
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
                        <span id="lblSig"><?php echo $sig_activo ? 'Siguiendo' : 'Seguir'; ?></span>
                    </button>

                    <button class="btn-action <?php echo $vmt_activo ? 'active-vmt' : ''; ?>"
                            id="btnVmt" onclick="toggleAccion('toggle_ver_mas_tarde','btnVmt')">
                        <img class="btn-icon" id="iconVmt"
                             src="uploads/content/<?php echo $vmt_activo ? 'icon_vmt_on.png' : 'icon_vmt_off.png'; ?>"
                             alt="">
                        <span id="lblVmt"><?php echo $vmt_activo ? 'Guardado' : 'Ver más tarde'; ?></span>
                    </button>
                </div>

                <?php if ($total_episodios > 0): ?>
                <div class="progress-section">
                    <div class="progress-label">
                        <span>Progreso</span>
                        <span id="progressText">
                            <?php echo $vistos_count; ?> / <?php echo $total_episodios; ?> episodios (<?php echo $progreso_pct; ?>%)
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

    <div class="episodes-section">
        <h2 class="section-title">Lista de Episodios</h2>
        <div class="episodes-list">
            <?php
            $stmt_ep = $conn->prepare("SELECT * FROM episodios WHERE id_anime = ? ORDER BY numero_episodio DESC");
            $stmt_ep->bind_param("i", $id);
            $stmt_ep->execute();
            $episodios = $stmt_ep->get_result();

            if ($episodios->num_rows > 0):
                while ($epi = $episodios->fetch_assoc()):
                    $num       = (int)$epi['numero_episodio'];
                    $visto     = in_array($num, $vistos_set);
                    $titulo_ep = !empty(trim($epi['titulo'])) ? $epi['titulo'] : $anime['nombre'];
            ?>
                <div class="episode-card <?php echo $visto ? 'visto-card' : ''; ?>"
                     id="ep-card-<?php echo $num; ?>">
                    <div class="ep-info">
                        <span class="ep-number">Episodio <?php echo $num; ?></span>
                        <small class="ep-title"><?php echo htmlspecialchars($titulo_ep); ?></small>
                    </div>
                    <div class="ep-right">
                        <?php if ($logged_in): ?>
                        <label class="switch-label">
                            <label class="switch">
                                <input type="checkbox" <?php echo $visto ? 'checked' : ''; ?>
                                       onchange="toggleVisto(this, <?php echo $num; ?>)">
                                <span class="slider"></span>
                            </label>
                            Visto
                        </label>
                        <?php endif; ?>
                        <a href="episodio.php?anime_id=<?php echo $id; ?>&ep=<?php echo $num; ?>"
                           class="watch-btn">VER</a>
                    </div>
                </div>
            <?php
                endwhile;
            else: ?>
                <p class="no-episodes">Próximamente más episodios...</p>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
    window.ANIME_ID          = <?php echo (int)$id; ?>;
    window.TOTAL_EPS         = <?php echo $total_episodios; ?>;
    window.VISTOS_COUNT      = <?php echo $vistos_count; ?>;
    window.ANIME_DETALLE_URL = 'anime-detalle.php?id=<?php echo (int)$id; ?>';
</script>
<script src="js/main.js"></script>
<script src="js/anime-detalle.js"></script>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?>