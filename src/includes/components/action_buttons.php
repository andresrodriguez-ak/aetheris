<?php
/* ═══════════════════════════════════════════════════════════════
   Aetheris — action_buttons.php
   Componente: botón favorito + dropdown de estado.
   Espera: $tipo, $content_id, $es_favorito, $estado_actual
   ═══════════════════════════════════════════════════════════════ */

$tipo          = $tipo ?? 'anime';
$content_id    = $content_id ?? 0;
$es_favorito   = $es_favorito ?? false;
$estado_actual = $estado_actual ?? '';
?>
<div class="content-actions" data-tipo="<?php echo htmlspecialchars($tipo); ?>"
     data-id="<?php echo (int)$content_id; ?>">

    <button type="button" class="btn-favorito <?php echo $es_favorito ? 'is-active' : ''; ?>"
            data-fav-btn aria-label="Favorito">
        <svg class="fav-icon" viewBox="0 0 24 24"
             fill="<?php echo $es_favorito ? 'currentColor' : 'none'; ?>"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
        </svg>
        <span class="fav-label"><?php echo $es_favorito ? 'En favoritos' : 'Favorito'; ?></span>
    </button>

    <div class="estado-dropdown" data-estado-dropdown data-current="<?php echo htmlspecialchars($estado_actual); ?>">
        <button type="button" class="estado-trigger" data-estado-trigger>
            <span class="estado-trigger-label" data-estado-label>Elegí un estado...</span>
            <svg class="estado-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="m6 9 6 6 6-6"/>
            </svg>
        </button>
        <div class="estado-menu" data-estado-menu></div>
    </div>

</div>