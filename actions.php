<?php
$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        if ($db_connected) {
            $stmt = $pdo->prepare("SELECT id, nome_do_time, senha FROM gm WHERE email = ?");
            $stmt->execute([$_POST['email']]);
            $gm = $stmt->fetch();
            if ($gm && password_verify($_POST['senha'], $gm['senha'])) {
                $_SESSION['logged_in'] = true;
                $_SESSION['gm_id'] = $gm['id'];
                $_SESSION['gm_name'] = $gm['nome_do_time'];
                header("Location: ?page=app");
                exit;
            }
            $msg = "Credenciais invalidas.";
            $msgType = "error";
        } else {
            $_SESSION['logged_in'] = true;
            $_SESSION['gm_id'] = 1;
            $_SESSION['gm_name'] = "Modo Offline";
            header("Location: ?page=app");
            exit;
        }
    }

    if ($action === 'logout') {
        session_destroy();
        header("Location: ?page=login");
        exit;
    }

    if ($action === 'salvar_jogador' && (!$db_connected || !isset($_SESSION['gm_id']))) {
        header("Location: ?page=app&tab=team&error=db");
        exit;
    }
    if ($action === 'salvar_jogador' && $db_connected && isset($_SESSION['gm_id'])) {
        $id = !empty($_POST['jogador_id']) ? (int)$_POST['jogador_id'] : null;
        $nome = $_POST['nome'];
        $posicao = $_POST['posicao'];
        $overall = (int)$_POST['overall'];
        $idade = (int)$_POST['idade'];
        $gm_id = $_SESSION['gm_id'];

        if ($id) {
            $stmt = $pdo->prepare("UPDATE jogador j JOIN elenco e ON j.id = e.jogador_id SET j.nome=?, j.posicao=?, j.overall=?, j.idade=? WHERE j.id=? AND e.gm_id=?");
            $stmt->execute([$nome, $posicao, $overall, $idade, $id, $gm_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO jogador (nome, posicao, overall, idade) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nome, $posicao, $overall, $idade]);
            $novo_id = $pdo->lastInsertId();
            $pdo->prepare("INSERT INTO elenco (gm_id, jogador_id, is_titular) VALUES (?, ?, 0)")->execute([$gm_id, $novo_id]);
        }
        header("Location: ?page=app&tab=team&manage=1");
        exit;
    }

    if ($action === 'deletar_jogador' && $db_connected && isset($_SESSION['gm_id'])) {
        $id = (int)$_POST['jogador_id'];
        $stmt = $pdo->prepare("DELETE j FROM jogador j JOIN elenco e ON j.id = e.jogador_id WHERE j.id = ? AND e.gm_id = ?");
        $stmt->execute([$id, $_SESSION['gm_id']]);
        header("Location: ?page=app&tab=team&manage=1");
        exit;
    }

    if ($action === 'atualizar_titulares' && $db_connected && isset($_SESSION['gm_id'])) {
        $gm_id = $_SESSION['gm_id'];
        $raw = $_POST['titulares'] ?? '';
        $ids = array_filter(array_map('intval', explode(',', $raw)));
        $ids = array_values(array_unique($ids));
        $ids = array_slice($ids, 0, 11);

        $valid_ids = [];
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $params = $ids;
            array_unshift($params, $gm_id);
            $stmt = $pdo->prepare("SELECT j.id FROM jogador j JOIN elenco e ON j.id = e.jogador_id WHERE e.gm_id = ? AND j.id IN ($placeholders)");
            $stmt->execute($params);
            $valid_ids = array_map(fn($r) => (int)$r['id'], $stmt->fetchAll());
        }

        $pdo->prepare("UPDATE elenco SET is_titular = 0 WHERE gm_id = ?")->execute([$gm_id]);
        if (!empty($valid_ids)) {
            $placeholders = implode(',', array_fill(0, count($valid_ids), '?'));
            $params = $valid_ids;
            array_unshift($params, $gm_id);
            $pdo->prepare("UPDATE elenco SET is_titular = 1 WHERE gm_id = ? AND jogador_id IN ($placeholders)")->execute($params);
        }

        header("Location: ?page=app&tab=team");
        exit;
    }

    if ($action === 'criar_leilao' && $db_connected) {
        $jogador_id = (int)$_POST['jogador_id'];
        $gm_id = $_SESSION['gm_id'];
        $stmt = $pdo->prepare("SELECT j.overall FROM jogador j JOIN elenco e ON j.id = e.jogador_id WHERE j.id = ? AND e.gm_id = ?");
        $stmt->execute([$jogador_id, $gm_id]);
        if ($jog = $stmt->fetch()) {
            if ($jog['overall'] >= 88) {
                $pdo->prepare("INSERT INTO leilao (jogador_id, gm_vendedor_id, data_fim) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 20 MINUTE))")->execute([$jogador_id, $gm_id]);
            }
        }
        header("Location: ?page=app&tab=market");
        exit;
    }

    if ($action === 'avancar_sprint' && $db_connected) {
        $pdo->exec("UPDATE config_sistema SET sprint_atual = sprint_atual + 1");
        $sprint = $pdo->query("SELECT sprint_atual FROM config_sistema")->fetchColumn();
        if ($sprint % 2 != 0) {
            $pdo->exec("UPDATE gm SET trade_count = 0");
        }
        header("Location: ?page=app&tab=admin");
        exit;
    }
}
