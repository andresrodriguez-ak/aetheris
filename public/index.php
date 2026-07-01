<?php

$page_title = "Anime, Manga y Novelas";
$accent_color = "general";
$page_css = ['animations.css', 'home.css'];

require_once __DIR__ . '/../src/includes/header.php';

$banner_imgs = [];
$res = $conn->query("SELECT imagen FROM banner ORDER BY orden ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $banner_imgs[] = $row['imagen'];
    }
}

if (empty($banner_imgs)) {
    for ($i = 1; $i <= 9; $i++) {
        $banner_imgs[] = "uploads/banner/{$i}.jpg";
    }
}
$banner_json = json_encode($banner_imgs);
?>

<canvas id="sakura-overlay"></canvas>

<script>
    window.AetherisBannerCovers = <?php echo $banner_json; ?>;
</script>

<div class="welcome-section" id="bannerSection">
    <canvas id="particles-canvas"></canvas>
    <div class="welcome-carousel">
        <div class="orbit-stage" id="orbitStage"></div>
    </div>
    <div class="banner-content" id="bannerContent">
        <h1 class="welcome-title">Bienvenido a <span>Aetheris</span></h1>
        <p class="welcome-message">
            Tu portal definitivo para el mundo del entretenimiento japonés.
            Explora anime, manga y novelas ligeras en un solo lugar.
        </p>
    </div>
</div>

<div class="Title-Section">
    <img src="uploads/content/icon_folder_mini.png" style="width:24px; filter:invert(53%) sepia(93%) saturate(1471%) hue-rotate(193deg);">
    Nuestras Categorías
</div>

<div class="section-cards">

    <div class="section-card anime" onclick="window.location.href='anime-home.php'">
        <canvas class="card-stars-canvas" data-color="#9d4edd"></canvas>
        <img src="uploads/content/anni.gif" class="section-icon-img">
        <h3 class="section-title">Anime</h3>
        <p class="section-description">Mira los mejores títulos, desde estrenos de temporada hasta los clásicos inmortales.</p>
        <button class="section-button">Ir a Anime</button>
    </div>

    <div class="section-card manga" onclick="window.location.href='manga-home.php'">
        <canvas class="card-stars-canvas" data-color="#dd4e4e"></canvas>
        <img src="uploads/content/icon_manga.gif" class="section-icon-img">
        <h3 class="section-title">Manga</h3>
        <p class="section-description">Lee los últimos capítulos de tus series favoritas con la mejor calidad visual.</p>
        <button class="section-button">Ir a Manga</button>
    </div>

    <div class="section-card novela" onclick="window.location.href='novela-home.php'">
        <canvas class="card-stars-canvas" data-color="#00c4a0"></canvas>
        <img src="uploads/content/icon_novelas.gif" class="section-icon-img">
        <h3 class="section-title">Novelas Ligeras</h3>
        <p class="section-description">Sumérgete en historias profundas de fantasía, isekai y mucho más.</p>
        <button class="section-button">Ir a Novelas</button>
    </div>

</div>

<?php
require_once __DIR__ . '/../src/includes/footer.php';
?>