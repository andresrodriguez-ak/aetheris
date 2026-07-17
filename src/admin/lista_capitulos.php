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

$id_manga_seleccionado = $_GET['id_manga'] ?? null;
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_link'])) {
    $id_cap = $_POST['id_capitulo'];
    $nuevo_enlace = trim($_POST['enlace_pdf']);

    if (empty($id_cap) || empty($nuevo_enlace)) {
        $mensaje = "<div class='alert alert-error'>Los campos son obligatorios.</div>";
    } elseif (strpos($nuevo_enlace, 'drive.google.com') === false) {
        $mensaje = "<div class='alert alert-error'>El enlace debe ser de Google Drive.</div>";
    } else {
        $stmt_upd = $conn->prepare("UPDATE manga_capitulos SET enlace_pdf = ? WHERE id = ?");
        $stmt_upd->bind_param("si", $nuevo_enlace, $id_cap);

        if ($stmt_upd->execute()) {
            $mensaje = "<div class='alert alert-success'>Enlace del capítulo #$id_cap actualizado con éxito.</div>";
        } else {
            $mensaje = "<div class='alert alert-error'>Error al actualizar: " . $conn->error . "</div>";
        }
    }
}

$mangas_result = $conn->query("SELECT id, nombre, imagen FROM mangas ORDER BY nombre");
$mangas = $mangas_result->fetch_all(MYSQLI_ASSOC);

$manga_seleccionado_nombre = null;
foreach ($mangas as $m) {
    if ($m['id'] == $id_manga_seleccionado) {
        $manga_seleccionado_nombre = $m['nombre'];
        break;
    }
}

$capitulos = null;
if ($id_manga_seleccionado) {
    $stmt_cap = $conn->prepare("SELECT id, nombre_capitulo, capitulo_inicio, capitulo_fin, enlace_pdf FROM manga_capitulos WHERE id_manga = ? ORDER BY capitulo_inicio DESC");
    $stmt_cap->bind_param("i", $id_manga_seleccionado);
    $stmt_cap->execute();
    $capitulos = $stmt_cap->get_result();
}

$page_title   = 'Editor de Capítulos';
$accent_color = 'manga';
$admin_css    = ['lista_contenido.css'];
$admin_js     = ['lista_contenido.js'];

require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-panel">
    <div class="editor-header">
    <h1 class="admin-title">Editor de Capítulos</h1>
</div>

    <?php echo $mensaje; ?>

    <div class="content-select" id="contentSelect">
        <button type="button" class="content-select-btn" id="contentSelectBtn">
            <span><?php echo $manga_seleccionado_nombre ? htmlspecialchars($manga_seleccionado_nombre) : 'Selecciona un manga...'; ?></span>
            <span class="arrow">▼</span>
        </button>
        <div class="content-select-panel" id="contentSelectPanel">
            <div class="content-select-search">
                <input type="text" id="content-filter" placeholder="Buscar manga..." autocomplete="off">
            </div>
            <div class="content-select-list" id="contentGrid">
                <?php if (empty($mangas)): ?>
                    <div class="content-option-empty">No hay mangas cargados todavía.</div>
                <?php endif; ?>
                <?php foreach ($mangas as $manga): ?>
                    <?php $img_path = !empty($manga['imagen']) ? $manga['imagen'] : DEFAULT_CONTENT_IMG; ?>
                    <a href="?id_manga=<?php echo $manga['id']; ?>"
                       class="content-option <?php echo ($id_manga_seleccionado == $manga['id']) ? 'active' : ''; ?>"
                       data-name="<?php echo htmlspecialchars(mb_strtolower($manga['nombre'])); ?>">
                        <div class="content-option-img">
                            <img src="<?php echo BASE_URL . 'public/' . htmlspecialchars($img_path); ?>"
                                 alt="<?php echo htmlspecialchars($manga['nombre']); ?>"
                                 onerror="this.onerror=null; this.src='<?php echo BASE_URL . 'public/' . DEFAULT_CONTENT_IMG; ?>'; this.classList.add('img-placeholder');">
                        </div>
                        <span class="content-option-name"><?php echo htmlspecialchars($manga['nombre']); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="content-list">
        <?php if ($id_manga_seleccionado && $capitulos): ?>
            <?php if ($capitulos->num_rows > 0): ?>
                <?php while ($cap = $capitulos->fetch_assoc()): ?>
                    <?php
                        $rango = $cap['capitulo_inicio'];
                        if (!empty($cap['capitulo_fin']) && $cap['capitulo_fin'] != $cap['capitulo_inicio']) {
                            $rango .= ' - ' . $cap['capitulo_fin'];
                        }
                    ?>
                    <div class="content-item">
                        <form method="POST">
                            <div class="item-info">
                                <span>Capítulo <?php echo htmlspecialchars($rango); ?></span>
                                <span><?php echo htmlspecialchars($cap['nombre_capitulo']); ?></span>
                            </div>
                            <input type="hidden" name="id_capitulo" value="<?php echo $cap['id']; ?>">
                            <textarea name="enlace_pdf" placeholder="Pega el enlace de Google Drive aquí..."><?php echo htmlspecialchars($cap['enlace_pdf']); ?></textarea>
                            <button type="submit" name="actualizar_link" class="btn-update">Actualizar Enlace</button>
                        </form>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-msg">No se encontraron capítulos para este manga.</div>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-msg">Selecciona un manga arriba para gestionar sus links de Drive.</div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>