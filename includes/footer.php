        </div><!-- /.main-content -->
    </div><!-- /.app-wrapper -->
    
    <!-- Core JS -->
    <script src="<?= BASE_URL ?>/assets/js/app.js"></script>
    
    <!-- Additional JS -->
    <?php if (isset($extraJS)): ?>
        <?php foreach ($extraJS as $js): ?>
            <script src="<?= $js ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Inline Scripts -->
    <?php if (isset($inlineJS)): ?>
        <script><?= $inlineJS ?></script>
    <?php endif; ?>
</body>
</html>
