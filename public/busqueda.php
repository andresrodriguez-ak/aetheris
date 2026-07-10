<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 3) {
    header("Location: index.php?error=query_too_short");
    exit();
}

$page_title    = 'Resultados de búsqueda';
$accent_color  = 'general';
$page_css      = ['busqueda.css'];
$search_query  = $query;

require_once __DIR__ . '/../src/includes/header.php';

$search_term       = "%$query%";
$search_term_exact = $query . '%';

$stmt = $conn->prepare("SELECT id, nombre as titulo, 'anime' as tipo, imagen as imagen_portada,
    CASE WHEN nombre LIKE ? THEN 0 ELSE 1 END as relevancia
    FROM animes WHERE nombre LIKE ? OR descripcion LIKE ?
    ORDER BY relevancia ASC, nombre ASC LIMIT 20");
$stmt->bind_param("sss", $search_term_exact, $search_term, $search_term);
$stmt->execute();
$anime_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt = $conn->prepare("SELECT id, nombre as titulo, 'manga' as tipo, imagen as imagen_portada,
    CASE WHEN nombre LIKE ? THEN 0 ELSE 1 END as relevancia
    FROM mangas WHERE nombre LIKE ? OR descripcion LIKE ?
    ORDER BY relevancia ASC, nombre ASC LIMIT 20");
$stmt->bind_param("sss", $search_term_exact, $search_term, $search_term);
$stmt->execute();
$manga_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt = $conn->prepare("SELECT id, nombre as titulo, 'novela' as tipo, imagen as imagen_portada,
    CASE WHEN nombre LIKE ? THEN 0 ELSE 1 END as relevancia
    FROM novelas WHERE nombre LIKE ? OR descripcion LIKE ?
    ORDER BY relevancia ASC, nombre ASC LIMIT 20");
$stmt->bind_param("sss", $search_term_exact, $search_term, $search_term);
$stmt->execute();
$novela_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$search_results = array_merge($anime_results, $manga_results, $novela_results);
usort($search_results, fn($a, $b) => $a['relevancia'] - $b['relevancia']);

$has_results  = !empty($search_results);
$total        = count($search_results);
$total_anime  = count($anime_results);
$total_manga  = count($manga_results);
$total_novela = count($novela_results);
?>

<div class="SearchWrap">

    <div class="search-header">
        <h1>Resultados para: <span class="search-query"><?php echo htmlspecialchars($query); ?></span></h1>
        <p class="search-total">Se encontraron <strong><?php echo $total; ?></strong> resultado<?php echo $total !== 1 ? 's' : ''; ?></p>
        <div class="filter-buttons">
            <button class="filter-button active" data-type="all">Todos (<?php echo $total; ?>)</button>
            <button class="filter-button" data-type="anime">Anime (<?php echo $total_anime; ?>)</button>
            <button class="filter-button" data-type="manga">Manga (<?php echo $total_manga; ?>)</button>
            <button class="filter-button" data-type="novela">Novelas (<?php echo $total_novela; ?>)</button>
        </div>
    </div>

    <div class="search-results" id="searchResults" data-query="<?php echo htmlspecialchars($query, ENT_QUOTES); ?>">
        <?php if ($has_results): ?>
            <?php foreach ($search_results as $result): ?>
                <a href="<?php echo $result['tipo']; ?>-detalle.php?id=<?php echo $result['id']; ?>"
                   class="result-item" data-type="<?php echo $result['tipo']; ?>">

                    <div class="ResultImgWrap">
                        <img src="<?php echo !empty($result['imagen_portada']) ? htmlspecialchars($result['imagen_portada']) : DEFAULT_CONTENT_IMG; ?>"
                             alt="<?php echo htmlspecialchars($result['titulo']); ?>"
                             class="result-image"
                             onerror="this.onerror=null; this.src='<?php echo DEFAULT_CONTENT_IMG; ?>'; this.classList.add('img-placeholder');">
                    </div>

                    <div class="result-info">
                        <h3 class="result-title" data-raw="<?php echo htmlspecialchars($result['titulo']); ?>">
                            <?php echo htmlspecialchars($result['titulo']); ?>
                        </h3>
                        <span class="result-type"><?php echo ucfirst($result['tipo']); ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-results">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="M21 21l-4.35-4.35" stroke-linecap="round"/>
                </svg>
                <p>Sin resultados para "<?php echo htmlspecialchars($query); ?>"</p>
                <p>Intenta con términos diferentes o más específicos.</p>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?>