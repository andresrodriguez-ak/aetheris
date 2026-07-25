<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id   = $_SESSION['user_id'];
$username  = $_SESSION['username'];
$is_admin  = ($_SESSION['role'] ?? '') === 'admin';
$logged_in = true;

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$stmt = $conn->prepare("SELECT id, username, email, role, created_at, profile_image, password FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$update_errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf'] ?? '')) {
        $update_errors[] = "Tu sesión expiró, recargá la página e intentá de nuevo.";
    } else {
        $new_username  = trim($_POST['username']);
        $new_email     = trim($_POST['email']);
        $profile_image = $user['profile_image'];

        $dup = false;

        $chk_user = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $chk_user->bind_param("si", $new_username, $user_id);
        $chk_user->execute();
        if ($chk_user->get_result()->num_rows > 0) $dup = true;

        $chk_email = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $chk_email->bind_param("si", $new_email, $user_id);
        $chk_email->execute();
        if ($chk_email->get_result()->num_rows > 0) $dup = true;

        if ($dup) {
            $update_errors[] = "Ese nombre de usuario o correo no está disponible.";
        }

        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $allowed_ext = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            $ext  = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            $info = getimagesize($_FILES['profile_image']['tmp_name']);

            if (!in_array($ext, $allowed_ext) || $info === false) {
                $update_errors[] = "El archivo debe ser una imagen válida (jpg, png o webp).";
            } elseif ($_FILES['profile_image']['size'] > 2 * 1024 * 1024) {
                $update_errors[] = "La imagen no puede superar los 2MB.";
            }
        }

        if (empty($update_errors)) {
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], 'uploads/profiles/' . $new_filename)) {
                    $profile_image = 'uploads/profiles/' . $new_filename;
                }
            }

            $upd = $conn->prepare("UPDATE users SET username=?, email=?, profile_image=? WHERE id=?");
            $upd->bind_param("sssi", $new_username, $new_email, $profile_image, $user_id);
            if ($upd->execute()) {
                $_SESSION['username'] = $new_username;
                header("Location: perfil.php?success=1");
                exit();
            } else {
                $update_errors[] = "Error al guardar los cambios. Intenta de nuevo.";
            }
        }
    }
}

define('DELETE_MAX_ATTEMPTS', 3);
define('DELETE_LOCK_MINUTES', 15);

$delete_error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf'] ?? '')) {
        $delete_error = "Tu sesión expiró, recargá la página e intentá de nuevo.";
    } elseif (!empty($_SESSION['delete_lock_until']) && time() < $_SESSION['delete_lock_until']) {
        $mins = (int) ceil(($_SESSION['delete_lock_until'] - time()) / 60);
        $delete_error = "Demasiados intentos fallidos. Probá de nuevo en $mins minuto(s).";
    } else {
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (password_verify($confirm_password, $user['password'])) {
            unset($_SESSION['delete_attempts'], $_SESSION['delete_lock_until']);
            $del = $conn->prepare("DELETE FROM users WHERE id = ?");
            $del->bind_param("i", $user_id);
            if ($del->execute()) {
                session_destroy();
                header("Location: login.php?deleted=1");
                exit();
            }
            $delete_error = "Error al eliminar la cuenta. Intentá de nuevo.";
        } else {
            $_SESSION['delete_attempts'] = ($_SESSION['delete_attempts'] ?? 0) + 1;

            if ($_SESSION['delete_attempts'] >= DELETE_MAX_ATTEMPTS) {
                $_SESSION['delete_lock_until'] = time() + (DELETE_LOCK_MINUTES * 60);
                $_SESSION['delete_attempts']   = 0;
                $delete_error = "Contraseña incorrecta. Demasiados intentos, esperá " . DELETE_LOCK_MINUTES . " minutos.";
            } else {
                $left = DELETE_MAX_ATTEMPTS - $_SESSION['delete_attempts'];
                $delete_error = "Contraseña incorrecta. Te quedan $left intento(s).";
            }
        }
    }
}


