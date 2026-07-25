<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(0);

$accent_color = 'anime';
$page_css     = ['anime.css', 'action-buttons.css'];

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

$es_favorito   = false;
$estado_actual = '';
$vistos_set = [];

if ($logged_in) {
    $user_id  = (int)$_SESSION['user_id'];
    $anime_id = $id;

    $fq = $conn->prepare("SELECT es_favorito, estado_seguimiento FROM favoritos WHERE user_id=? AND anime_id=?");
    $fq->bind_param("ii", $user_id, $anime_id);
    $fq->execute();
    $frow = $fq->get_result()->fetch_assoc();
    if ($frow) {
        $es_favorito   = (bool)$frow['es_favorito'];
        $estado_actual = $frow['estado_seguimiento'] ?? '';
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

                <?php
                $tipo = 'anime';
                $content_id = $id;
                require __DIR__ . '/../src/includes/components/action_buttons.php';
                ?>

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
                    $titulo_ep = !empty(trim((string)($epi['titulo'] ?? ''))) ? $epi['titulo'] : $anime['nombre'];
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