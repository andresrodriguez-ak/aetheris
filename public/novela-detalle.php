<?php
ob_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

$accent_color = 'novela';
$page_css     = ['novela.css', 'action-buttons.css'];

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

    $user_id   = (int)$_SESSION['user_id'];
    $novela_id = (int)($_POST['novela_id'] ?? 0);
    $action    = $_POST['action'];

    if ($action === 'toggle_leido') {
        $volumen_id = (int)($_POST['volumen_id'] ?? 0);

        $check = $conn->prepare("SELECT id FROM volumenes_leidos WHERE user_id=? AND volumen_id=?");
        $check->bind_param("ii", $user_id, $volumen_id);
        $check->execute();
        $existe = $check->get_result()->fetch_assoc();

        if ($existe) {
            $del = $conn->prepare("DELETE FROM volumenes_leidos WHERE user_id=? AND volumen_id=?");
            $del->bind_param("ii", $user_id, $volumen_id);
            $del->execute();
            echo json_encode(['success' => true, 'leido' => false]);
        } else {
            $ins = $conn->prepare("INSERT INTO volumenes_leidos (user_id, volumen_id) VALUES (?,?)");
            $ins->bind_param("ii", $user_id, $volumen_id);
            $ins->execute();
            echo json_encode(['success' => true, 'leido' => true]);
        }
        exit;
    }

    echo json_encode(['success' => false, 'msg' => 'accion_invalida']);
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header("Location: novela-home.php"); exit; }

$stmt = $conn->prepare("SELECT * FROM novelas WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$novela = $stmt->get_result()->fetch_assoc();
if (!$novela) { header("Location: novela-home.php"); exit; }

$gq = $conn->prepare("SELECT g.id, g.nombre FROM generos g JOIN novela_generos ng ON g.id = ng.genero_id WHERE ng.novela_id = ? ORDER BY g.nombre");
$gq->bind_param("i", $id);
$gq->execute();
$generos = $gq->get_result()->fetch_all(MYSQLI_ASSOC);

$es_favorito   = false;
$estado_actual = '';
$leidos_set = [];

if ($logged_in) {
    $user_id   = (int)$_SESSION['user_id'];
    $novela_id = $id;

    $fq = $conn->prepare("SELECT es_favorito, estado_seguimiento FROM favoritos WHERE user_id=? AND novela_id=?");
    $fq->bind_param("ii", $user_id, $novela_id);
    $fq->execute();
    $frow = $fq->get_result()->fetch_assoc();
    if ($frow) {
        $es_favorito   = (bool)$frow['es_favorito'];
        $estado_actual = $frow['estado_seguimiento'] ?? '';
    }

    $vq = $conn->prepare(
        "SELECT vl.volumen_id FROM volumenes_leidos vl
         JOIN volumenes v ON v.id_volumen = vl.volumen_id
         WHERE vl.user_id=? AND v.id_novela=?"
    );
    $vq->bind_param("ii", $user_id, $novela_id);
    $vq->execute();
    $vres = $vq->get_result();
    while ($vrow = $vres->fetch_assoc()) $leidos_set[] = (int)$vrow['volumen_id'];
}

$tq = $conn->prepare("SELECT COUNT(*) as total FROM volumenes WHERE id_novela=?");
$tq->bind_param("i", $id);
$tq->execute();
$total_volumenes = (int)$tq->get_result()->fetch_assoc()['total'];
$leidos_count    = count($leidos_set);
$progreso_pct    = $total_volumenes > 0 ? round(($leidos_count / $total_volumenes) * 100) : 0;

$page_title = htmlspecialchars($novela['nombre']);

require_once __DIR__ . '/../src/includes/header.php';
?>

<div class="novela-banner"
     style="background-image: url('<?php echo htmlspecialchars($novela['portada'] ?? ''); ?>')">
</div>

<div class="novela-detail-container">

    <div class="novela-detail-card">

        <div class="novela-poster">
            <img src="<?php echo htmlspecialchars($novela['imagen']); ?>"
                 alt="Portada de <?php echo htmlspecialchars($novela['nombre']); ?>">
        </div>

        <div class="novela-detail-info">

            <h1 class="novela-detail-title"><?php echo htmlspecialchars($novela['nombre']); ?></h1>

            <div class="novela-detail-genres">
                <span class="meta-item estado"><?php echo htmlspecialchars($novela['estado'] ?? ''); ?></span>
                <?php foreach ($generos as $g): ?>
                    <a class="meta-item" href="directorio.php?type=novela&genre=<?php echo (int)$g['id']; ?>">
                        <?php echo htmlspecialchars($g['nombre']); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="novela-synopsis">
                <h3>Sinopsis</h3>
                <p><?php echo nl2br(htmlspecialchars($novela['descripcion'])); ?></p>
            </div>

            <?php if ($logged_in): ?>

                <?php
                $tipo = 'novela';
                $content_id = $id;
                require __DIR__ . '/../src/includes/components/action_buttons.php';
                ?>

                <?php if ($total_volumenes > 0): ?>
                <div class="progress-section">
                    <div class="progress-label">
                        <span>Progreso</span>
                        <span id="progressText">
                            <?php echo $leidos_count; ?> / <?php echo $total_volumenes; ?> volúmenes (<?php echo $progreso_pct; ?>%)
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

    <div class="volumenes-section">
        <h2 class="section-title">Lista de Volúmenes</h2>
        <div class="volumenes-list">
            <?php
            $stmt_vol = $conn->prepare("SELECT * FROM volumenes WHERE id_novela = ? ORDER BY numero_volumen ASC");
            $stmt_vol->bind_param("i", $id);
            $stmt_vol->execute();
            $volumenes = $stmt_vol->get_result();

            if ($volumenes->num_rows > 0):
                while ($volumen = $volumenes->fetch_assoc()):
                    $vol_id  = (int)$volumen['id_volumen'];
                    $leido   = in_array($vol_id, $leidos_set);
                    $titulo  = !empty(trim($volumen['titulo'] ?? '')) ? $volumen['titulo'] : $novela['nombre'];
            ?>
                <div class="volumen-card <?php echo $leido ? 'leido-card' : ''; ?>"
                     id="vol-card-<?php echo $vol_id; ?>">
                    <div class="vol-info">
                        <span class="vol-number">Volumen <?php echo (int)$volumen['numero_volumen']; ?></span>
                        <small class="vol-title"><?php echo htmlspecialchars($titulo); ?></small>
                    </div>
                    <div class="vol-right">
                        <?php if ($logged_in): ?>
                        <label class="switch-label">
                            <label class="switch">
                                <input type="checkbox" <?php echo $leido ? 'checked' : ''; ?>
                                       onchange="toggleLeido(this, <?php echo $vol_id; ?>)">
                                <span class="slider"></span>
                            </label>
                            Leído
                        </label>
                        <?php endif; ?>
                        <a href="novela-lector.php?novela_id=<?php echo $id; ?>&vol=<?php echo $vol_id; ?>"
                           class="read-btn">LEER</a>
                    </div>
                </div>
            <?php
                endwhile;
            else: ?>
                <p class="no-volumenes">Próximamente más volúmenes...</p>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
    window.NOVELA_ID          = <?php echo (int)$id; ?>;
    window.TOTAL_VOLS         = <?php echo $total_volumenes; ?>;
    window.LEIDOS_COUNT       = <?php echo $leidos_count; ?>;
    window.NOVELA_DETALLE_URL = 'novela-detalle.php?id=<?php echo (int)$id; ?>';
</script>
<script src="js/main.js"></script>
<script src="js/novela-detalle.js"></script>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?>