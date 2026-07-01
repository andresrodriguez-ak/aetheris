<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/db_config.php';

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
<!-- DEBUG page_css: <?php echo isset($page_css) ? implode(',', $page_css) : 'NO DEFINIDA'; ?> -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo isset($page_title) ? $page_title . " - Aetheris" : "Aetheris - Anime, Manga y Novelas"; ?></title>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600,700" rel="stylesheet">
    <link rel="icon" href="uploads/aetheris.png" type="image/x-icon">
    
    <link rel="stylesheet" href="css/global.css">

    <?php if (!empty($page_css)): ?>
        <?php foreach ($page_css as $css_file): ?>
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
        <a href="index.php" class="logo">Aetheris</a>
    </div>
    <div class="header-right">
        <div class="menu">
            <button class="menu-button" id="menuBtn">Menú ▼</button>
            <div class="menu-content" id="menuContent">
                <div class="menu-search">
                    <input type="text" id="m-search" placeholder="Buscar..." autocomplete="off">
                    <button type="button">
                        <img src="uploads/content/icon_search.png" alt="Buscar">
                    </button>
                </div>
                <a href="anime-home.php"><img src="uploads/content/anni.gif"> Anime</a>
                <a href="manga-home.php"><img src="uploads/content/icon_manga.gif"> Manga</a>
                <a href="novela-home.php"><img src="uploads/content/icon_novelas.gif"> Novelas</a>
                <a href="directorio.php"><img src="uploads/content/icon_folder.gif"> Directorio</a>
                <?php if($is_admin): ?>
                    <a href="admin_dashboard.php" style="color:#4a8eff;">
                        <img src="uploads/content/subir.png"> Panel Admin
                    </a>
                <?php endif; ?>
                <?php if($logged_in): ?>
                    <a href="perfil.php">
                        <img src="uploads/content/user.png"> Mi Perfil (<?php echo htmlspecialchars($username); ?>)
                    </a>
                    <a href="../src/actions/auth/logout.php">
                        <img src="uploads/content/salir.png"> Salir
                    </a>
                <?php else: ?>
                    <a href="login.php"><img src="uploads/content/user.png"> Iniciar Sesión</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<div class="Body">
    <div class="Container">