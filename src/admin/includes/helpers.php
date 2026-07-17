<?php
/* ═══════════════════════════════════════════════════════════════
   Aetheris Admin — helpers.php
   Funciones compartidas por los formularios de carga/edición de
   contenido en src/admin/ (subir_anime.php, subir_episodio.php,
   subir_manga.php, subir_capitulo.php, subir_novela.php,
   subir_volumen.php).
   ═══════════════════════════════════════════════════════════════ */

function guardarImagenComoWebp(array $archivo, string $carpetaRelativa): ?string
{
    if (!isset($archivo['tmp_name']) || $archivo['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $info = getimagesize($archivo['tmp_name']);
    if ($info === false) {
        return null;
    }

    switch ($info['mime']) {
        case 'image/jpeg':
            $imagen = imagecreatefromjpeg($archivo['tmp_name']);
            break;
        case 'image/png':
            $imagen = imagecreatefrompng($archivo['tmp_name']);
            if ($imagen !== false) {
                imagepalettetotruecolor($imagen);
                imagealphablending($imagen, true);
                imagesavealpha($imagen, true);
            }
            break;
        case 'image/webp':
            $imagen = imagecreatefromwebp($archivo['tmp_name']);
            break;
        default:
            return null;
    }

    if ($imagen === false) {
        return null;
    }

    $carpetaAbsoluta = __DIR__ . '/../../../public/' . $carpetaRelativa;
    if (!is_dir($carpetaAbsoluta)) {
        mkdir($carpetaAbsoluta, 0755, true);
    }

    $nombreArchivo = uniqid() . '.webp';
    $rutaAbsoluta  = $carpetaAbsoluta . $nombreArchivo;

    $guardado = imagewebp($imagen, $rutaAbsoluta, 85);
    imagedestroy($imagen);

    if (!$guardado) {
        return null;
    }

    return $carpetaRelativa . $nombreArchivo;
}