$q_fav = $conn->prepare("
    (SELECT a.id, a.nombre, a.imagen, 'anime' as tipo
     FROM favoritos f JOIN animes a ON f.anime_id = a.id
     WHERE f.user_id = ? AND f.es_favorito = 1)
    UNION
    (SELECT m.id, m.nombre, m.imagen, 'manga' as tipo
     FROM favoritos f JOIN mangas m ON f.manga_id = m.id
     WHERE f.user_id = ? AND f.es_favorito = 1)
    UNION
    (SELECT n.id, n.nombre, n.imagen, 'novela' as tipo
     FROM favoritos f JOIN novelas n ON f.novela_id = n.id
     WHERE f.user_id = ? AND f.es_favorito = 1)
");
$q_fav->bind_param("iii", $user_id, $user_id, $user_id);
$q_fav->execute();
$lista_favoritos = $q_fav->get_result();

$q_seg = $conn->prepare("
    (SELECT a.id, a.nombre, a.imagen, 'anime' as tipo, f.estado_seguimiento as estado
     FROM favoritos f JOIN animes a ON f.anime_id = a.id
     WHERE f.user_id = ? AND f.estado_seguimiento != '')
    UNION
    (SELECT m.id, m.nombre, m.imagen, 'manga' as tipo, f.estado_seguimiento as estado
     FROM favoritos f JOIN mangas m ON f.manga_id = m.id
     WHERE f.user_id = ? AND f.estado_seguimiento != '')
    UNION
    (SELECT n.id, n.nombre, n.imagen, 'novela' as tipo, f.estado_seguimiento as estado
     FROM favoritos f JOIN novelas n ON f.novela_id = n.id
     WHERE f.user_id = ? AND f.estado_seguimiento != '')
");
$q_seg->bind_param("iii", $user_id, $user_id, $user_id);
$q_seg->execute();
$res_seg = $q_seg->get_result();

$estados_labels = [
    'viendo'      => 'Viendo',
    'por_ver'     => 'Por ver',
    'completado'  => 'Completado',
    'pausado'     => 'Pausado',
    'descartado'  => 'Descartado',
];

$por_estado = array_fill_keys(array_keys($estados_labels), []);
while ($row = $res_seg->fetch_assoc()) {
    if (isset($por_estado[$row['estado']])) {
        $por_estado[$row['estado']][] = $row;
    }
}

$progreso = [];
$pq = $conn->prepare("
    SELECT a.id, a.nombre, a.imagen,
           COUNT(ev.numero_episodio) as vistos,
           (SELECT COUNT(*) FROM episodios e WHERE e.id_anime = a.id) as total
    FROM episodios_vistos ev
    JOIN animes a ON ev.anime_id = a.id
    WHERE ev.user_id = ?
    GROUP BY a.id, a.nombre, a.imagen
    HAVING vistos > 0
    ORDER BY vistos DESC
");
$pq->bind_param("i", $user_id);
$pq->execute();
$pr = $pq->get_result();
while ($row = $pr->fetch_assoc()) $progreso[] = $row;

function avatarUrl($img) {
    if (empty($img)) return 'uploads/profiles/default.png';
    if (strpos($img, 'uploads/') === 0) return $img;
    return 'uploads/profiles/' . $img;
}

function linkTipo($tipo, $id) {
    switch ($tipo) {
        case 'anime':  return "anime-detalle.php?id=$id";
        case 'manga':  return "manga-detalle.php?id=$id";
        case 'novela': return "novela-detalle.php?id=$id";
        default:       return "#";
    }
}


function colorVar($tipo) {
    switch ($tipo) {
        case 'anime':  return 'var(--anime-color)';
        case 'manga':  return 'var(--manga-color)';
        case 'novela': return 'var(--novela-color)';
        default:       return 'var(--accent-current)';
    }
}

$page_title   = 'Perfil';
$accent_color = 'general';
$page_css     = ['perfil.css'];
require_once __DIR__ . '/../src/includes/header.php';
?>

<div class="Main">
    <div class="Side">
        <?php $avatar = avatarUrl($user['profile_image']); ?>
        <div class="AvatarCard">
            <div class="AvatarBg" style="background-image:url('<?php echo htmlspecialchars($avatar); ?>')"></div>
            <img src="<?php echo htmlspecialchars($avatar); ?>"
                 class="AvatarImg"
                 onerror="this.onerror=null;this.src='uploads/profiles/default.png'">
            <div class="Name"><?php echo htmlspecialchars($user['username']); ?></div>
            <div class="Meta"><?php echo $user['role'] === 'admin' ? 'Administrador' : 'Usuario'; ?></div>
            <div class="Meta">Miembro desde: <?php echo date('d/m/Y', strtotime($user['created_at'])); ?></div>
            <button class="btn-edit" onclick="toggleModal(true)">Editar Perfil</button>
        </div>
    </div>

    <div class="Content">
        <?php if(isset($_GET['success'])): ?>
            <div class="success-msg">Perfil actualizado correctamente.</div>
        <?php endif; ?>

        <div class="Filters">
            <button class="filter-btn active" onclick="filtrar('todos', this)">Todos</button>
            <button class="filter-btn" onclick="filtrar('anime', this)">Animes</button>
            <button class="filter-btn" onclick="filtrar('manga', this)">Mangas</button>
            <button class="filter-btn" onclick="filtrar('novela', this)">Novelas</button>
        </div>

        <?php foreach ($estados_labels as $estado_key => $estado_label): ?>
        <div class="Section">
            <h2 class="SectionTitle"><?php echo htmlspecialchars($estado_label); ?></h2>
            <div class="Grid">
                <?php if (!empty($por_estado[$estado_key])):
                    foreach ($por_estado[$estado_key] as $item): ?>
                    <a href="<?php echo linkTipo($item['tipo'], $item['id']); ?>"
                       class="Card item-type" data-type="<?php echo $item['tipo']; ?>"
                       style="--item-color:<?php echo colorVar($item['tipo']); ?>">
                        <div class="CardImgWrap">
                            <img src="<?php echo htmlspecialchars($item['imagen']); ?>"
                                 alt="<?php echo htmlspecialchars($item['nombre']); ?>"
                                 onerror="this.onerror=null;this.src='<?php echo DEFAULT_CONTENT_IMG; ?>';this.classList.add('img-placeholder')">
                        </div>
                        <p><?php echo htmlspecialchars($item['nombre']); ?></p>
                    </a>
                <?php endforeach; else: ?>
                    <p class="empty-txt">Vacío</p>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="Section">
            <h2 class="SectionTitle">Mis Favoritos</h2>
            <div class="Grid">
                <?php if($lista_favoritos->num_rows > 0):
                    while($item = $lista_favoritos->fetch_assoc()): ?>
                    <a href="<?php echo linkTipo($item['tipo'], $item['id']); ?>"
                       class="Card item-type" data-type="<?php echo $item['tipo']; ?>"
                       style="--item-color:<?php echo colorVar($item['tipo']); ?>">
                        <div class="CardImgWrap">
                            <img src="<?php echo htmlspecialchars($item['imagen']); ?>"
                                 alt="<?php echo htmlspecialchars($item['nombre']); ?>"
                                 onerror="this.onerror=null;this.src='<?php echo DEFAULT_CONTENT_IMG; ?>';this.classList.add('img-placeholder')">
                        </div>
                        <p><?php echo htmlspecialchars($item['nombre']); ?></p>
                    </a>
                <?php endwhile; else: ?>
                    <p class="empty-txt">Vacío</p>
                <?php endif; ?>
            </div>
        </div>

        <?php if(!empty($progreso)): ?>
        <div class="Section">
            <h2 class="SectionTitle">Mi Progreso</h2>
            <?php foreach($progreso as $p):
                $pct = $p['total'] > 0 ? round(($p['vistos'] / $p['total']) * 100) : 0;
            ?>
            <a href="anime-detalle.php?id=<?php echo $p['id']; ?>" class="ProgressCard" style="--item-color:var(--anime-color)">
                <div class="ProgressImgWrap">
                    <img src="<?php echo htmlspecialchars($p['imagen']); ?>"
                         onerror="this.onerror=null;this.src='<?php echo DEFAULT_CONTENT_IMG; ?>';this.classList.add('img-placeholder')"
                         alt="<?php echo htmlspecialchars($p['nombre']); ?>">
                </div>
                <div class="ProgressInfo">
                    <div class="ProgressName"><?php echo htmlspecialchars($p['nombre']); ?></div>
                    <div class="ProgressBarBg">
                        <div class="ProgressBarFill" style="width:<?php echo $pct; ?>%"></div>
                    </div>
                    <div class="ProgressMeta">
                        <?php echo $p['vistos']; ?> / <?php echo $p['total'] ?: '?'; ?> episodios — <?php echo $pct; ?>%
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>
</div>

<div id="editModal" class="Modal">
    <div class="ModalContent">
        <h3>Editar Perfil</h3>

        <?php if(!empty($update_errors)): ?>
            <div class="error-msg">
                <?php foreach($update_errors as $err): ?>
                    <div><?php echo htmlspecialchars($err); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <label>Nombre de usuario:</label>
            <input type="text" name="username"
                   value="<?php echo htmlspecialchars($_POST['username'] ?? $user['username']); ?>" required>
            <label>Email:</label>
            <input type="email" name="email"
                   value="<?php echo htmlspecialchars($_POST['email'] ?? $user['email']); ?>" required>
            <label>Nueva foto de perfil:</label>
            <div class="FileInput">
                <label for="profile_image" class="FileInput-btn">Elegir archivo</label>
                <span class="FileInput-name" id="fileInputName">Ningún archivo seleccionado</span>
                <input type="file" name="profile_image" id="profile_image" accept="image/*" hidden>
            </div>
            <div style="display:flex; gap:10px; margin-top:15px;">
                <button type="submit" name="update_profile" class="btn-edit" style="flex:1">Guardar</button>
                <button type="button" class="btn-edit" style="flex:1; background:#333;"
                        onclick="toggleModal(false)">Cancelar</button>
            </div>
        </form>

        <hr style="border:none; border-top:1px solid var(--border); margin:20px 0;">
        <p style="font-size:12px; color:#666; margin:0 0 10px;">Zona de peligro</p>
        <button class="btn-delete" onclick="toggleDeleteModal(true)" style="width:100%;">
            Eliminar mi cuenta
        </button>
    </div>
</div>

<div id="deleteModal" class="Modal">
    <div class="ModalContent">
        <h3 style="color:#ff4444;">Eliminar Cuenta</h3>
        <p style="font-size:14px; color:#aaa;">
            Esta acción es <strong>irreversible</strong>. Se eliminará tu cuenta y todos tus datos permanentemente.
        </p>

        <?php if ($delete_error): ?>
            <div class="error-msg"><?php echo htmlspecialchars($delete_error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <label>Confirmá tu contraseña:</label>
            <input type="password" name="confirm_password" required>
            <div style="display:flex; gap:10px; margin-top:15px;">
                <button type="submit" name="delete_account"
                        class="btn-edit" style="flex:1; background:#ff4444;">
                    Sí, eliminar
                </button>
                <button type="button" class="btn-edit" style="flex:1; background:#333;"
                        onclick="toggleDeleteModal(false)">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?>
<script src="js/perfil.js"></script>
<?php if(!empty($update_errors)): ?>
<script>document.addEventListener('DOMContentLoaded', function () { toggleModal(true); });</script>
<?php endif; ?>
<?php if($delete_error): ?>
<script>document.addEventListener('DOMContentLoaded', function () { toggleDeleteModal(true); });</script>
<?php endif; ?>