<?php
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/app_config.php';
require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/includes/helpers.php';

$conn->set_charset("utf8mb4");

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: " . BASE_URL . "public/index.php");
    exit();
}

$tipos_validos = [
    'anime'  => ['tabla' => 'animes',  'tabla_gen' => 'anime_generos',  'fk' => 'anime_id',  'portada' => true],
    'manga'  => ['tabla' => 'mangas',  'tabla_gen' => 'manga_generos',  'fk' => 'manga_id',  'portada' => true],
    'novela' => ['tabla' => 'novelas', 'tabla_gen' => 'novela_generos', 'fk' => 'novela_id', 'portada' => false],
];

if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_item') {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');

    $tipo = $_GET['tipo'] ?? '';
    $id   = (int) ($_GET['id'] ?? 0);
    $data = null;

    if (isset($tipos_validos[$tipo])) {
        $tabla     = $tipos_validos[$tipo]['tabla'];
        $tabla_gen = $tipos_validos[$tipo]['tabla_gen'];
        $fk        = $tipos_validos[$tipo]['fk'];

        $s = $conn->prepare("SELECT t.*, GROUP_CONCAT(g.genero_id) as genero_ids FROM $tabla t LEFT JOIN $tabla_gen g ON t.id = g.$fk WHERE t.id = ? GROUP BY t.id");
        $s->bind_param("i", $id);
        $s->execute();
        $data = $s->get_result()->fetch_assoc();

        if ($data) {
            if (!empty($data['imagen'])) {
                $data['imagen'] = BASE_URL . 'public/' . $data['imagen'];
            }
            if (isset($data['portada']) && !empty($data['portada'])) {
                $data['portada'] = BASE_URL . 'public/' . $data['portada'];
            }
        }
    }

    echo json_encode($data ?? (object) []);
    exit;
}

if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_list') {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');

    $tipo  = $_GET['tipo'] ?? '';
    $items = [];

    if (isset($tipos_validos[$tipo])) {
        $tabla = $tipos_validos[$tipo]['tabla'];
        $r = $conn->query("SELECT id, nombre, imagen FROM $tabla ORDER BY nombre");
        if ($r) {
            while ($row = $r->fetch_assoc()) {
                $row['imagen'] = !empty($row['imagen'])
                    ? BASE_URL . 'public/' . $row['imagen']
                    : BASE_URL . 'public/' . DEFAULT_CONTENT_IMG;
                $items[] = $row;
            }
        }
    }

    echo json_encode($items);
    exit;
}

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar'])) {
    $tipo        = $_POST['tipo'] ?? '';
    $id          = (int) ($_POST['id'] ?? 0);
    $nombre      = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $estado      = $_POST['estado'] ?? '';
    $generos     = $_POST['generos'] ?? [];

    if (!isset($tipos_validos[$tipo]) || empty($id) || empty($nombre)) {
        $mensaje = "<div class='alert alert-error'>Datos inválidos.</div>";
    } else {
        $tabla      = $tipos_validos[$tipo]['tabla'];
        $tabla_gen  = $tipos_validos[$tipo]['tabla_gen'];
        $fk         = $tipos_validos[$tipo]['fk'];
        $tiene_port = $tipos_validos[$tipo]['portada'];

        $stmt_actual = $tiene_port
            ? $conn->prepare("SELECT imagen, portada FROM $tabla WHERE id = ?")
            : $conn->prepare("SELECT imagen FROM $tabla WHERE id = ?");
        $stmt_actual->bind_param("i", $id);
        $stmt_actual->execute();
        $actual = $stmt_actual->get_result()->fetch_assoc();

        $imagen = $actual['imagen'] ?? null;
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $nueva_imagen = guardarImagenComoWebp($_FILES['imagen'], "uploads/{$tipo}s/");
            if ($nueva_imagen) {
                $imagen = $nueva_imagen;
            }
        }

        $portada = $tiene_port ? ($actual['portada'] ?? null) : null;
        if ($tiene_port && isset($_FILES['portada']) && $_FILES['portada']['error'] === UPLOAD_ERR_OK) {
            $nueva_portada = guardarImagenComoWebp($_FILES['portada'], "uploads/{$tipo}s/");
            if ($nueva_portada) {
                $portada = $nueva_portada;
            }
        }

        $conn->begin_transaction();
        try {
            if ($tiene_port) {
                $s = $conn->prepare("UPDATE $tabla SET nombre=?, descripcion=?, imagen=?, portada=?, estado=? WHERE id=?");
                $s->bind_param("sssssi", $nombre, $descripcion, $imagen, $portada, $estado, $id);
            } else {
                $s = $conn->prepare("UPDATE $tabla SET nombre=?, descripcion=?, imagen=?, estado=? WHERE id=?");
                $s->bind_param("ssssi", $nombre, $descripcion, $imagen, $estado, $id);
            }
            $s->execute();

            $conn->query("DELETE FROM $tabla_gen WHERE $fk=$id");
            $sg = $conn->prepare("INSERT INTO $tabla_gen ($fk, genero_id) VALUES (?, ?)");
            foreach ($generos as $gid) {
                $gid_int = (int) $gid;
                $sg->bind_param("ii", $id, $gid_int);
                $sg->execute();
            }

            $conn->commit();
            $mensaje = "<div class='alert alert-success'>Cambios guardados con éxito.</div>";
        } catch (Exception $e) {
            $conn->rollback();
            $mensaje = "<div class='alert alert-error'>Error al guardar: " . $e->getMessage() . "</div>";
        }
    }
}

