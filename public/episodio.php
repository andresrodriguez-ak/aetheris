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

$anime_id    = isset($_GET['anime_id']) ? (int)$_GET['anime_id'] : 0;
$episodio_num = isset($_GET['ep'])      ? (int)$_GET['ep']       : 0;

if (!$anime_id || !$episodio_num) { header("Location: anime-home.php"); exit; }

$stmt_anime = $conn->prepare("SELECT id, nombre, imagen FROM animes WHERE id = ?");
$stmt_anime->bind_param("i", $anime_id);
$stmt_anime->execute();
$anime = $stmt_anime->get_result()->fetch_assoc();
if (!$anime) { header("Location: anime-home.php"); exit; }

$stmt_ep = $conn->prepare("SELECT * FROM episodios WHERE id_anime = ? AND numero_episodio = ?");
$stmt_ep->bind_param("ii", $anime_id, $episodio_num);
$stmt_ep->execute();
$episodio = $stmt_ep->get_result()->fetch_assoc();
if (!$episodio) { header("Location: anime-detalle.php?id=" . $anime_id); exit; }

$stmt_prev = $conn->prepare("SELECT MAX(numero_episodio) FROM episodios WHERE id_anime = ? AND numero_episodio < ?");
$stmt_prev->bind_param("ii", $anime_id, $episodio_num);
$stmt_prev->execute();
$prev_ep = $stmt_prev->get_result()->fetch_row()[0];

$stmt_next = $conn->prepare("SELECT MIN(numero_episodio) FROM episodios WHERE id_anime = ? AND numero_episodio > ?");
$stmt_next->bind_param("ii", $anime_id, $episodio_num);
$stmt_next->execute();
$next_ep = $stmt_next->get_result()->fetch_row()[0];

$stmt_all = $conn->prepare("SELECT numero_episodio, titulo FROM episodios WHERE id_anime = ? ORDER BY numero_episodio");
$stmt_all->bind_param("i", $anime_id);
$stmt_all->execute();
$all_episodes = $stmt_all->get_result();

$page_title = htmlspecialchars($anime['nombre']) . ' — Ep. ' . $episodio_num;

require_once __DIR__ . '/../src/includes/header.php';
?>

<div class="ep-container">

    <div class="player-header">
        <img src="<?php echo htmlspecialchars($anime['imagen']); ?>"
             alt="Portada de <?php echo htmlspecialchars($anime['nombre']); ?>"
             class="anime-thumb">
        <div>
            <h1><?php echo htmlspecialchars($anime['nombre']); ?></h1>
            <h2>Episodio <?php echo $episodio_num; ?></h2>
        </div>
    </div>

    <div class="player-container">

        <div class="episode-selector">
            <select onchange="location = this.value;">
                <option value="">Cambiar episodio...</option>
                <?php while ($ep = $all_episodes->fetch_assoc()): ?>
                    <option value="episodio.php?anime_id=<?php echo $anime_id; ?>&ep=<?php echo $ep['numero_episodio']; ?>"
                            <?php echo ($ep['numero_episodio'] == $episodio_num) ? 'selected' : ''; ?>>
                        Ep. <?php echo $ep['numero_episodio']; ?> — <?php echo htmlspecialchars($ep['titulo'] ?? 'Sin título'); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="video-container">
            <?php echo htmlspecialchars_decode($episodio['embed_code']); ?>
        </div>

        <div class="episode-nav">
            <?php if ($prev_ep): ?>
                <a href="episodio.php?anime_id=<?php echo $anime_id; ?>&ep=<?php echo $prev_ep; ?>"
                   class="nav-btn">« ANTERIOR</a>
            <?php else: ?>
                <span class="nav-btn disabled">« ANTERIOR</span>
            <?php endif; ?>

            <?php if ($next_ep): ?>
                <a href="episodio.php?anime_id=<?php echo $anime_id; ?>&ep=<?php echo $next_ep; ?>"
                   class="nav-btn">SIGUIENTE »</a>
            <?php else: ?>
                <span class="nav-btn disabled">SIGUIENTE »</span>
            <?php endif; ?>
        </div>

    </div>

    <div class="episode-info">
        <h3>Detalles del Episodio</h3>
        <p><?php echo !empty($episodio['titulo'])
            ? htmlspecialchars($episodio['titulo'])
            : 'Sin título específico para este episodio.'; ?>
        </p>
    </div>

    <a href="anime-detalle.php?id=<?php echo $anime_id; ?>" class="back-btn">
        ← LISTA DE EPISODIOS
    </a>

</div>

<script src="js/main.js"></script>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?>