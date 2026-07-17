<?php
/* ═══════════════════════════════════════════════════════════════
   Aetheris Admin — subir_volumen.php
   Formulario de carga de un nuevo volumen de novela: novela
   asociada, número de volumen y enlace de Google Drive al PDF.
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
    $id_novela      = $_POST['id_novela'] ?? '';
    $numero_volumen = $_POST['numero_volumen'] ?? '';
    $ruta_volumen   = trim($_POST['ruta_volumen'] ?? '');

    if (empty($id_novela) || empty($numero_volumen) || empty($ruta_volumen)) {
        $mensaje = "<div class='alert alert-error'>Los campos marcados con * son obligatorios.</div>";
    } elseif (strpos($ruta_volumen, 'drive.google.com') === false) {
        $mensaje = "<div class='alert alert-error'>El enlace debe ser de Google Drive.</div>";
    } else {
        $stmt = $conn->prepare("INSERT INTO volumenes (id_novela, numero_volumen, ruta_volumen) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $id_novela, $numero_volumen, $ruta_volumen);

        if ($stmt->execute()) {
            $mensaje = "<div class='alert alert-success'>Volumen subido correctamente.</div>";
            $_POST = array();
        } else {
            $mensaje = "<div class='alert alert-error'>Error al subir el volumen: " . $conn->error . "</div>";
        }
    }
}

$novelas_result = $conn->query("SELECT id, nombre, imagen FROM novelas ORDER BY nombre");
$novelas = $novelas_result->fetch_all(MYSQLI_ASSOC);

$page_title   = 'Subir Volumen';
$accent_color = 'novela';
$admin_css    = ['subir_contenido.css'];
$admin_js     = ['subir_contenido.js'];

require_once __DIR__ . '/includes/header.php';

$fallback_img = BASE_URL . 'public/' . DEFAULT_CONTENT_IMG;
?>

<div class="admin-panel">
    <div class="editor-header">
        <h1 class="admin-title">Subir Nuevo Volumen</h1>
    </div>

    <?php echo $mensaje; ?>

    <form method="POST">

        <div class="form-card">
            <p class="section-label">Datos del volumen</p>

            <div class="form-group">
                <label class="field-label">Novela<span class="req">*</span></label>
                <input type="hidden" name="id_novela" id="id_novela" value="<?php echo htmlspecialchars($_POST['id_novela'] ?? ''); ?>">
                <div class="custom-select" data-hidden-input="id_novela" data-fallback-img="<?php echo $fallback_img; ?>">
                    <div class="select-selected">
                        <img class="img-placeholder" src="<?php echo $fallback_img; ?>" alt="">
                        <span>Selecciona una novela...</span>
                    </div>
                    <div class="select-items">
                        <?php foreach ($novelas as $novela): ?>
                            <?php $img_path = !empty($novela['imagen']) ? BASE_URL . 'public/' . htmlspecialchars($novela['imagen']) : $fallback_img; ?>
                            <div class="select-item"
                                 data-value="<?php echo $novela['id']; ?>"
                                 data-img="<?php echo $img_path; ?>"
                                 data-name="<?php echo htmlspecialchars($novela['nombre']); ?>">
                                <img src="<?php echo $img_path; ?>" alt="<?php echo htmlspecialchars($novela['nombre']); ?>"
                                     onerror="this.onerror=null; this.src='<?php echo $fallback_img; ?>'; this.classList.add('img-placeholder');">
                                <span><?php echo htmlspecialchars($novela['nombre']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="field-label">Número de volumen<span class="req">*</span></label>
                <input type="number" name="numero_volumen" min="1"
                       value="<?php echo htmlspecialchars($_POST['numero_volumen'] ?? ''); ?>"
                       placeholder="Ej: 1" required>
            </div>
        </div>

        <div class="form-card">
            <p class="section-label">Archivo PDF</p>

            <div class="form-group">
                <label class="field-label">Enlace de Google Drive<span class="req">*</span></label>
                <textarea name="ruta_volumen" class="embed-input"
                          placeholder="https://drive.google.com/file/d/..."
                          required><?php echo htmlspecialchars($_POST['ruta_volumen'] ?? ''); ?></textarea>
            </div>
        </div>

        <button type="submit" class="btn-submit">Subir Volumen</button>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>