$generos_list = $conn->query("SELECT id, nombre FROM generos ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

$page_title   = 'Editar Contenido';
$accent_color = 'general';
$admin_css    = ['subir_contenido.css', 'editar_contenido.css'];
$admin_js     = ['editar_contenido.js'];

require_once __DIR__ . '/includes/header.php';

$fallback_img = BASE_URL . 'public/' . DEFAULT_CONTENT_IMG;
?>

<div class="admin-panel">
    <div class="editor-header">
        <h1 class="admin-title">Editar Contenido</h1>
    </div>

    <?php echo $mensaje; ?>

    <div class="type-tabs">
        <button type="button" class="type-tab-btn active" data-tipo="anime">Anime</button>
        <button type="button" class="type-tab-btn" data-tipo="manga">Manga</button>
        <button type="button" class="type-tab-btn" data-tipo="novela">Novela</button>
    </div>

    <div class="form-group">
        <div class="custom-select" data-fallback-img="<?php echo $fallback_img; ?>">
            <div class="select-selected">
                <img class="img-placeholder" src="<?php echo $fallback_img; ?>" alt="">
                <span>Selecciona un anime...</span>
            </div>
            <div class="select-items">
                <div class="no-results">Cargando...</div>
            </div>
        </div>
    </div>

    <form method="POST" enctype="multipart/form-data" id="editForm" style="display:none">
        <input type="hidden" name="guardar" value="1">
        <input type="hidden" name="tipo" id="f_tipo">
        <input type="hidden" name="id" id="f_id">

        <div class="form-card">
            <p class="section-label">Información básica</p>

            <div class="form-group">
                <label class="field-label">Nombre del título<span class="req">*</span></label>
                <input type="text" name="nombre" id="f_nombre" required>
            </div>

            <div class="form-group">
                <label class="field-label">Sinopsis / descripción</label>
                <textarea name="descripcion" id="f_descripcion"></textarea>
            </div>

            <div class="form-group">
                <label class="field-label">Estado de publicación</label>
                <select name="estado" id="f_estado">
                    <option value="En emisión">En emisión</option>
                    <option value="Finalizado">Finalizado</option>
                    <option value="Próximamente">Próximamente</option>
                    <option value="En progreso">En progreso</option>
                    <option value="Abandonado">Abandonado</option>
                </select>
            </div>
        </div>

        <div class="form-card">
            <p class="section-label">Géneros relacionados</p>
            <div class="generos-grid">
                <?php foreach ($generos_list as $g): ?>
                    <div class="genero-chip">
                        <input type="checkbox" name="generos[]" id="g<?php echo $g['id']; ?>" value="<?php echo $g['id']; ?>">
                        <label for="g<?php echo $g['id']; ?>"><?php echo htmlspecialchars($g['nombre']); ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="form-card">
            <p class="section-label">Imágenes</p>

            <div class="form-group">
                <label class="field-label">Miniatura</label>
                <div class="file-upload-area" data-name-target="imagen-name">
                    <div class="upload-placeholder">
                        <div>Toca para reemplazar la imagen</div>
                        <div class="upload-hint">JPEG / PNG / WEBP — máx. 2MB</div>
                    </div>
                    <img class="area-preview-img" alt="Vista previa">
                    <input type="file" id="imagen" name="imagen" accept="image/jpeg,image/png,image/webp">
                </div>
                <div class="file-name-display" id="imagen-name"></div>
            </div>

            <div class="form-group" id="portada-group">
                <label class="field-label">Portada / banner</label>
                <div class="file-upload-area" data-name-target="portada-name">
                    <div class="upload-placeholder">
                        <div>Toca para reemplazar la portada</div>
                        <div class="upload-hint">JPEG / PNG / WEBP — máx. 5MB</div>
                    </div>
                    <img class="area-preview-img" alt="Vista previa">
                    <input type="file" id="portada" name="portada" accept="image/jpeg,image/png,image/webp">
                </div>
                <div class="file-name-display" id="portada-name"></div>
            </div>
        </div>

        <button type="submit" class="btn-submit">Guardar Cambios</button>
    </form>

    <div id="emptyState" class="edit-empty-state">Selecciona un título arriba para empezar a editar.</div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>