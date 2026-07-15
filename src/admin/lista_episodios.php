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

$id_anime_seleccionado = $_GET['id_anime'] ?? null;
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_link'])) {
    $id_epi = $_POST['id_episodio'];
    $nuevo_embed = $_POST['embed_code'];

    if (!empty($id_epi) && !empty($nuevo_embed)) {
        $stmt_upd = $conn->prepare("UPDATE episodios SET embed_code = ? WHERE id = ?");
        $stmt_upd->bind_param("si", $nuevo_embed, $id_epi);

        if ($stmt_upd->execute()) {
            $mensaje = "<div class='alert alert-success'>Enlace del episodio #$id_epi actualizado con éxito.</div>";
        } else {
            $mensaje = "<div class='alert alert-error'>Error al actualizar: " . $conn->error . "</div>";
        }
    }
}

$animes_result = $conn->query("SELECT id, nombre, imagen FROM animes ORDER BY nombre");
$animes = $animes_result->fetch_all(MYSQLI_ASSOC);

$anime_seleccionado_nombre = null;
foreach ($animes as $a) {
    if ($a['id'] == $id_anime_seleccionado) {
        $anime_seleccionado_nombre = $a['nombre'];
        break;
    }
}

$episodios = null;
if ($id_anime_seleccionado) {
    $stmt_epi = $conn->prepare("SELECT id, numero_episodio, titulo, embed_code FROM episodios WHERE id_anime = ? ORDER BY numero_episodio DESC");
    $stmt_epi->bind_param("i", $id_anime_seleccionado);
    $stmt_epi->execute();
    $episodios = $stmt_epi->get_result();
}

$page_title   = 'Editor de Episodios';
$accent_color = 'anime';
$admin_css    = ['lista_contenido.css'];
$admin_js     = ['lista_contenido.js'];

require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-panel">
    <div class="editor-header">
    <h1 class="admin-title">Editor de Episodios</h1>
</div>

    <?php echo $mensaje; ?>

    <div class="content-select" id="contentSelect">
        <button type="button" class="content-select-btn" id="contentSelectBtn">
            <span><?php echo $anime_seleccionado_nombre ? htmlspecialchars($anime_seleccionado_nombre) : 'Selecciona un anime...'; ?></span>
            <span class="arrow">▼</span>
        </button>
        <div class="content-select-panel" id="contentSelectPanel">
            <div class="content-select-search">
                <input type="text" id="content-filter" placeholder="Buscar anime..." autocomplete="off">
            </div>
            <div class="content-select-list" id="contentGrid">
                <?php if (empty($animes)): ?>
                    <div class="content-option-empty">No hay animes cargados todavía.</div>
                <?php endif; ?>
                <?php foreach ($animes as $anime): ?>
                    <?php $img_path = !empty($anime['imagen']) ? $anime['imagen'] : DEFAULT_CONTENT_IMG; ?>
                    <a href="?id_anime=<?php echo $anime['id']; ?>"
                       class="content-option <?php echo ($id_anime_seleccionado == $anime['id']) ? 'active' : ''; ?>"
                       data-name="<?php echo htmlspecialchars(mb_strtolower($anime['nombre'])); ?>">
                        <div class="content-option-img">
                            <img src="<?php echo BASE_URL . 'public/' . htmlspecialchars($img_path); ?>"
                                 alt="<?php echo htmlspecialchars($anime['nombre']); ?>"
                                 onerror="this.onerror=null; this.src='<?php echo BASE_URL . 'public/' . DEFAULT_CONTENT_IMG; ?>'; this.classList.add('img-placeholder');">
                        </div>
                        <span class="content-option-name"><?php echo htmlspecialchars($anime['nombre']); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="content-list">
        <?php if ($id_anime_seleccionado && $episodios): ?>
            <?php if ($episodios->num_rows > 0): ?>
                <?php while ($epi = $episodios->fetch_assoc()): ?>
                    <div class="content-item">
                        <form method="POST">
                            <div class="item-info">
                                <span>Episodio <?php echo $epi['numero_episodio']; ?></span>
                                <span><?php echo htmlspecialchars($epi['titulo']); ?></span>
                            </div>
                            <input type="hidden" name="id_episodio" value="<?php echo $epi['id']; ?>">
                            <textarea name="embed_code" placeholder="Pega el código iframe aquí..."><?php echo htmlspecialchars($epi['embed_code']); ?></textarea>
                            <button type="submit" name="actualizar_link" class="btn-update">Actualizar Enlace</button>
                        </form>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-msg">No se encontraron episodios para este anime.</div>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-msg">Selecciona un anime arriba para gestionar sus links de video.</div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>