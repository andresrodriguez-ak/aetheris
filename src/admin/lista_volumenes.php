<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/app_config.php';
require_once __DIR__ . '/../../config/db_config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: " . BASE_URL . "public/index.php");
    exit();
}

$id_novela_seleccionada = $_GET['id_novela'] ?? null;
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_link'])) {
    $id_vol = $_POST['id_volumen'];
    $nuevo_enlace = trim($_POST['ruta_volumen']);

    if (empty($id_vol) || empty($nuevo_enlace)) {
        $mensaje = "<div class='alert alert-error'>Los campos son obligatorios.</div>";
    } elseif (strpos($nuevo_enlace, 'drive.google.com') === false) {
        $mensaje = "<div class='alert alert-error'>El enlace debe ser de Google Drive.</div>";
    } else {
        $stmt_upd = $conn->prepare("UPDATE volumenes SET ruta_volumen = ? WHERE id_volumen = ?");
        $stmt_upd->bind_param("si", $nuevo_enlace, $id_vol);

        if ($stmt_upd->execute()) {
            $mensaje = "<div class='alert alert-success'>Enlace del volumen #" . htmlspecialchars($id_vol) . " actualizado con éxito.</div>";
        } else {
            $mensaje = "<div class='alert alert-error'>Error al actualizar: " . $conn->error . "</div>";
        }
    }
}

$novelas_result = $conn->query("SELECT id, nombre, imagen FROM novelas ORDER BY nombre");
$novelas = $novelas_result->fetch_all(MYSQLI_ASSOC);

$novela_seleccionada_nombre = null;
foreach ($novelas as $n) {
    if ($n['id'] == $id_novela_seleccionada) {
        $novela_seleccionada_nombre = $n['nombre'];
        break;
    }
}

$volumenes = null;
if ($id_novela_seleccionada) {
    $stmt_vol = $conn->prepare("SELECT id_volumen, numero_volumen, ruta_volumen FROM volumenes WHERE id_novela = ? ORDER BY numero_volumen DESC");
    $stmt_vol->bind_param("i", $id_novela_seleccionada);
    $stmt_vol->execute();
    $volumenes = $stmt_vol->get_result();
}

$page_title   = 'Editor de Volúmenes';
$accent_color = 'novela';
$admin_css    = ['lista_contenido.css'];
$admin_js     = ['lista_contenido.js'];

require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-panel">
    <div class="editor-header">
    <h1 class="admin-title">Editor de Volúmenes</h1>
</div>

    <?php echo $mensaje; ?>

    <div class="content-select" id="contentSelect">
        <button type="button" class="content-select-btn" id="contentSelectBtn">
            <span><?php echo $novela_seleccionada_nombre ? htmlspecialchars($novela_seleccionada_nombre) : 'Selecciona una novela...'; ?></span>
            <span class="arrow">▼</span>
        </button>
        <div class="content-select-panel" id="contentSelectPanel">
            <div class="content-select-search">
                <input type="text" id="content-filter" placeholder="Buscar novela..." autocomplete="off">
            </div>
            <div class="content-select-list" id="contentGrid">
                <?php if (empty($novelas)): ?>
                    <div class="content-option-empty">No hay novelas cargadas todavía.</div>
                <?php endif; ?>
                <?php foreach ($novelas as $novela): ?>
                    <?php $img_path = !empty($novela['imagen']) ? $novela['imagen'] : DEFAULT_CONTENT_IMG; ?>
                    <a href="?id_novela=<?php echo $novela['id']; ?>"
                       class="content-option <?php echo ($id_novela_seleccionada == $novela['id']) ? 'active' : ''; ?>"
                       data-name="<?php echo htmlspecialchars(mb_strtolower($novela['nombre'])); ?>">
                        <div class="content-option-img">
                            <img src="<?php echo BASE_URL . 'public/' . htmlspecialchars($img_path); ?>"
                                 alt="<?php echo htmlspecialchars($novela['nombre']); ?>"
                                 onerror="this.onerror=null; this.src='<?php echo BASE_URL . 'public/' . DEFAULT_CONTENT_IMG; ?>'; this.classList.add('img-placeholder');">
                        </div>
                        <span class="content-option-name"><?php echo htmlspecialchars($novela['nombre']); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="content-list">
        <?php if ($id_novela_seleccionada && $volumenes): ?>
            <?php if ($volumenes->num_rows > 0): ?>
                <?php while ($vol = $volumenes->fetch_assoc()): ?>
                    <div class="content-item">
                        <form method="POST">
                            <div class="item-info">
                                <span>Volumen <?php echo htmlspecialchars($vol['numero_volumen']); ?></span>
                            </div>
                            <input type="hidden" name="id_volumen" value="<?php echo $vol['id_volumen']; ?>">
                            <textarea name="ruta_volumen" placeholder="Pega el enlace de Google Drive aquí..."><?php echo htmlspecialchars($vol['ruta_volumen']); ?></textarea>
                            <button type="submit" name="actualizar_link" class="btn-update">Actualizar Enlace</button>
                        </form>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-msg">No se encontraron volúmenes para esta novela.</div>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-msg">Selecciona una novela arriba para gestionar sus links de Drive.</div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>