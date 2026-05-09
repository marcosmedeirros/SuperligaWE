<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');

require __DIR__ . '/config.php';
require __DIR__ . '/engine/helpers.php';
require __DIR__ . '/migrations.php';

if ($db_connected) {
    runMigrations($pdo);
}

require __DIR__ . '/actions.php';

$page = $_GET['page'] ?? 'login';

$paginas_protegidas = ['saves', 'app'];
$requer_save        = ['app'];

if (in_array($page, $paginas_protegidas) && !isset($_SESSION['ecofut_logado'])) {
    header("Location: ?page=login"); exit;
}

if (in_array($page, $requer_save) && !isset($_SESSION['ecofut_save_id'])) {
    header("Location: ?page=saves"); exit;
}

if (in_array($page, ['login', 'register']) && isset($_SESSION['ecofut_logado'])) {
    header("Location: ?page=saves"); exit;
}

require __DIR__ . '/data.php';
require __DIR__ . '/views/layout/header.php';

switch ($page) {
    case 'register': require __DIR__ . '/views/register.php'; break;
    case 'saves':    require __DIR__ . '/views/saves.php';    break;
    case 'app':      require __DIR__ . '/views/app.php';      break;
    default:         require __DIR__ . '/views/login.php';
}

require __DIR__ . '/views/layout/footer.php';
