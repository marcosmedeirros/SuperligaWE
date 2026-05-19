<?php
$msg      = '';
$msg_tipo = '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

$action = $_POST['action'] ?? '';

// ── REGISTER ──────────────────────────────────────────────────────────────────
if ($action === 'register') {
    if (!$db_connected) { $msg = 'Banco de dados indisponível.'; $msg_tipo = 'erro'; goto fim; }

    $nome  = trim($_POST['nome'] ?? '');
    $email = trim(strtolower($_POST['email'] ?? ''));
    $senha = $_POST['senha'] ?? '';
    $conf  = $_POST['confirmar_senha'] ?? '';

    if (strlen($nome) < 2)                              { $msg = 'Nome muito curto.'; $msg_tipo = 'erro'; goto fim; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))     { $msg = 'E-mail inválido.'; $msg_tipo = 'erro'; goto fim; }
    if (strlen($senha) < 6)                             { $msg = 'Senha deve ter ao menos 6 caracteres.'; $msg_tipo = 'erro'; goto fim; }
    if ($senha !== $conf)                               { $msg = 'As senhas não coincidem.'; $msg_tipo = 'erro'; goto fim; }

    $stmt = $pdo->prepare("SELECT id FROM ecofut_usuarios WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) { $msg = 'Este e-mail já está cadastrado.'; $msg_tipo = 'erro'; goto fim; }

    $hash = password_hash($senha, PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO ecofut_usuarios (nome, email, senha) VALUES (?, ?, ?)")->execute([$nome, $email, $hash]);

    $_SESSION['ecofut_reg_ok'] = 'Conta criada com sucesso! Faça login.';
    header("Location: ?page=login");
    exit;
}

// ── LOGIN ─────────────────────────────────────────────────────────────────────
if ($action === 'login') {
    if (!$db_connected) { $msg = 'Banco de dados indisponível.'; $msg_tipo = 'erro'; goto fim; }

    $email = trim(strtolower($_POST['email'] ?? ''));
    $senha = $_POST['senha'] ?? '';

    $stmt = $pdo->prepare("SELECT id, nome, senha FROM ecofut_usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if (!$usuario || !password_verify($senha, $usuario['senha'])) {
        $msg = 'E-mail ou senha incorretos.'; $msg_tipo = 'erro'; goto fim;
    }

    $_SESSION['ecofut_logado']       = true;
    $_SESSION['ecofut_usuario_id']   = $usuario['id'];
    $_SESSION['ecofut_usuario_nome'] = $usuario['nome'];

    header("Location: ?page=saves");
    exit;
}

// ── LOGOUT ────────────────────────────────────────────────────────────────────
if ($action === 'logout') {
    session_destroy();
    header("Location: ?page=login");
    exit;
}

// ── NOVO SAVE ─────────────────────────────────────────────────────────────────
if ($action === 'novo_save') {
    if (!$db_connected || !isset($_SESSION['ecofut_logado'])) {
        header("Location: ?page=saves"); exit;
    }

    $usuario_id     = (int)$_SESSION['ecofut_usuario_id'];
    $slot           = (int)($_POST['slot'] ?? 1);
    $nome_treinador = trim($_POST['nome_treinador'] ?? '');
    $time_id        = (int)($_POST['time_id'] ?? 0);

    if ($slot < 1 || $slot > 2)      { $msg = 'Slot inválido.'; $msg_tipo = 'erro'; goto fim; }
    if (strlen($nome_treinador) < 2) { $msg = 'Nome do treinador muito curto.'; $msg_tipo = 'erro'; goto fim; }
    if ($time_id <= 0)               { $msg = 'Selecione um time para continuar.'; $msg_tipo = 'erro'; goto fim; }

    // Valida que o usuário da sessão existe no banco atual
    $stmtUser = $pdo->prepare("SELECT id FROM ecofut_usuarios WHERE id = ?");
    $stmtUser->execute([$usuario_id]);
    if (!$stmtUser->fetch()) {
        session_destroy();
        header("Location: ?page=login"); exit;
    }

    // Verifica se time existe
    $stmtTime = $pdo->prepare("SELECT nome, cor1 FROM ecofut_times WHERE id = ?");
    $stmtTime->execute([$time_id]);
    $timeRow = $stmtTime->fetch();
    if (!$timeRow) { $msg = 'Time não encontrado.'; $msg_tipo = 'erro'; goto fim; }

    $nome_time = $timeRow['nome'];

    try {
        // Apaga dados do slot anterior (cascata cuida de elenco, partidas, etc.)
        $saveAnt = $pdo->prepare("SELECT id FROM ecofut_saves WHERE usuario_id = ? AND slot = ?");
        $saveAnt->execute([$usuario_id, $slot]);
        $antRow = $saveAnt->fetch();
        if ($antRow) {
            $pdo->prepare("DELETE FROM ecofut_saves WHERE id = ?")->execute([$antRow['id']]);
        }

        $dados_iniciais = json_encode([
            'formacao' => '4-3-3',
            'estilo'   => 'equilibrado',
        ]);

        $stmt = $pdo->prepare(
            "INSERT INTO ecofut_saves
             (usuario_id, slot, nome_treinador, nome_time, time_id, temporada, rodada_atual, saldo, dados_json)
             VALUES (?, ?, ?, ?, ?, 1, 1, 10000000, ?)"
        );
        $stmt->execute([$usuario_id, $slot, $nome_treinador, $nome_time, $time_id, $dados_iniciais]);
        $save_id = (int)$pdo->lastInsertId();

        // Inicializa temporada (elenco, fixtures, classificação)
        require_once __DIR__ . '/engine/season.php';
        inicializarTemporada($pdo, $save_id, $time_id);

        $_SESSION['ecofut_save_id']   = $save_id;
        $_SESSION['ecofut_save_slot'] = $slot;
        $_SESSION['ecofut_nome_time'] = $nome_time;

        header("Location: ?page=app");
        exit;
    } catch (Exception $e) {
        error_log('EcoFut novo_save error: ' . $e->getMessage());
        $msg = 'Erro ao criar save: ' . $e->getMessage();
        $msg_tipo = 'erro';
        goto fim;
    }
}

// ── CARREGAR SAVE ─────────────────────────────────────────────────────────────
if ($action === 'carregar_save') {
    if (!$db_connected || !isset($_SESSION['ecofut_logado'])) {
        header("Location: ?page=saves"); exit;
    }

    $usuario_id = (int)$_SESSION['ecofut_usuario_id'];
    $slot       = (int)($_POST['slot'] ?? 1);

    $stmt = $pdo->prepare("SELECT id, nome_time, slot FROM ecofut_saves WHERE usuario_id = ? AND slot = ?");
    $stmt->execute([$usuario_id, $slot]);
    $save = $stmt->fetch();

    if (!$save) { header("Location: ?page=saves"); exit; }

    $_SESSION['ecofut_save_id']   = (int)$save['id'];
    $_SESSION['ecofut_save_slot'] = (int)$save['slot'];
    $_SESSION['ecofut_nome_time'] = $save['nome_time'];

    header("Location: ?page=app");
    exit;
}

// ── SAIR DO SAVE ──────────────────────────────────────────────────────────────
if ($action === 'sair_save') {
    unset($_SESSION['ecofut_save_id'], $_SESSION['ecofut_save_slot'], $_SESSION['ecofut_nome_time']);
    header("Location: ?page=saves");
    exit;
}

// ── AVANCAR RODADA ────────────────────────────────────────────────────────────
if ($action === 'avancar_rodada') {
    if (!$db_connected || !isset($_SESSION['ecofut_save_id'])) {
        header("Location: ?page=app"); exit;
    }

    $save_id = (int)$_SESSION['ecofut_save_id'];

    require_once __DIR__ . '/engine/season.php';
    $resultado = avancarRodada($pdo, $save_id);

    if (!empty($resultado['match_setup'])) {
        $_SESSION['ecofut_match_setup']     = $resultado['match_setup'];
        $_SESSION['ecofut_match_auto_open'] = true;
    } else {
        unset($_SESSION['ecofut_match_setup'], $_SESSION['ecofut_match_auto_open']);
    }

    header("Location: ?page=app");
    exit;
}

// ── SALVAR PARTIDA DO USUÁRIO ─────────────────────────────────────────────────
if ($action === 'salvar_partida_usuario') {
    if (!$db_connected || !isset($_SESSION['ecofut_save_id'])) {
        header("Location: ?page=app"); exit;
    }

    $save_id       = (int)$_SESSION['ecofut_save_id'];
    $partida_id    = (int)($_POST['partida_id']    ?? 0);
    $gols_casa     = (int)($_POST['gols_casa']     ?? 0);
    $gols_fora     = (int)($_POST['gols_fora']     ?? 0);
    $stats_json    = $_POST['stats_json']    ?? '[]';
    $nova_formacao = $_POST['nova_formacao'] ?? null;
    $nova_tatica   = $_POST['nova_tatica']   ?? null;

    require_once __DIR__ . '/engine/season.php';
    $res = finalizarPartidaUsuario($pdo, $save_id, $partida_id, $gols_casa, $gols_fora);

    if ($res['ok']) {
        $statsArr = json_decode($stats_json, true) ?: [];
        if (!empty($statsArr)) {
            $stmtStats = $pdo->prepare(
                "UPDATE ecofut_elenco
                 SET partidas   = partidas   + 1,
                     gols       = gols       + ?,
                     assists    = assists    + ?,
                     amarelos   = amarelos   + ?,
                     vermelhos  = vermelhos  + ?,
                     nota_total = nota_total + ?
                 WHERE id = ? AND save_id = ?"
            );
            foreach ($statsArr as $stat) {
                $stmtStats->execute([
                    (int)($stat['gols']      ?? 0),
                    (int)($stat['assists']   ?? 0),
                    (int)($stat['amarelos']  ?? 0),
                    (int)($stat['vermelhos'] ?? 0),
                    (float)($stat['nota']    ?? 6.0),
                    (int)($stat['id']        ?? 0),
                    $save_id,
                ]);
            }
        }

        $formacoes_validas = ['4-3-3','4-4-2','4-2-3-1','3-5-2','5-3-2','4-1-4-1','3-4-3'];
        if ($nova_formacao || $nova_tatica) {
            $rowDados = $pdo->prepare("SELECT dados_json FROM ecofut_saves WHERE id = ?");
            $rowDados->execute([$save_id]);
            $dados = json_decode($rowDados->fetchColumn() ?: '{}', true) ?: [];
            if ($nova_formacao && in_array($nova_formacao, $formacoes_validas)) $dados['formacao'] = $nova_formacao;
            if ($nova_tatica   && in_array($nova_tatica, ['normal','ataque','defensivo'])) $dados['tatica'] = $nova_tatica;
            $pdo->prepare("UPDATE ecofut_saves SET dados_json=? WHERE id=?")->execute([json_encode($dados), $save_id]);
        }

        unset($_SESSION['ecofut_match_setup'], $_SESSION['ecofut_match_auto_open']);
    }

    header("Location: ?page=app");
    exit;
}

// ── COMPRAR JOGADOR ───────────────────────────────────────────────────────────
if ($action === 'comprar_jogador') {
    if (!$db_connected || !isset($_SESSION['ecofut_save_id'])) {
        header("Location: ?page=app"); exit;
    }

    $save_id        = (int)$_SESSION['ecofut_save_id'];
    $jogador_base_id = (int)($_POST['jogador_base_id'] ?? 0);

    // Busca dados base primeiro para calcular preço server-side
    $jBase = $pdo->prepare("SELECT * FROM ecofut_jogadores_base WHERE id = ?");
    $jBase->execute([$jogador_base_id]);
    $j = $jBase->fetch();
    if (!$j) { $msg = 'Jogador não encontrado.'; $msg_tipo = 'erro'; goto fim; }

    $preco = valorMercado((int)$j['forca'], (int)$j['idade']);

    // Valida saldo
    $saveRow = $pdo->prepare("SELECT saldo, time_id FROM ecofut_saves WHERE id = ?");
    $saveRow->execute([$save_id]);
    $saveData = $saveRow->fetch();

    if (!$saveData || $saveData['saldo'] < $preco) {
        $msg = 'Saldo insuficiente.'; $msg_tipo = 'erro'; goto fim;
    }

    // Valida jogador (não deve já estar no elenco)
    $jaElenco = $pdo->prepare("SELECT id FROM ecofut_elenco WHERE save_id = ? AND jogador_base_id = ?");
    $jaElenco->execute([$save_id, $jogador_base_id]);
    if ($jaElenco->fetch()) { $msg = 'Jogador já está no seu elenco.'; $msg_tipo = 'erro'; goto fim; }

    // Insere no elenco
    $pdo->prepare(
        "INSERT INTO ecofut_elenco
         (save_id, time_id, jogador_base_id, nome, posicao, forca, idade, energia, moral,
          contundido, suspenso, salario, meses_contrato, titular,
          sk_goleiro, sk_agilidade, sk_passe, sk_armacao, sk_desarme, sk_finalizacao, sk_tecnica)
         VALUES (?,?,?,?,?,?,?,100,75,0,0,?,12,0,?,?,?,?,?,?,?)"
    )->execute([
        $save_id, $saveData['time_id'], $j['id'], $j['nome'], $j['posicao'],
        $j['forca'], $j['idade'], $j['salario'],
        $j['sk_goleiro'], $j['sk_agilidade'], $j['sk_passe'],
        $j['sk_armacao'], $j['sk_desarme'], $j['sk_finalizacao'], $j['sk_tecnica'],
    ]);

    // Desconta saldo e registra
    $pdo->prepare("UPDATE ecofut_saves SET saldo = saldo - ? WHERE id = ?")->execute([$preco, $save_id]);
    $pdo->prepare(
        "INSERT INTO ecofut_financas (save_id, categoria, descricao, valor)
         VALUES (?, 'transferencia', ?, ?)"
    )->execute([$save_id, 'Contratação: ' . $j['nome'], -$preco]);

    header("Location: ?page=app");
    exit;
}

// ── VENDER JOGADOR ────────────────────────────────────────────────────────────
if ($action === 'vender_jogador') {
    if (!$db_connected || !isset($_SESSION['ecofut_save_id'])) {
        header("Location: ?page=app"); exit;
    }

    $save_id   = (int)$_SESSION['ecofut_save_id'];
    $elenco_id = (int)($_POST['elenco_id'] ?? 0);

    $j = $pdo->prepare("SELECT nome, forca, idade FROM ecofut_elenco WHERE id = ? AND save_id = ?");
    $j->execute([$elenco_id, $save_id]);
    $jRow = $j->fetch();
    if (!$jRow) { $msg = 'Jogador não encontrado.'; $msg_tipo = 'erro'; goto fim; }

    // Preço calculado server-side
    $preco = precoVenda((int)$jRow['forca'], (int)$jRow['idade']);

    $pdo->prepare("DELETE FROM ecofut_elenco WHERE id = ? AND save_id = ?")->execute([$elenco_id, $save_id]);
    $pdo->prepare("UPDATE ecofut_saves SET saldo = saldo + ? WHERE id = ?")->execute([$preco, $save_id]);
    $pdo->prepare(
        "INSERT INTO ecofut_financas (save_id, categoria, descricao, valor)
         VALUES (?, 'transferencia', ?, ?)"
    )->execute([$save_id, 'Venda: ' . $jRow['nome'], $preco]);

    header("Location: ?page=app");
    exit;
}

// ── SALVAR ESCALAÇÃO ─────────────────────────────────────────────────────────
if ($action === 'salvar_escalacao') {
    if (!$db_connected || !isset($_SESSION['ecofut_save_id'])) {
        header("Location: ?page=app"); exit;
    }

    $save_id  = (int)$_SESSION['ecofut_save_id'];
    $formacao = $_POST['formacao'] ?? '4-3-3';

    $formacoes_validas = ['4-3-3','4-4-2','4-2-3-1','3-5-2','5-3-2','4-1-4-1','3-4-3'];
    if (!in_array($formacao, $formacoes_validas)) $formacao = '4-3-3';

    $titIds   = array_values(array_filter(array_map('intval', explode(',', trim($_POST['titular_ids'] ?? '')))));
    $bancoIds = array_values(array_filter(array_map('intval', explode(',', trim($_POST['banco_ids']   ?? '')))));
    $titIds   = array_slice($titIds,   0, 11);
    $bancoIds = array_slice($bancoIds, 0,  6);

    try {
        $pdo->prepare("UPDATE ecofut_elenco SET titular = 0 WHERE save_id = ?")->execute([$save_id]);

        if (!empty($titIds)) {
            $ph = implode(',', array_fill(0, count($titIds), '?'));
            $pdo->prepare("UPDATE ecofut_elenco SET titular = 1 WHERE save_id = ? AND id IN ($ph)")
                ->execute(array_merge([$save_id], $titIds));
        }

        $row = $pdo->prepare("SELECT dados_json FROM ecofut_saves WHERE id = ?");
        $row->execute([$save_id]);
        $dados = json_decode($row->fetchColumn() ?: '{}', true) ?: [];
        $dados['formacao']  = $formacao;
        $dados['banco_ids'] = $bancoIds;
        $taticaPost = $_POST['tatica'] ?? '';
        if (in_array($taticaPost, ['normal','ataque','defensivo'])) $dados['tatica'] = $taticaPost;
        $pdo->prepare("UPDATE ecofut_saves SET dados_json = ? WHERE id = ?")
            ->execute([json_encode($dados), $save_id]);

        $_SESSION['ecofut_flash_aba'] = 'elenco';
        header("Location: ?page=app"); exit;
    } catch (Exception $e) {
        error_log('EcoFut salvar_escalacao: ' . $e->getMessage());
        $msg = 'Erro ao salvar escalação.'; $msg_tipo = 'erro'; goto fim;
    }
}

fim:
