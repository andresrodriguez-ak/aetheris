<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/app_config.php';
require_once __DIR__ . '/../../config/db_config.php';

$logged_in = isset($_SESSION['user_id']);
$username  = $logged_in ? $_SESSION['username'] : '';
$is_admin  = $logged_in && ($_SESSION['role'] ?? '') === 'admin';
$avatar    = $logged_in ? avatarUrl($_SESSION['profile_image'] ?? null) : '';


if (!isset($accent_color)) {
    $accent_color = 'general';
}

switch ($accent_color) {
    case 'anime':
        $css_accent = 'var(--anime-color)';
        $css_accent_light = 'var(--anime-light)';
        $css_card_bg = 'var(--anime-card-bg)';
        break;
    case 'manga':
        $css_accent = 'var(--manga-color)';
        $css_accent_light = 'var(--manga-light)';
        $css_card_bg = 'var(--manga-card-bg)';
        break;
    case 'novela':
        $css_accent = 'var(--novela-color)';
        $css_accent_light = 'var(--novela-light)';
        $css_card_bg = 'var(--novela-card-bg)';
        break;
    case 'general':
    default:
        $css_accent = 'var(--primary-blue)';
        $css_accent_light = 'var(--primary-blue)';
        $css_card_bg = 'var(--bg-dark-card)';
        break;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo isset($page_title) ? $page_title . " - Aetheris" : "Aetheris - Anime, Manga y Novelas"; ?></title>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600,700" rel="stylesheet">
    <link rel="icon" href="uploads/aetheris.png" type="image/x-icon">
    
    <link rel="stylesheet" href="css/global.css">
    <script src="js/main.js" defer></script>

    <?php if (!empty($page_css)): ?>
        <?php foreach ($page_css as $css_file): ?>
    <link rel="stylesheet" href="css/<?php echo htmlspecialchars($css_file); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <style>
        :root {
            --accent-current: <?php echo $css_accent; ?>;
            --accent-light: <?php echo $css_accent_light; ?>;
            --card-bg-current: <?php echo $css_card_bg; ?>;
        }
    </style>
</head>
<body>

<header>
    <div class="nav-left">
        <a href="index.php" class="logo">AETHERIS</a>
    </div>

    <button class="nav-toggle" id="navToggle" aria-label="Abrir menú" aria-expanded="false" aria-controls="navLinks">
        <span></span><span></span><span></span>
    </button>

    <div class="nav-links" id="navLinks">
        <a href="anime-home.php" class="nav-link"><img src="uploads/content/anni.gif" alt=""> Anime</a>
        <a href="manga-home.php" class="nav-link"><img src="uploads/content/icon_manga.gif" alt=""> Manga</a>
        <a href="novela-home.php" class="nav-link"><img src="uploads/content/icon_novelas.gif" alt=""> Novelas</a>
        <a href="directorio.php" class="nav-link"><img src="uploads/content/icon_folder.gif" alt=""> Directorio</a>

        <form class="nav-search" action="<?php echo BASE_URL; ?>busqueda.php" method="get">
            <input type="text" name="q" id="m-search" placeholder="Buscar..." autocomplete="off"
                   value="<?php echo htmlspecialchars($search_query ?? ''); ?>">
            <button type="submit" aria-label="Buscar">
                <img src="uploads/content/icon_search.png" alt="">
            </button>
        </form>
    </div>

    <div class="user-menu" id="userMenu">
        <?php if ($logged_in): ?>
            <button class="user-menu-trigger" id="userMenuBtn" aria-expanded="false">
                <img src="<?php echo htmlspecialchars($avatar); ?>" alt="<?php echo htmlspecialchars($username); ?>"
                     class="user-avatar" onerror="this.onerror=null;this.src='uploads/profiles/default.png'">
                <span class="user-name"><?php echo htmlspecialchars($username); ?></span>
            </button>
            <div class="user-menu-content" id="userMenuContent">
                <a href="perfil.php"><img src="uploads/content/user.png" alt=""> Mi Perfil</a>
                <a href="perfil.php?ajustes=1"><img src="uploads/content/icon_folder.gif" alt=""> Ajustes</a>
                <a href="<?php echo BASE_URL; ?>src/actions/auth/logout.php"><img src="uploads/content/salir.png" alt=""> Salir</a>
            </div>
        <?php else: ?>
            <a href="login.php" class="user-menu-trigger user-menu-guest" aria-label="Iniciar sesión">
                <img src="uploads/content/user.png" alt="Iniciar sesión" class="user-avatar">
            </a>
        <?php endif; ?>
    </div>
</header>

<div class="Body">
    <div class="Container">