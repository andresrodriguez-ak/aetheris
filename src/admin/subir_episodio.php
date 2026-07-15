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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_anime        = $_POST['id_anime'];
    $numero_episodio = $_POST['numero_episodio'];
    $embed_code      = $_POST['embed_code'];

    if (empty($id_anime) || empty($numero_episodio) || empty($embed_code)) {
        $error = "Todos los campos son obligatorios";
    } else {
        $stmt = $conn->prepare("INSERT INTO episodios (id_anime, numero_episodio, embed_code) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $id_anime, $numero_episodio, $embed_code);
        if ($stmt->execute()) {
            $success = "Episodio subido correctamente";
        } else {
            $error = "Error al subir el episodio: " . $conn->error;
        }
    }
}

$animes = $conn->query("SELECT id, nombre, imagen FROM animes ORDER BY nombre");

$page_title   = 'Subir Episodio';
$accent_color = 'anime';
$admin_css    = ['subir_contenido.css'];
$admin_js     = ['subir_contenido.js'];

require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-panel">
    <div class="editor-header">
        <h1 class="admin-title">Subir Nuevo Episodio</h1>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="form-card">
        <form method="POST">

            <div class="form-group">
                <label class="field-label">Anime</label>
                <input type="hidden" id="id_anime" name="id_anime">

                <div class="custom-select" id="customSelect" data-hidden-input="id_anime" data-fallback-img="<?php echo BASE_URL . 'public/' . DEFAULT_CONTENT_IMG; ?>">
                    <div class="select-selected">
                        <span>Seleccionar anime...</span>
                    </div>
                    <div class="select-items">
                        <?php while ($anime = $animes->fetch_assoc()): ?>
                            <?php $img_path = !empty($anime['imagen']) ? $anime['imagen'] : DEFAULT_CONTENT_IMG; ?>
                            <div class="select-item"
                                 data-value="<?php echo $anime['id']; ?>"
                                 data-name="<?php echo htmlspecialchars($anime['nombre']); ?>"
                                 data-img="<?php echo BASE_URL . 'public/' . htmlspecialchars($img_path); ?>">
                                <img src="<?php echo BASE_URL . 'public/' . htmlspecialchars($img_path); ?>"
                                     alt="<?php echo htmlspecialchars($anime['nombre']); ?>"
                                     onerror="this.onerror=null; this.src='<?php echo BASE_URL . 'public/' . DEFAULT_CONTENT_IMG; ?>'; this.classList.add('img-placeholder');">
                                <span><?php echo htmlspecialchars($anime['nombre']); ?></span>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="field-label" for="numero_episodio">Número de Episodio</label>
                <input type="number" id="numero_episodio" name="numero_episodio" min="1" required>
            </div>

            <div class="form-group">
                <label class="field-label" for="embed_code">Código de Embed (iframe)</label>
                <textarea id="embed_code" name="embed_code" class="embed-input"
                          placeholder='Ej: <iframe src="https://www.youtube.com/embed/VIDEO_ID" width="560" height="315" frameborder="0" allowfullscreen></iframe>'
                          required></textarea>
            </div>

            <button type="submit" class="btn-submit">Subir Episodio</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>