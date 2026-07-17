<?php
/* ═══════════════════════════════════════════════════════════════
   Aetheris Admin — subir_capitulo.php
   Formulario de carga de un nuevo capítulo de manga: manga
   asociado, rango de capítulos y enlace de Google Drive al PDF.
   ═══════════════════════════════════════════════════════════════ */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/app_config.php';
require_once __DIR__ . '/../../config/db_config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: " . BASE_URL . "public/index.php");
    exit();
}

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_manga        = $_POST['id_manga'] ?? '';
    $nombre_capitulo = trim($_POST['nombre_capitulo'] ?? '');
    $enlace_pdf      = trim($_POST['enlace_pdf'] ?? '');
    $capitulo_inicio = $_POST['capitulo_inicio'] ?? '';
    $capitulo_fin    = !empty($_POST['capitulo_fin']) ? $_POST['capitulo_fin'] : null;

    if (empty($id_manga) || empty($nombre_capitulo) || empty($enlace_pdf) || empty($capitulo_inicio)) {
        $mensaje = "<div class='alert alert-error'>Los campos marcados con * son obligatorios.</div>";
    } elseif (strpos($enlace_pdf, 'drive.google.com') === false) {
        $mensaje = "<div class='alert alert-error'>El enlace debe ser de Google Drive.</div>";
    } else {
        $stmt = $conn->prepare("INSERT INTO manga_capitulos (id_manga, nombre_capitulo, enlace_pdf, capitulo_inicio, capitulo_fin) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issii", $id_manga, $nombre_capitulo, $enlace_pdf, $capitulo_inicio, $capitulo_fin);

        if ($stmt->execute()) {
            $mensaje = "<div class='alert alert-success'>Capítulo subido correctamente.</div>";
            $_POST = array();
        } else {
            $mensaje = "<div class='alert alert-error'>Error al subir el capítulo: " . $conn->error . "</div>";
        }
    }
}

$mangas_result = $conn->query("SELECT id, nombre, imagen FROM mangas ORDER BY nombre");
$mangas = $mangas_result->fetch_all(MYSQLI_ASSOC);

$page_title   = 'Subir Capítulo';
$accent_color = 'manga';
$admin_css    = ['subir_contenido.css'];
$admin_js     = ['subir_contenido.js'];

require_once __DIR__ . '/includes/header.php';

$fallback_img = BASE_URL . 'public/' . DEFAULT_CONTENT_IMG;
?>

<div class="admin-panel">
    <div class="editor-header">
        <h1 class="admin-title">Subir Nuevo Capítulo</h1>
    </div>

    <?php echo $mensaje; ?>

    <form method="POST">

        <div class="form-card">
            <p class="section-label">Datos del capítulo</p>

            <div class="form-group">
                <label class="field-label">Manga<span class="req">*</span></label>
                <input type="hidden" name="id_manga" id="id_manga" value="<?php echo htmlspecialchars($_POST['id_manga'] ?? ''); ?>">
                <div class="custom-select" data-hidden-input="id_manga" data-fallback-img="<?php echo $fallback_img; ?>">
                    <div class="select-selected">
                        <img class="img-placeholder" src="<?php echo $fallback_img; ?>" alt="">
                        <span>Selecciona un manga...</span>
                    </div>
                    <div class="select-items">
                        <?php foreach ($mangas as $manga): ?>
                            <?php $img_path = !empty($manga['imagen']) ? BASE_URL . 'public/' . htmlspecialchars($manga['imagen']) : $fallback_img; ?>
                            <div class="select-item"
                                 data-value="<?php echo $manga['id']; ?>"
                                 data-img="<?php echo $img_path; ?>"
                                 data-name="<?php echo htmlspecialchars($manga['nombre']); ?>">
                                <img src="<?php echo $img_path; ?>" alt="<?php echo htmlspecialchars($manga['nombre']); ?>"
                                     onerror="this.onerror=null; this.src='<?php echo $fallback_img; ?>'; this.classList.add('img-placeholder');">
                                <span><?php echo htmlspecialchars($manga['nombre']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="field-label">Nombre del capítulo<span class="req">*</span></label>
                <input type="text" name="nombre_capitulo"
                       value="<?php echo htmlspecialchars($_POST['nombre_capitulo'] ?? ''); ?>"
                       placeholder="Ej: Capítulo 1 - El comienzo" required>
            </div>

            <div class="form-group">
                <label class="field-label">Capítulo inicio<span class="req">*</span></label>
                <input type="number" name="capitulo_inicio" min="1"
                       value="<?php echo htmlspecialchars($_POST['capitulo_inicio'] ?? ''); ?>"
                       placeholder="Ej: 1" required>
            </div>

            <div class="form-group">
                <label class="field-label">Capítulo fin <span style="font-weight:400; color:var(--text-muted);">(opcional)</span></label>
                <input type="number" name="capitulo_fin" min="1"
                       value="<?php echo htmlspecialchars($_POST['capitulo_fin'] ?? ''); ?>"
                       placeholder="Ej: 5">
            </div>
        </div>

        <div class="form-card">
            <p class="section-label">Archivo PDF</p>

            <div class="form-group">
                <label class="field-label">Enlace de Google Drive<span class="req">*</span></label>
                <textarea name="enlace_pdf" class="embed-input"
                          placeholder="https://drive.google.com/file/d/..."
                          required><?php echo htmlspecialchars($_POST['enlace_pdf'] ?? ''); ?></textarea>
            </div>
        </div>

        <button type="submit" class="btn-submit">Subir Capítulo</button>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>