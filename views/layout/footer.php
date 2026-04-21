<?php if ($page === 'app'): ?>
    <script>
        const appState = <?php echo json_encode($page_data['save'] ?? []); ?>;
    </script>
    <script src="assets/app.js"></script>
<?php endif; ?>
</body>
</html>
