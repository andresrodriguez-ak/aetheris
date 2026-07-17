<?php
/* ═══════════════════════════════════════════════════════════════
   Aetheris Admin — subir_manga.php
   Formulario de carga de un nuevo manga: información básica,
   géneros e imágenes (miniatura + portada opcional).
   ═══════════════════════════════════════════════════════════════ */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/app_config.php';
require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/includes/helpers.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: " . BASE_URL . "public/index.php");
    exit();
}

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre                = trim($_POST['nombre'] ?? '');
    $descripcion            = trim($_POST['descripcion'] ?? '');
    $estado                 = $_POST['estado'] ?? '';
    $fecha_publicacion      = !empty($_POST['fecha_publicacion']) ? $_POST['fecha_publicacion'] : null;
    $generos_seleccionados  = $_POST['generos'] ?? [];

    if (empty($nombre) || empty($descripcion) || empty($generos_seleccionados)) {
        $mensaje = "<div class='alert alert-error'>Los campos marcados con * son obligatorios.</div>";
    } else {
        $imagen  = null;
        $portada = null;

        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $imagen = guardarImagenComoWebp($_FILES['imagen'], 'uploads/mangas/');
        }

        if (isset($_FILES['portada']) && $_FILES['portada']['error'] === UPLOAD_ERR_OK) {
            $portada = guardarImagenComoWebp($_FILES['portada'], 'uploads/mangas/');
        }

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO mangas (nombre, descripcion, imagen, portada, estado, fecha_publicacion) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $nombre, $descripcion, $imagen, $portada, $estado, $fecha_publicacion);
            $stmt->execute();
            $manga_id = $conn->insert_id;

            $stmt_generos = $conn->prepare("INSERT INTO manga_generos (manga_id, genero_id) VALUES (?, ?)");
            foreach ($generos_seleccionados as $genero_id) {
                $stmt_generos->bind_param("ii", $manga_id, $genero_id);
                $stmt_generos->execute();
            }

            $conn->commit();
            $mensaje = "<div class='alert alert-success'>Manga subido correctamente.</div>";
            $_POST = array();
        } catch (Exception $e) {
            $conn->rollback();
            $mensaje = "<div class='alert alert-error'>Error al subir el manga: " . $e->getMessage() . "</div>";
        }
    }
}

$generos_query = $conn->query("SELECT id, nombre FROM generos ORDER BY nombre");
$generos = $generos_query->fetch_all(MYSQLI_ASSOC);

$page_title   = 'Subir Manga';
$accent_color = 'manga';
$admin_css    = ['subir_contenido.css'];
$admin_js     = ['subir_contenido.js'];

require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-panel">
    <div class="editor-header">
        <h1 class="admin-title">Subir Nuevo Manga</h1>
    </div>

    <?php echo $mensaje; ?>

    <form method="POST" enctype="multipart/form-data">

        <div class="form-card">
            <p class="section-label">Información básica</p>

            <div class="form-group">
                <label class="field-label">Nombre del manga<span class="req">*</span></label>
                <input type="text" name="nombre"
                       value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>"
                       placeholder="Ej: Berserk" required>
            </div>

            <div class="form-group">
                <label class="field-label">Descripción<span class="req">*</span></label>
                <textarea name="descripcion"
                          placeholder="Escribe una sinopsis del manga..."
                          required><?php echo htmlspecialchars($_POST['descripcion'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label class="field-label">Estado</label>
                <select name="estado">
                    <option value="En emision"   <?php echo ($_POST['estado'] ?? '') === 'En emision'   ? 'selected' : ''; ?>>En emisión</option>
                    <option value="Finalizado"   <?php echo ($_POST['estado'] ?? '') === 'Finalizado'   ? 'selected' : ''; ?>>Finalizado</option>
                    <option value="Proximamente" <?php echo ($_POST['estado'] ?? '') === 'Proximamente' ? 'selected' : ''; ?>>Próximamente</option>
                </select>
            </div>

            <div class="form-group">
                <label class="field-label">Fecha de publicación <span style="font-weight:400; color:var(--text-muted);">(opcional)</span></label>
                <input type="date" name="fecha_publicacion"
                       value="<?php echo htmlspecialchars($_POST['fecha_publicacion'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-card">
            <p class="section-label">Géneros<span class="req">*</span></p>
            <?php if (empty($generos)): ?>
                <p style="font-size:13px; color:var(--text-muted);">No hay géneros disponibles.</p>
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
                    <div class="upload-placeholder">
                        <div>Toca para seleccionar imagen</div>
                        <div class="upload-hint">JPEG / PNG / WEBP — máx. 2MB</div>
                    </div>
                    <img class="area-preview-img" alt="Vista previa miniatura">
                    <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp">
                </div>
                <div class="file-name-display" id="imagen-name"></div>
            </div>

            <div class="form-group">
                <label class="field-label">Portada / banner <span style="font-weight:400; color:var(--text-muted);">(opcional)</span></label>
                <div class="file-upload-area" data-name-target="portada-name">
                    <div class="upload-placeholder">
                        <div>Toca para seleccionar portada</div>
                        <div class="upload-hint">JPEG / PNG / WEBP — máx. 5MB</div>
                    </div>
                    <img class="area-preview-img" alt="Vista previa portada">
                    <input type="file" name="portada" accept="image/jpeg,image/png,image/webp">
                </div>
                <div class="file-name-display" id="portada-name"></div>
            </div>
        </div>

        <button type="submit" class="btn-submit">Subir Manga</button>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>