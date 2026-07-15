<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/app_config.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_to'] = 'subir_contenido.php';
    header("Location: " . BASE_URL . "public/login.php");
    exit();
}

// Verificar si es administrador
if (($_SESSION['role'] ?? '') !== 'admin') {
    $_SESSION['error'] = "Acceso restringido a administradores";
    header("Location: " . BASE_URL . "public/index.php");
    exit();
}

$page_title = 'Subir Contenido';

require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-panel">
    <h1 class="admin-title">Panel de Administración</h1>
    <p class="admin-subtitle">Bienvenido, <?php echo htmlspecialchars($username); ?></p>

    <?php if (isset($_SESSION['exito'])): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($_SESSION['exito']); unset($_SESSION['exito']); ?>
        </div>
    <?php endif; ?>

    <div class="upload-options">

        <!-- Gestión de Anime -->
        <div class="upload-option" data-type="anime">
            <img src="<?php echo BASE_URL; ?>public/uploads/content/anni.gif" class="upload-icon-img" alt="Anime">
            <h3 class="upload-title">Gestión de Anime</h3>
            <p class="upload-description">Administra series, añade episodios o edita episodios existentes.</p>
            <div class="upload-actions">
                <a href="subir_anime.php" class="upload-button">Nueva Serie</a>
                <a href="subir_episodio.php" class="upload-button">Nuevo Episodio</a>
                <a href="lista_episodios.php" class="upload-button">Editar Episodios</a>
            </div>
        </div>

        <!-- Gestión de Manga -->
        <div class="upload-option" data-type="manga">
            <img src="<?php echo BASE_URL; ?>public/uploads/content/icon_manga.gif" class="upload-icon-img" alt="Manga">
            <h3 class="upload-title">Gestión de Manga</h3>
            <p class="upload-description">Administra series, añade capítulos o edita capítulos existentes.</p>
            <div class="upload-actions">
                <a href="subir_manga.php" class="upload-button">Nueva Serie</a>
                <a href="subir_capitulo.php" class="upload-button">Nuevo Capítulo</a>
                <a href="lista_capitulos.php" class="upload-button">Editar Capítulos</a>
            </div>
        </div>

        <!-- Gestión de Novelas -->
        <div class="upload-option" data-type="novela">
            <img src="<?php echo BASE_URL; ?>public/uploads/content/icon_novelas.gif" class="upload-icon-img" alt="Novelas">
            <h3 class="upload-title">Gestión de Novelas</h3>
            <p class="upload-description">Administra series, añade volúmenes o edita volúmenes existentes.</p>
            <div class="upload-actions">
                <a href="subir_novela.php" class="upload-button">Nueva Serie</a>
                <a href="subir_volumen.php" class="upload-button">Nuevo Volumen</a>
                <a href="lista_volumenes.php" class="upload-button">Editar Volúmenes</a>
            </div>
        </div>

    </div>

    <!-- Botón de Edición General -->
    <div class="panel-footer">
        <a href="editar_contenido.php" class="upload-button" style="padding: 13px 38px; font-size: 14px; text-transform: uppercase; letter-spacing: .5px;">
            Editar Contenido General
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>