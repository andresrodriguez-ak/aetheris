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

function guardarImagenComoWebp($archivo, $destDirAbs, $destDirRel) {
    $info = @getimagesize($archivo['tmp_name']);
    if ($info === false) {
        return null;
    }

    switch ($info['mime']) {
        case 'image/jpeg':
            $origen = imagecreatefromjpeg($archivo['tmp_name']);
            break;
        case 'image/png':
            $origen = imagecreatefrompng($archivo['tmp_name']);
            imagepalettetotruecolor($origen);
            imagealphablending($origen, true);
            imagesavealpha($origen, true);
            break;
        case 'image/webp':
            $origen = imagecreatefromwebp($archivo['tmp_name']);
            break;
        default:
            return null;
    }

    if (!$origen) {
        return null;
    }

    if (!is_dir($destDirAbs)) {
        mkdir($destDirAbs, 0755, true);
    }

    $nombre  = uniqid() . '.webp';
    $rutaAbs = $destDirAbs . $nombre;
    $rutaRel = $destDirRel . $nombre;

    $guardado = imagewebp($origen, $rutaAbs, 85);
    imagedestroy($origen);

    return $guardado ? $rutaRel : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $estado = $_POST['estado'];
    $fecha_emision = !empty($_POST['fecha_emision']) ? $_POST['fecha_emision'] : null;
    $generos_seleccionados = $_POST['generos'] ?? [];

    $imagen = '';
    $portada = '';
    $imagen_abs = '';
    $portada_abs = '';
    $dest_dir_abs = __DIR__ . '/../../public/uploads/animes/';
    $dest_dir_rel = 'uploads/animes/';

    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $imagen = guardarImagenComoWebp($_FILES['imagen'], $dest_dir_abs, $dest_dir_rel);
        if ($imagen === null) {
            $error = "La miniatura debe ser una imagen JPEG, PNG o WEBP válida";
        } else {
            $imagen_abs = __DIR__ . '/../../public/' . $imagen;
        }
    }

    if (empty($error) && isset($_FILES['portada']) && $_FILES['portada']['error'] === UPLOAD_ERR_OK) {
        $portada = guardarImagenComoWebp($_FILES['portada'], $dest_dir_abs, $dest_dir_rel);
        if ($portada === null) {
            $error = "La portada debe ser una imagen JPEG, PNG o WEBP válida";
        } else {
            $portada_abs = __DIR__ . '/../../public/' . $portada;
        }
    }

    if (empty($error) && (empty($nombre) || empty($descripcion) || empty($generos_seleccionados))) {
        $error = "Los campos marcados con * son obligatorios";
    }

    if (empty($error)) {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO animes (nombre, descripcion, imagen, portada, estado, fecha_emision) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $nombre, $descripcion, $imagen, $portada, $estado, $fecha_emision);
            $stmt->execute();
            $anime_id = $conn->insert_id;

            $stmt_generos = $conn->prepare("INSERT INTO anime_generos (anime_id, genero_id) VALUES (?, ?)");
            foreach ($generos_seleccionados as $genero_id) {
                $stmt_generos->bind_param("ii", $anime_id, $genero_id);
                $stmt_generos->execute();
            }

            $conn->commit();
            $success = "¡Anime subido correctamente!";
            $_POST = array();
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error al subir el anime: " . $e->getMessage();
            if (!empty($imagen_abs) && file_exists($imagen_abs)) unlink($imagen_abs);
            if (!empty($portada_abs) && file_exists($portada_abs)) unlink($portada_abs);
        }
    } else {
        if (!empty($imagen_abs) && file_exists($imagen_abs)) unlink($imagen_abs);
        if (!empty($portada_abs) && file_exists($portada_abs)) unlink($portada_abs);
    }
}

$generos_query = $conn->query("SELECT id, nombre FROM generos ORDER BY nombre");
$generos = $generos_query ? $generos_query->fetch_all(MYSQLI_ASSOC) : [];

