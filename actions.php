<?php
$msg = '';
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

    if (strlen($nome) < 2)          { $msg = 'Nome muito curto.'; $msg_tipo = 'erro'; goto fim; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $msg = 'E-mail inválido.'; $msg_tipo = 'erro'; goto fim; }
    if (strlen($senha) < 6)         { $msg = 'Senha deve ter ao menos 6 caracteres.'; $msg_tipo = 'erro'; goto fim; }
    if ($senha !== $conf)           { $msg = 'As senhas não coincidem.'; $msg_tipo = 'erro'; goto fim; }

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
        $msg = 'E-mail ou senha incorretos.';
        $msg_tipo = 'erro';
        goto fim;
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

    $usuario_id     = (int) $_SESSION['ecofut_usuario_id'];
    $slot           = (int) ($_POST['slot'] ?? 1);
    $nome_treinador = trim($_POST['nome_treinador'] ?? '');
    $nome_time      = trim($_POST['nome_time'] ?? '');

    if ($slot < 1 || $slot > 2)      { $msg = 'Slot inválido.'; $msg_tipo = 'erro'; goto fim; }
    if (strlen($nome_treinador) < 2) { $msg = 'Nome do treinador muito curto.'; $msg_tipo = 'erro'; goto fim; }
    if (strlen($nome_time) < 2)      { $msg = 'Nome do time muito curto.'; $msg_tipo = 'erro'; goto fim; }

    $dados_iniciais = json_encode([
        'formacao'  => '4-3-3',
        'estilo'    => 'equilibrado',
        'marcacao'  => 'leve',
        'estadio'   => ['nome' => $nome_time . ' Arena', 'capacidade' => 20000],
        'titulos'   => [],
        'historico' => [],
    ]);

    $stmt = $pdo->prepare(
        "INSERT INTO ecofut_saves (usuario_id, slot, nome_treinador, nome_time, temporada, saldo, dados_json)
         VALUES (?, ?, ?, ?, 1, 10000000, ?)
         ON DUPLICATE KEY UPDATE
           nome_treinador = VALUES(nome_treinador),
           nome_time      = VALUES(nome_time),
           temporada      = 1,
           saldo          = 10000000,
           dados_json     = VALUES(dados_json),
           updated_at     = CURRENT_TIMESTAMP"
    );
    $stmt->execute([$usuario_id, $slot, $nome_treinador, $nome_time, $dados_iniciais]);

    $save_id = (int) ($pdo->lastInsertId() ?: $pdo->query(
        "SELECT id FROM ecofut_saves WHERE usuario_id=$usuario_id AND slot=$slot"
    )->fetchColumn());

    $_SESSION['ecofut_save_id']   = $save_id;
    $_SESSION['ecofut_save_slot'] = $slot;
    $_SESSION['ecofut_nome_time'] = $nome_time;

    header("Location: ?page=app");
    exit;
}

// ── CARREGAR SAVE ─────────────────────────────────────────────────────────────
if ($action === 'carregar_save') {
    if (!$db_connected || !isset($_SESSION['ecofut_logado'])) {
        header("Location: ?page=saves"); exit;
    }

    $usuario_id = (int) $_SESSION['ecofut_usuario_id'];
    $slot       = (int) ($_POST['slot'] ?? 1);

    $stmt = $pdo->prepare("SELECT id, nome_time, slot FROM ecofut_saves WHERE usuario_id = ? AND slot = ?");
    $stmt->execute([$usuario_id, $slot]);
    $save = $stmt->fetch();

    if (!$save) { header("Location: ?page=saves"); exit; }

    $_SESSION['ecofut_save_id']   = (int) $save['id'];
    $_SESSION['ecofut_save_slot'] = (int) $save['slot'];
    $_SESSION['ecofut_nome_time'] = $save['nome_time'];

    header("Location: ?page=app");
    exit;
}

// ── SAIR DO SAVE ─────────────────────────────────────────────────────────────
if ($action === 'sair_save') {
    unset($_SESSION['ecofut_save_id'], $_SESSION['ecofut_save_slot'], $_SESSION['ecofut_nome_time']);
    header("Location: ?page=saves");
    exit;
}

fim:
