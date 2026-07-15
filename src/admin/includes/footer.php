</div>
</div>

<footer>
    <p>Aetheris &copy; <?php echo date('Y'); ?> — Gestión Administrativa</p>
</footer>

<script>window.BASE_URL = "<?php echo BASE_URL; ?>";</script>
<script src="js/admin.js"></script>
<?php if (!empty($admin_js)): ?>
    <?php foreach ($admin_js as $js_file): ?>
<script src="js/<?php echo htmlspecialchars($js_file); ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>