<?php


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
    $generos_seleccionados  = $_POST['generos'] ?? [];

    if (empty($nombre) || empty($descripcion) || empty($generos_seleccionados)) {
        $mensaje = "<div class='alert alert-error'>Los campos marcados con * son obligatorios.</div>";
    } else {
        $imagen = null;

        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $imagen = guardarImagenComoWebp($_FILES['imagen'], 'uploads/novelas/');
        }

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO novelas (nombre, descripcion, imagen, estado) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $nombre, $descripcion, $imagen, $estado);
            $stmt->execute();
            $novela_id = $conn->insert_id;

            $stmt_generos = $conn->prepare("INSERT INTO novela_generos (novela_id, genero_id) VALUES (?, ?)");
            foreach ($generos_seleccionados as $genero_id) {
                $stmt_generos->bind_param("ii", $novela_id, $genero_id);
                $stmt_generos->execute();
            }

            $conn->commit();
            $mensaje = "<div class='alert alert-success'>Novela subida correctamente.</div>";
            $_POST = array();
        } catch (Exception $e) {
            $conn->rollback();
            $mensaje = "<div class='alert alert-error'>Error al subir la novela: " . $e->getMessage() . "</div>";
        }
    }
}

$generos_query = $conn->query("SELECT id, nombre FROM generos ORDER BY nombre");
$generos = $generos_query->fetch_all(MYSQLI_ASSOC);

$page_title   = 'Subir Novela';
$accent_color = 'novela';
$admin_css    = ['subir_contenido.css'];
$admin_js     = ['subir_contenido.js'];

require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-panel">
    <div class="editor-header">
        <h1 class="admin-title">Subir Nueva Novela</h1>
    </div>

    <?php echo $mensaje; ?>

    <form method="POST" enctype="multipart/form-data">

        <div class="form-card">
            <p class="section-label">Información básica</p>

            <div class="form-group">
                <label class="field-label">Nombre de la novela<span class="req">*</span></label>
                <input type="text" name="nombre"
                       value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>"
                       placeholder="Ej: Mushoku Tensei" required>
            </div>

            <div class="form-group">
                <label class="field-label">Descripción<span class="req">*</span></label>
                <textarea name="descripcion"
                          placeholder="Escribe una sinopsis de la novela..."
                          required><?php echo htmlspecialchars($_POST['descripcion'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label class="field-label">Estado</label>
                <select name="estado">
                    <option value="En emisión"   <?php echo ($_POST['estado'] ?? '') === 'En emisión'   ? 'selected' : ''; ?>>En emisión</option>
                    <option value="Finalizado"   <?php echo ($_POST['estado'] ?? '') === 'Finalizado'   ? 'selected' : ''; ?>>Finalizado</option>
                    <option value="Próximamente" <?php echo ($_POST['estado'] ?? '') === 'Próximamente' ? 'selected' : ''; ?>>Próximamente</option>
                </select>
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
            <p class="section-label">Imagen</p>

            <div class="form-group">
                <label class="field-label">Portada</label>
                <div class="file-upload-area" data-name-target="imagen-name">
                    <div class="upload-placeholder">
                        <div>Toca para seleccionar imagen</div>
                        <div class="upload-hint">JPEG / PNG / WEBP — máx. 2MB</div>
                    </div>
                    <img class="area-preview-img" alt="Vista previa">
                    <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp">
                </div>
                <div class="file-name-display" id="imagen-name"></div>
            </div>
        </div>

        <button type="submit" class="btn-submit">Subir Novela</button>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>