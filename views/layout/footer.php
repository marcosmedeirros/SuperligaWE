<?php if ($page === 'app'): ?>
    <script>
        const appState = <?php echo json_encode($app_data); ?>;
    </script>
    <script src="assets/app.js"></script>
<?php endif; ?>
</body>
</html>
