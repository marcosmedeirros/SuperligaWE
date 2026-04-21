<?php
$page_data = [];

if ($page === 'saves' && $db_connected && isset($_SESSION['ecofut_usuario_id'])) {
    $uid = (int) $_SESSION['ecofut_usuario_id'];
    $stmt = $pdo->prepare("SELECT slot, nome_treinador, nome_time, temporada, saldo, updated_at FROM ecofut_saves WHERE usuario_id = ? ORDER BY slot ASC");
    $stmt->execute([$uid]);
    $saves_rows = $stmt->fetchAll();

    $saves = [1 => null, 2 => null];
    foreach ($saves_rows as $row) {
        $saves[(int)$row['slot']] = $row;
    }
    $page_data['saves'] = $saves;
}

if ($page === 'app' && $db_connected && isset($_SESSION['ecofut_save_id'])) {
    $save_id = (int) $_SESSION['ecofut_save_id'];
    $stmt = $pdo->prepare("SELECT * FROM ecofut_saves WHERE id = ?");
    $stmt->execute([$save_id]);
    $page_data['save'] = $stmt->fetch();
    if ($page_data['save']) {
        $page_data['save']['dados'] = json_decode($page_data['save']['dados_json'] ?? '{}', true);
    }
}