$page_title   = 'Subir Anime';
$accent_color = 'anime';
$admin_css    = ['subir_contenido.css'];
$admin_js     = ['subir_contenido.js'];

require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-panel">
    <div class="editor-header">
        <h1 class="admin-title">Subir Nuevo Anime</h1>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

        <div class="form-card">
            <p class="section-label">Información básica</p>

            <div class="form-group">
                <label class="field-label">Nombre del Anime <span class="req">*</span></label>
                <input type="text" name="nombre"
                       value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>"
                       placeholder="Ej: Shingeki no Kyojin" required>
            </div>

            <div class="form-group">
                <label class="field-label">Descripción <span class="req">*</span></label>
                <textarea name="descripcion"
                          placeholder="Escribe una sinopsis del anime..."
                          required><?php echo htmlspecialchars($_POST['descripcion'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label class="field-label">Estado</label>
                <select name="estado">
                    <option value="En emisión"   <?php echo ($_POST['estado'] ?? '') == 'En emisión'   ? 'selected' : ''; ?>>En emisión</option>
                    <option value="Finalizado"   <?php echo ($_POST['estado'] ?? '') == 'Finalizado'   ? 'selected' : ''; ?>>Finalizado</option>
                    <option value="Próximamente" <?php echo ($_POST['estado'] ?? '') == 'Próximamente' ? 'selected' : ''; ?>>Próximamente</option>
                </select>
            </div>

            <div class="form-group">
                <label class="field-label">Fecha de Emisión <span style="color:var(--text-muted); font-weight:400;">(opcional)</span></label>
                <input type="date" name="fecha_emision"
                       value="<?php echo $_POST['fecha_emision'] ?? ''; ?>">
            </div>
        </div>

        <div class="form-card">
            <p class="section-label">Géneros <span class="req">*</span></p>
            <?php if (empty($generos)): ?>
                <p style="font-size:13px; color:var(--text-muted);">
                    No hay géneros disponibles.
                    <a href="admin_generos.php" style="color:var(--accent-current);">Agregar géneros</a>
                </p>
            <?php else: ?>
                <div class="generos-grid">
                    <?php foreach ($generos as $genero): ?>
                        <div class="genero-chip">
                            <input type="checkbox"
                                   id="g<?php echo $genero['id']; ?>"
                                   name="generos[]"
                                   value="<?php echo $genero['id']; ?>"
                                   <?php echo in_array($genero['id'], $_POST['generos'] ?? []) ? 'checked' : ''; ?>>
                            <label for="g<?php echo $genero['id']; ?>">
                                <?php echo htmlspecialchars($genero['nombre']); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-card">
            <p class="section-label">Imágenes</p>

            <div class="form-group">
                <label class="field-label">Miniatura principal</label>
                <div class="file-upload-area" data-name-target="imagen-name">
                    <img class="area-preview-img" alt="Vista previa">
                    <div class="upload-placeholder">
                        <div style="font-size:14px; color:var(--text-muted);">Toca para seleccionar imagen</div>
                        <div class="upload-hint">JPEG / PNG / WEBP — máx. 2MB — se convierte a WEBP automáticamente</div>
                    </div>
                    <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp">
                </div>
                <div class="file-name-display" id="imagen-name"></div>
            </div>

            <div class="form-group">
                <label class="field-label">Portada / Banner <span style="color:var(--text-muted); font-weight:400;">(opcional)</span></label>
                <div class="file-upload-area" data-name-target="portada-name">
                    <img class="area-preview-img" alt="Vista previa portada">
                    <div class="upload-placeholder">
                        <div style="font-size:14px; color:var(--text-muted);">Toca para seleccionar portada</div>
                        <div class="upload-hint">JPEG / PNG / WEBP — máx. 5MB — se convierte a WEBP automáticamente</div>
                    </div>
                    <input type="file" name="portada" accept="image/jpeg,image/png,image/webp">
                </div>
                <div class="file-name-display" id="portada-name"></div>
            </div>
        </div>

        <button type="submit" class="btn-submit">Subir Anime</button>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>