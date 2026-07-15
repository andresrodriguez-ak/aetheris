<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/app_config.php';
require_once __DIR__ . '/../../../config/db_config.php';

$logged_in = isset($_SESSION['user_id']);
$username  = $logged_in ? $_SESSION['username'] : '';
$is_admin  = $logged_in && ($_SESSION['role'] ?? '') === 'admin';

if (!isset($accent_color)) {
    $accent_color = 'general';
}

switch ($accent_color) {
    case 'anime':
        $css_accent = 'var(--anime-color)';
        break;
    case 'manga':
        $css_accent = 'var(--manga-color)';
        break;
    case 'novela':
        $css_accent = 'var(--novela-color)';
        break;
    case 'general':
    default:
        $css_accent = 'var(--primary-blue)';
        break;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo isset($page_title) ? $page_title . " - Aetheris Admin" : "Aetheris - Panel de Administración"; ?></title>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600,700" rel="stylesheet">
    <link rel="icon" href="<?php echo BASE_URL; ?>public/uploads/aetheris.png" type="image/x-icon">

    <!-- Base compartida con public: variables, tipografía, reset, header/menú -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/global.css">

    <!-- Estilos propios de admin -->
    <link rel="stylesheet" href="css/admin.css">
    <?php if (!empty($admin_css)): ?>
        <?php foreach ($admin_css as $css_file): ?>
    <link rel="stylesheet" href="css/<?php echo htmlspecialchars($css_file); ?>">
        <?php endforeach; ?>
    <?php endif; ?>

    <style>
        :root {
            --accent-current: <?php echo $css_accent; ?>;
        }
    </style>
</head>
<body>

<header>
    <div class="header-left">
        <a href="<?php echo BASE_URL; ?>public/index.php" class="logo">Aetheris</a>
    </div>
    <div class="header-right">
        <div class="menu">
            <button class="menu-button" id="menuBtn">Menú ▼</button>
            <div class="menu-content" id="menuContent">
                <div class="menu-search">
                    <input type="text" id="m-search" placeholder="Buscar..." autocomplete="off">
                    <button type="button">
                        <img src="<?php echo BASE_URL; ?>public/uploads/content/icon_search.png" alt="Buscar">
                    </button>
                </div>
                <a href="<?php echo BASE_URL; ?>public/anime-home.php"><img src="<?php echo BASE_URL; ?>public/uploads/content/anni.gif"> Anime</a>
                <a href="<?php echo BASE_URL; ?>public/manga-home.php"><img src="<?php echo BASE_URL; ?>public/uploads/content/icon_manga.gif"> Manga</a>
                <a href="<?php echo BASE_URL; ?>public/novela-home.php"><img src="<?php echo BASE_URL; ?>public/uploads/content/icon_novelas.gif"> Novelas</a>
                <a href="<?php echo BASE_URL; ?>public/directorio.php"><img src="<?php echo BASE_URL; ?>public/uploads/content/icon_folder.gif"> Directorio</a>

                <?php if ($is_admin): ?>
                    <a href="subir_contenido.php" style="color: #ff9e00;">
                        <img src="<?php echo BASE_URL; ?>public/uploads/content/subir.png"> Panel Admin
                    </a>
                <?php endif; ?>

                <?php if ($logged_in): ?>
                    <a href="<?php echo BASE_URL; ?>public/perfil.php">
                        <img src="<?php echo BASE_URL; ?>public/uploads/content/user.png"> Mi Perfil (<?php echo htmlspecialchars($username); ?>)
                    </a>
                    <a href="<?php echo BASE_URL; ?>src/actions/auth/logout.php">
                        <img src="<?php echo BASE_URL; ?>public/uploads/content/salir.png"> Salir
                    </a>
                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>public/login.php"><img src="<?php echo BASE_URL; ?>public/uploads/content/user.png"> Iniciar Sesión</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<div class="Body">
    <div class="Container">