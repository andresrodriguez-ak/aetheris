<?php
/* ═══════════════════════════════════════════════════════════════
   Aetheris — acciones-contenido.php
   Endpoint AJAX: favorito y estado de seguimiento (anime/manga/novela).
   ═══════════════════════════════════════════════════════════════ */

ob_start();
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../../../config/db_config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

ob_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'msg' => 'no_login']);
    exit;
}

// Allowlist: evita interpolar cualquier string arbitrario como nombre de columna
$tipo_map = ['anime' => 'anime_id', 'manga' => 'manga_id', 'novela' => 'novela_id'];
$tipo = $_POST['tipo'] ?? '';

if (!isset($tipo_map[$tipo])) {
    echo json_encode(['success' => false, 'msg' => 'tipo_invalido']);
    exit;
}
$col = $tipo_map[$tipo];

$user_id    = (int)$_SESSION['user_id'];
$content_id = (int)($_POST['id'] ?? 0);
$action     = $_POST['action'] ?? '';

if (!$content_id) {
    echo json_encode(['success' => false, 'msg' => 'id_invalido']);
    exit;
}

if ($action === 'toggle_favorito') {
    $check = $conn->prepare("SELECT id, es_favorito FROM favoritos WHERE user_id=? AND $col=?");
    $check->bind_param("ii", $user_id, $content_id);
    $check->execute();
    $row = $check->get_result()->fetch_assoc();

    if ($row) {
        $nuevo = $row['es_favorito'] ? 0 : 1;
        $upd = $conn->prepare("UPDATE favoritos SET es_favorito=? WHERE id=?");
        $upd->bind_param("ii", $nuevo, $row['id']);
        $upd->execute();
        echo json_encode(['success' => true, 'active' => (bool)$nuevo]);
    } else {
        $ins = $conn->prepare("INSERT INTO favoritos (user_id, $col, es_favorito) VALUES (?,?,1)");
        $ins->bind_param("ii", $user_id, $content_id);
        $ins->execute();
        echo json_encode(['success' => true, 'active' => true]);
    }
    exit;
}

if ($action === 'cambiar_estado') {
    $estados_validos = ['viendo', 'por_ver', 'completado', 'pausado', 'descartado', ''];
    $estado = $_POST['estado'] ?? '';

    if (!in_array($estado, $estados_validos, true)) {
        echo json_encode(['success' => false, 'msg' => 'estado_invalido']);
        exit;
    }

    $check = $conn->prepare("SELECT id FROM favoritos WHERE user_id=? AND $col=?");
    $check->bind_param("ii", $user_id, $content_id);
    $check->execute();
    $row = $check->get_result()->fetch_assoc();

    if ($row) {
        $upd = $conn->prepare("UPDATE favoritos SET estado_seguimiento=? WHERE id=?");
        $upd->bind_param("si", $estado, $row['id']);
        $upd->execute();
    } else {
        $ins = $conn->prepare("INSERT INTO favoritos (user_id, $col, estado_seguimiento) VALUES (?,?,?)");
        $ins->bind_param("iis", $user_id, $content_id, $estado);
        $ins->execute();
    }
    echo json_encode(['success' => true, 'estado' => $estado]);
    exit;
}

echo json_encode(['success' => false, 'msg' => 'accion_invalida']);