<?php
/**
 * FANTASY FC - ARQUITETURA SINGLE-FILE PHP (NÍVEL PLENO/SÊNIOR)
 * Dinâmico com BD, Mercado, Dashboard, CRUD de Jogadores e Cap.
 */
session_start();
date_default_timezone_set('America/Sao_Paulo');

// ------------------------------------------------------------------
// 0. CONEXÃO PDO E MIGRATIONS (Setup do Banco)
// ------------------------------------------------------------------
$db_host = 'localhost';
$db_name = 'u289267434_u289267434_fut';
$db_user = 'u289267434_u289267434_fut';
$db_pass = 'Tu#@EX/K>&=2';

$pdo = null;
$db_connected = false;

function runMigrations($pdo) {
    // Adicionamos 'idade' na tabela jogador
    $sql = "
    <?php
    session_start();
    date_default_timezone_set('America/Sao_Paulo');

    require __DIR__ . '/config.php';
    require __DIR__ . '/migrations.php';

    if ($db_connected) {
        runMigrations($pdo);
        seedDatabaseIfNeeded($pdo);
    }

    require __DIR__ . '/actions.php';

    $page = $_GET['page'] ?? 'login';
    if ($page === 'app' && !isset($_SESSION['logged_in'])) {
        header("Location: ?page=login");
        exit;
    }

    require __DIR__ . '/data.php';

    require __DIR__ . '/views/layout/header.php';
    if ($page === 'app') {
        require __DIR__ . '/views/app.php';
    } else {
        require __DIR__ . '/views/login.php';
    }
    require __DIR__ . '/views/layout/footer.php';