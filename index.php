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
    CREATE TABLE IF NOT EXISTS config_sistema ( id INT PRIMARY KEY, sprint_atual INT DEFAULT 1 );
    CREATE TABLE IF NOT EXISTS gm (
        id INT AUTO_INCREMENT PRIMARY KEY, nome VARCHAR(100) NOT NULL, nome_do_time VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL, senha VARCHAR(255) NOT NULL, pontos INT DEFAULT 0,
        trade_count INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE IF NOT EXISTS jogador (
        id INT AUTO_INCREMENT PRIMARY KEY, nome VARCHAR(100) NOT NULL, posicao VARCHAR(10) NOT NULL,
        overall INT NOT NULL, idade INT DEFAULT 25
    );
    CREATE TABLE IF NOT EXISTS elenco (
        id INT AUTO_INCREMENT PRIMARY KEY, gm_id INT NOT NULL, jogador_id INT NOT NULL, is_titular BOOLEAN DEFAULT FALSE,
        FOREIGN KEY (gm_id) REFERENCES gm(id) ON DELETE CASCADE, FOREIGN KEY (jogador_id) REFERENCES jogador(id) ON DELETE CASCADE
    );
    CREATE TABLE IF NOT EXISTS leilao (
        id INT AUTO_INCREMENT PRIMARY KEY, jogador_id INT NOT NULL, gm_vendedor_id INT NOT NULL,
        data_fim DATETIME NOT NULL, status VARCHAR(20) DEFAULT 'aberto',
        FOREIGN KEY (jogador_id) REFERENCES jogador(id) ON DELETE CASCADE, FOREIGN KEY (gm_vendedor_id) REFERENCES gm(id) ON DELETE CASCADE
    );
    CREATE TABLE IF NOT EXISTS leilao_oferta (
        id INT AUTO_INCREMENT PRIMARY KEY, leilao_id INT NOT NULL, gm_comprador_id INT NOT NULL,
        jogador_oferecido_id INT NOT NULL, status VARCHAR(20) DEFAULT 'pendente',
        FOREIGN KEY (leilao_id) REFERENCES leilao(id) ON DELETE CASCADE, FOREIGN KEY (gm_comprador_id) REFERENCES gm(id) ON DELETE CASCADE,
        FOREIGN KEY (jogador_oferecido_id) REFERENCES jogador(id) ON DELETE CASCADE
    );
    ";
    try { $pdo->exec($sql); } catch (Exception $e) { error_log($e->getMessage()); }
    
    // Patch update para adicionar a coluna idade se ela não existir (para quem já rodou o banco antes)
    try { $pdo->exec("ALTER TABLE jogador ADD COLUMN idade INT DEFAULT 25"); } catch (Exception $e) { /* Ignora se já existe */ }
    // Patch update para remover coluna time_real se existir
    try { $pdo->exec("ALTER TABLE jogador DROP COLUMN time_real"); } catch (Exception $e) { /* Ignora se nao existe */ }
}

function seedDatabaseIfNeeded($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM gm");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO config_sistema (id, sprint_atual) VALUES (1, 1)");
        $hash = password_hash('123456', PASSWORD_DEFAULT);
        for ($i = 1; $i <= 20; $i++) {
            $pdo->exec("INSERT INTO gm (nome, nome_do_time, email, senha, pontos) VALUES ('Usuario $i', 'Franquia $i', 'gm$i@email.com', '$hash', " . rand(10,50) . ")");
        }
        $posicoes = ['GOL', 'ZAG', 'ZAG', 'LD', 'LE', 'VOL', 'MC', 'MEI', 'PE', 'PD', 'ATA', 'GOL', 'ZAG', 'MC', 'ATA', 'PE'];
        $jogador_id = 1;
        for ($gm_id = 1; $gm_id <= 20; $gm_id++) {
            foreach ($posicoes as $index => $pos) {
                $ovr = rand(80, 93); $idade = rand(18, 35); $nome = "Jogador " . $jogador_id;
                $pdo->exec("INSERT INTO jogador (nome, posicao, overall, idade) VALUES ('$nome', '$pos', $ovr, $idade)");
                $is_titular = ($index < 11) ? 1 : 0;
                $pdo->exec("INSERT INTO elenco (gm_id, jogador_id, is_titular) VALUES ($gm_id, $jogador_id, $is_titular)");
                $jogador_id++;
            }
        }
        $pdo->exec("UPDATE gm SET nome = 'Marcos Medeiros', email='admin@admin.com' WHERE id = 1");
    }
}

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db_connected = true;
    runMigrations($pdo);
    seedDatabaseIfNeeded($pdo);
} catch (PDOException $e) { $db_connected = false; }

// ------------------------------------------------------------------
// 1. ENGINE DE REQUISIÇÕES (Processa POSTs - CRUD e Ações)
// ------------------------------------------------------------------
$msg = ''; $msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        if ($db_connected) {
            $stmt = $pdo->prepare("SELECT id, nome_do_time, senha FROM gm WHERE email = ?");
            $stmt->execute([$_POST['email']]);
            $gm = $stmt->fetch();
            if ($gm && password_verify($_POST['senha'], $gm['senha'])) {
                $_SESSION['logged_in'] = true; $_SESSION['gm_id'] = $gm['id']; $_SESSION['gm_name'] = $gm['nome_do_time'];
                header("Location: ?page=app"); exit;
            } else { $msg = "Credenciais inválidas."; $msgType = "error"; }
        } else {
            $_SESSION['logged_in'] = true; $_SESSION['gm_id'] = 1; $_SESSION['gm_name'] = "Modo Offline";
            header("Location: ?page=app"); exit;
        }
    }

    if ($action === 'logout') { session_destroy(); header("Location: ?page=login"); exit; }

    // SENIOR TIP: CRUD DE JOGADORES (Create / Update)
    if ($action === 'salvar_jogador' && (!$db_connected || !isset($_SESSION['gm_id']))) {
        header("Location: ?page=app&tab=team&error=db"); exit;
    }
    if ($action === 'salvar_jogador' && $db_connected && isset($_SESSION['gm_id'])) {
        $id = !empty($_POST['jogador_id']) ? (int)$_POST['jogador_id'] : null;
        $nome = $_POST['nome'];
        $posicao = $_POST['posicao'];
        $overall = (int)$_POST['overall'];
        $idade = (int)$_POST['idade'];
        $gm_id = $_SESSION['gm_id'];

        if ($id) {
            // Update
            $stmt = $pdo->prepare("UPDATE jogador j JOIN elenco e ON j.id = e.jogador_id SET j.nome=?, j.posicao=?, j.overall=?, j.idade=? WHERE j.id=? AND e.gm_id=?");
            $stmt->execute([$nome, $posicao, $overall, $idade, $id, $gm_id]);
        } else {
            // Create
            $stmt = $pdo->prepare("INSERT INTO jogador (nome, posicao, overall, idade) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nome, $posicao, $overall, $idade]);
            $novo_id = $pdo->lastInsertId();
            $pdo->prepare("INSERT INTO elenco (gm_id, jogador_id, is_titular) VALUES (?, ?, 0)")->execute([$gm_id, $novo_id]);
        }
        header("Location: ?page=app&tab=team&manage=1"); exit;
    }

    // SENIOR TIP: CRUD DE JOGADORES (Delete)
    if ($action === 'deletar_jogador' && $db_connected && isset($_SESSION['gm_id'])) {
        $id = (int)$_POST['jogador_id'];
        // Garante que só deleta se for do próprio time
        $stmt = $pdo->prepare("DELETE j FROM jogador j JOIN elenco e ON j.id = e.jogador_id WHERE j.id = ? AND e.gm_id = ?");
        $stmt->execute([$id, $_SESSION['gm_id']]);
        header("Location: ?page=app&tab=team&manage=1"); exit;
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

        header("Location: ?page=app&tab=team"); exit;
    }

    if ($action === 'criar_leilao' && $db_connected) {
        $jogador_id = (int)$_POST['jogador_id'];
        $gm_id = $_SESSION['gm_id'];
        $stmt = $pdo->prepare("SELECT j.overall FROM jogador j JOIN elenco e ON j.id = e.jogador_id WHERE j.id = ? AND e.gm_id = ?");
        $stmt->execute([$jogador_id, $gm_id]);
        if ($jog = $stmt->fetch()) {
            if ($jog['overall'] >= 88) $pdo->prepare("INSERT INTO leilao (jogador_id, gm_vendedor_id, data_fim) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 20 MINUTE))")->execute([$jogador_id, $gm_id]);
        }
        header("Location: ?page=app&tab=market"); exit;
    }

    if ($action === 'avancar_sprint' && $db_connected) {
        $pdo->exec("UPDATE config_sistema SET sprint_atual = sprint_atual + 1");
        $sprint = $pdo->query("SELECT sprint_atual FROM config_sistema")->fetchColumn();
        if ($sprint % 2 != 0) $pdo->exec("UPDATE gm SET trade_count = 0");
        header("Location: ?page=app&tab=admin"); exit;
    }
}

// ------------------------------------------------------------------
// 2. BUSCA DE DADOS (Hydration do Front-End)
// ------------------------------------------------------------------
$page = $_GET['page'] ?? 'login';
if ($page === 'app' && !isset($_SESSION['logged_in'])) { header("Location: ?page=login"); exit; }

$app_data = [];
if ($page === 'app') {
    $gm_id_logado = $_SESSION['gm_id'] ?? 1;
    $cap_limit = 704; 
    
    if ($db_connected) {
        $sprint_atual = $pdo->query("SELECT sprint_atual FROM config_sistema")->fetchColumn();
        $gms = $pdo->query("SELECT id, nome as name, nome_do_time as teamName, pontos as points, trade_count FROM gm ORDER BY pontos DESC")->fetchAll();
        
        $stmt = $pdo->prepare("SELECT j.id, j.nome as name, j.posicao as pos, j.overall as ovr, j.idade, e.is_titular FROM jogador j JOIN elenco e ON j.id = e.jogador_id WHERE e.gm_id = ? ORDER BY e.is_titular DESC, j.overall DESC");
        $stmt->execute([$gm_id_logado]);
        $players = $stmt->fetchAll();

        // Todos os jogadores do Database
        $all_players = $pdo->query("SELECT j.id, j.nome as name, j.posicao as pos, j.overall as ovr, j.idade, IFNULL(g.nome_do_time, 'Livre') as gm_dono FROM jogador j LEFT JOIN elenco e ON j.id = e.jogador_id LEFT JOIN gm g ON e.gm_id = g.id ORDER BY j.overall DESC")->fetchAll();

        $stmtCap = $pdo->prepare("SELECT SUM(overall) as top_8_sum FROM (SELECT j.overall FROM jogador j JOIN elenco e ON j.id = e.jogador_id WHERE e.gm_id = ? ORDER BY j.overall DESC LIMIT 8) as subquery");
        $stmtCap->execute([$gm_id_logado]);
        $cap_sum = $stmtCap->fetchColumn() ?: 0;

        $auctions = $pdo->query("SELECT l.id, j.nome, j.overall, j.posicao, gm.nome_do_time as vendedor, l.data_fim FROM leilao l JOIN jogador j ON l.jogador_id = j.id JOIN gm ON l.gm_vendedor_id = gm.id WHERE l.status = 'aberto' AND l.data_fim > NOW()")->fetchAll();

        // Cálculo da Posição no Ranking (Dashboard)
        $minha_posicao = 1;
        foreach ($gms as $index => $g) { if ($g['id'] == $gm_id_logado) $minha_posicao = $index + 1; }

        $meu_gm = array_filter($gms, fn($g) => $g['id'] == $gm_id_logado);
        $meu_gm = reset($meu_gm);

    } else {
        $sprint_atual = 1; $gms = []; $players = []; $all_players = []; $cap_sum = 0; $auctions = []; $meu_gm = ['trade_count' => 0]; $minha_posicao = 1;
    }

    $app_data = [
        'sprint' => $sprint_atual, 'max_sprints' => 15, 'db_status' => $db_connected,
        'gm_logado' => [
            'id' => $gm_id_logado, 'nome_time' => $_SESSION['gm_name'], 'trade_count' => $meu_gm['trade_count'] ?? 0, 'max_trades' => 10,
            'cap_sum' => (int)$cap_sum, 'cap_limit' => $cap_limit, 'cap_ok' => ((int)$cap_sum <= $cap_limit),
            'posicao_ranking' => $minha_posicao, 'pontos' => $meu_gm['points'] ?? 0, 'total_atletas' => count($players)
        ],
        'gms' => $gms, 'players' => $players, 'all_players' => $all_players, 'auctions' => $auctions,
        'formations' => ['4-3-3', '4-4-2', '4-2-3-1', '3-5-2', '5-3-2']
    ];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Fantasy FC - EAFC26</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeIn 0.3s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .pitch-pattern { background-color: #065f46; background-image: repeating-linear-gradient(0deg, transparent, transparent 10%, rgba(255,255,255,0.05) 10%, rgba(255,255,255,0.05) 20%); }
        @keyframes pulse-red { 0%, 100% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.7); } 50% { box-shadow: 0 0 0 6px rgba(220, 38, 38, 0); } }
        .out-of-position { animation: pulse-red 2s infinite; }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 font-sans antialiased h-screen w-full overflow-hidden flex flex-col md:flex-row">

<?php if ($page === 'login'): ?>
    <div class="flex-1 flex items-center justify-center p-4">
        <form action="index.php" method="POST" class="bg-slate-800 p-8 rounded-2xl w-full max-w-md shadow-2xl border border-slate-700">
            <h1 class="text-3xl font-black text-emerald-400 text-center mb-6">FANTASY FC</h1>
            <?php if($msg) echo "<p class='text-red-400 text-center mb-4 text-sm'>$msg</p>"; ?>
            <input type="hidden" name="action" value="login">
            <input type="email" name="email" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 mb-4 text-white" placeholder="admin@admin.com">
            <input type="password" name="senha" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 mb-6 text-white" placeholder="123456">
            <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-3 rounded-xl transition">ENTRAR</button>
        </form>
    </div>

<?php elseif ($page === 'app'): ?>
    <!-- SIDEBAR -->
    <aside id="sidebar" class="w-64 bg-slate-950 border-r border-slate-800 flex flex-col h-full absolute md:relative z-40 transform -translate-x-full md:translate-x-0 transition-transform">
        <div class="p-6 border-b border-slate-800 flex justify-between">
            <h1 class="text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-cyan-500">FANTASY FC</h1>
            <button onclick="document.getElementById('sidebar').classList.add('-translate-x-full')" class="md:hidden text-slate-500"><i class="fa-solid fa-xmark text-xl"></i></button>
        </div>
        <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
            <button onclick="switchTab('dashboard')" class="nav-btn w-full flex items-center px-4 py-3 text-sm rounded-xl text-emerald-400 bg-emerald-900/20 font-bold border border-emerald-500/50" data-target="dashboard"><i class="fa-solid fa-chart-line w-6"></i> Dashboard</button>
            <button onclick="switchTab('team')" class="nav-btn w-full flex items-center px-4 py-3 text-sm rounded-xl text-slate-400 hover:bg-slate-800 transition" data-target="team"><i class="fa-solid fa-shield-halved w-6"></i> Meu Elenco</button>
            <button onclick="switchTab('market')" class="nav-btn w-full flex items-center px-4 py-3 text-sm rounded-xl text-slate-400 hover:bg-slate-800 transition" data-target="market"><i class="fa-solid fa-comments-dollar w-6"></i> Mercado</button>
            <button onclick="switchTab('all_players')" class="nav-btn w-full flex items-center px-4 py-3 text-sm rounded-xl text-slate-400 hover:bg-slate-800 transition" data-target="all_players"><i class="fa-solid fa-users w-6"></i> Database</button>
            <button onclick="switchTab('ranking')" class="nav-btn w-full flex items-center px-4 py-3 text-sm rounded-xl text-slate-400 hover:bg-slate-800 transition" data-target="ranking"><i class="fa-solid fa-trophy w-6"></i> Ranking</button>
            <button onclick="switchTab('admin')" class="nav-btn w-full flex items-center px-4 py-3 text-sm rounded-xl text-red-400 hover:bg-red-900/20 mt-4 border border-transparent transition" data-target="admin"><i class="fa-solid fa-gear w-6"></i> Admin Panel</button>
        </nav>
        <div class="p-4 border-t border-slate-800 shrink-0">
            <form action="index.php" method="POST"><input type="hidden" name="action" value="logout"><button class="w-full bg-slate-800 hover:bg-red-900/30 text-xs py-3 rounded-xl font-bold text-slate-400 hover:text-red-400 transition"><i class="fa-solid fa-power-off mr-2"></i>Sair</button></form>
        </div>
    </aside>

    <main class="flex-1 overflow-y-auto bg-slate-900 p-4 md:p-8 relative">
        <!-- HEADER MOBILE -->
        <div class="md:hidden flex justify-between items-center mb-4 bg-slate-950 p-4 rounded-xl border border-slate-800">
            <h1 class="text-xl font-black text-emerald-400">FANTASY FC</h1>
            <button onclick="document.getElementById('sidebar').classList.remove('-translate-x-full')" class="text-slate-400"><i class="fa-solid fa-bars text-xl"></i></button>
        </div>

        <div class="flex justify-between items-center mb-6 bg-slate-800/80 p-4 rounded-xl border border-slate-700 backdrop-blur-sm">
            <div>
                <h2 class="text-slate-400 text-xs font-bold uppercase">Temporada Atual</h2>
                <div class="text-xl font-bold text-white"><i class="fa-solid fa-calendar text-emerald-500 mr-2"></i> Sprint <span id="sprint-display"></span>/15</div>
            </div>
            <div class="text-right">
                <h2 class="text-slate-400 text-xs font-bold uppercase">DB Status</h2>
                <div class="text-sm font-bold <?php echo $db_connected ? 'text-emerald-400' : 'text-red-500'; ?>"><i class="fa-solid fa-database mr-1"></i> <?php echo $db_connected ? 'ONLINE' : 'OFFLINE'; ?></div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB: DASHBOARD (Central de Controle)       -->
        <!-- ========================================== -->
        <section id="tab-dashboard" class="tab-content active">
            <h2 class="text-2xl font-bold text-white mb-6"><i class="fa-solid fa-chart-line text-emerald-400 mr-2"></i> Command Center</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <!-- Card Posição -->
                <div class="bg-slate-800 p-5 rounded-2xl border border-slate-700 shadow-lg flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-400 uppercase font-bold tracking-wider mb-1">Posição Ranking</p>
                        <h3 class="text-3xl font-black text-yellow-400" id="dash-rank">--</h3>
                        <p class="text-xs text-emerald-400 mt-1" id="dash-pts">-- pts</p>
                    </div>
                    <div class="w-12 h-12 bg-slate-900 rounded-full flex items-center justify-center border border-slate-700 text-yellow-400 text-xl"><i class="fa-solid fa-trophy"></i></div>
                </div>

                <!-- Card Teto Salarial (Cap) -->
                <div class="bg-slate-800 p-5 rounded-2xl border border-slate-700 shadow-lg flex items-center justify-between" id="dash-cap-card">
                    <div>
                        <p class="text-xs text-slate-400 uppercase font-bold tracking-wider mb-1">Cap (Top 8 OVR)</p>
                        <h3 class="text-3xl font-black text-white" id="dash-cap-val">--</h3>
                        <p class="text-xs text-slate-400 mt-1">Limite: <span id="dash-cap-limit"></span></p>
                    </div>
                    <div class="w-12 h-12 bg-slate-900 rounded-full flex items-center justify-center border border-slate-700 text-xl"><i class="fa-solid fa-wallet"></i></div>
                </div>

                <!-- Card Atletas -->
                <div class="bg-slate-800 p-5 rounded-2xl border border-slate-700 shadow-lg flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-400 uppercase font-bold tracking-wider mb-1">Tamanho do Elenco</p>
                        <h3 class="text-3xl font-black text-white" id="dash-athletes">--</h3>
                        <p class="text-xs text-slate-400 mt-1">Jogadores Ativos</p>
                    </div>
                    <div class="w-12 h-12 bg-slate-900 rounded-full flex items-center justify-center border border-slate-700 text-blue-400 text-xl"><i class="fa-solid fa-users"></i></div>
                </div>

                <!-- Card Trades -->
                <div class="bg-slate-800 p-5 rounded-2xl border border-slate-700 shadow-lg flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-400 uppercase font-bold tracking-wider mb-1">Trades Usados</p>
                        <h3 class="text-3xl font-black text-white"><span id="dash-trades" class="text-blue-400"></span><span class="text-slate-600 text-xl">/10</span></h3>
                        <p class="text-xs text-slate-400 mt-1">Neste Ciclo</p>
                    </div>
                    <div class="w-12 h-12 bg-slate-900 rounded-full flex items-center justify-center border border-slate-700 text-blue-400 text-xl"><i class="fa-solid fa-right-left"></i></div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Top 5 Ranking -->
                <div class="bg-slate-800 rounded-2xl p-6 border border-slate-700 shadow-lg">
                    <h3 class="text-lg font-bold text-white mb-4"><i class="fa-solid fa-fire text-orange-500 mr-2"></i> Top 5 GMs</h3>
                    <div class="space-y-3" id="dash-top5"></div>
                </div>
                <!-- Propostas Recebidas (Mock visual) -->
                <div class="bg-slate-800 rounded-2xl p-6 border border-slate-700 shadow-lg">
                    <h3 class="text-lg font-bold text-white mb-4"><i class="fa-solid fa-envelope-open-text text-emerald-500 mr-2"></i> Propostas de Negócio</h3>
                    <div class="bg-slate-900/50 rounded-xl p-6 text-center border border-dashed border-slate-700">
                        <i class="fa-regular fa-folder-open text-3xl text-slate-600 mb-2"></i>
                        <p class="text-slate-400 text-sm">Nenhuma proposta recebida no momento.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- TAB: MEU ELENCO (Com CRUD e Campinho)      -->
        <!-- ========================================== -->
        <section id="tab-team" class="tab-content">
            <div class="bg-slate-800 rounded-2xl p-6 shadow-xl border border-slate-700">
                <div class="flex flex-col md:flex-row justify-between items-center mb-6 border-b border-slate-700 pb-4">
                    <h2 class="text-2xl font-bold text-white"><i class="fa-solid fa-tshirt text-blue-400 mr-2"></i> Meu Elenco</h2>
                    <div class="flex items-center space-x-4 mt-4 md:mt-0">
                        <button onclick="toggleManagePlayers()" class="bg-slate-900 hover:bg-slate-700 text-slate-300 border border-slate-600 px-4 py-2 rounded-lg font-bold text-sm transition shadow-inner">
                            <i class="fa-solid fa-pen-to-square mr-2"></i> Gerenciar Jogadores
                        </button>
                        <select id="formation-select" onchange="renderPitch()" class="bg-slate-900 border border-slate-600 text-white rounded-lg p-2 font-bold focus:outline-none"></select>
                    </div>
                </div>

                <?php if (($_GET['error'] ?? '') === 'db'): ?>
                    <div class="mb-4 rounded-xl border border-red-800/60 bg-red-900/20 px-4 py-3 text-sm text-red-200">
                        Banco offline. Nao foi possivel salvar o jogador.
                    </div>
                <?php endif; ?>
                
                <!-- VIEW 1: CAMPINHO -->
                <form id="lineup-form" action="index.php?page=app&tab=team" method="POST">
                    <input type="hidden" name="action" value="atualizar_titulares">
                    <input type="hidden" name="titulares" id="titulares-input" value="">
                    <div id="view-pitch" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <div class="lg:col-span-2 flex justify-center">
                            <div id="pitch-container" class="relative w-full max-w-lg aspect-[3/4] pitch-pattern rounded-lg border-4 border-white overflow-hidden shadow-2xl"></div>
                        </div>
                        <div class="bg-slate-900 rounded-xl p-4 border border-slate-700 h-fit">
                            <h3 class="text-slate-400 font-bold mb-4 text-sm uppercase tracking-wider flex items-center"><i class="fa-solid fa-chair mr-2"></i> Banco</h3>
                            <div id="bench-list" class="space-y-3"></div>
                            <button type="submit" class="mt-4 w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-2 rounded-lg text-sm">Salvar Escalacao</button>
                        </div>
                    </div>
                </form>

                <!-- VIEW 2: GERENCIAMENTO (CRUD) -->
                <div id="view-manage" class="hidden">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold text-white">Editor de Elenco</h3>
                        <button onclick="openPlayerModal()" class="bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-lg transition"><i class="fa-solid fa-plus mr-1"></i> Novo Jogador</button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-slate-300 text-sm">
                            <thead class="bg-slate-900/80 text-slate-400 uppercase text-xs">
                                <tr>
                                    <th class="p-3 rounded-tl-lg">OVR</th><th class="p-3">Jogador</th><th class="p-3">Pos</th>
                                    <th class="p-3">Idade</th><th class="p-3 rounded-tr-lg text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="crud-players-list"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- TAB: DATABASE DE JOGADORES (Geral)         -->
        <!-- ========================================== -->
        <section id="tab-all_players" class="tab-content">
            <div class="bg-slate-800 rounded-2xl p-6 shadow-xl border border-slate-700">
                <div class="flex flex-col md:flex-row justify-between items-center mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-white"><i class="fa-solid fa-database text-slate-400 mr-2"></i> Database Global</h2>
                        <p class="text-xs text-slate-400 mt-1">Lista de todos os jogadores ativos no simulador.</p>
                    </div>
                    <!-- Simulação de busca -->
                    <div class="mt-4 md:mt-0 relative">
                        <i class="fa-solid fa-search absolute left-3 top-3 text-slate-500"></i>
                        <input type="text" placeholder="Buscar jogador..." class="bg-slate-900 border border-slate-700 text-white rounded-xl pl-10 pr-4 py-2 focus:border-emerald-500 focus:outline-none w-full md:w-64 text-sm">
                    </div>
                </div>

                <div class="overflow-x-auto bg-slate-900/50 rounded-xl border border-slate-700">
                    <table class="w-full text-left text-slate-300 text-sm">
                        <thead class="bg-slate-900 border-b border-slate-700 text-slate-400 uppercase text-xs">
                            <tr>
                                <th class="p-4 w-16 text-center">OVR</th>
                                <th class="p-4">Nome</th>
                                <th class="p-4 w-16">Pos</th>
                                <th class="p-4 w-16">Idade</th>
                                <th class="p-4 text-right">Franquia GM</th>
                            </tr>
                        </thead>
                        <tbody id="all-players-body"></tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- TABS OMITIDAS POR BREVIDADE (Mantêm o mesmo HTML anterior: Mercado, Ranking, Admin) -->
        <section id="tab-market" class="tab-content"><div class="bg-slate-800 rounded-2xl p-6 border border-slate-700"><h2 class="text-2xl text-white font-bold"><i class="fa-solid fa-comments-dollar mr-2"></i> Mercado</h2><p class="text-slate-400 text-sm mt-2">Mesma lógica da versão anterior.</p></div></section>
        <section id="tab-ranking" class="tab-content"><div class="bg-slate-800 rounded-2xl p-6 border border-slate-700"><h2 class="text-2xl font-bold text-white mb-6">Ranking Geral</h2><table class="w-full text-left text-slate-300"><tbody id="ranking-body"></tbody></table></div></section>
        <section id="tab-admin" class="tab-content">
            <div class="bg-slate-800 rounded-2xl p-6 border-2 border-red-900/30">
                <h2 class="text-2xl font-bold text-white mb-6"><i class="fa-solid fa-lock text-red-500 mr-2"></i> Admin</h2>
                <form action="index.php" method="POST" class="bg-slate-900 p-6 rounded-xl border border-slate-700 flex justify-between items-center"><input type="hidden" name="action" value="avancar_sprint"><button type="submit" class="bg-red-600 text-white font-bold py-3 px-6 rounded-xl">Avançar Sprint <i class="fa-solid fa-forward ml-2"></i></button></form>
            </div>
        </section>
    </main>

    <!-- ========================================== -->
    <!-- MODAL CRUD DE JOGADORES                    -->
    <!-- ========================================== -->
    <div id="player-modal" class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50 hidden">
        <div class="bg-slate-800 border border-slate-600 rounded-2xl p-6 w-full max-w-md shadow-2xl relative">
            <button onclick="closePlayerModal()" class="absolute top-4 right-4 text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-xl"></i></button>
            <h2 class="text-xl font-bold text-white mb-6" id="modal-title">Novo Jogador</h2>
            
                <form action="index.php?page=app&tab=team&manage=1" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="salvar_jogador">
                <input type="hidden" name="jogador_id" id="modal_id" value="">
                
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-1">Nome</label>
                    <input type="text" name="nome" id="modal_nome" required class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2.5 text-white text-sm focus:border-emerald-500 focus:outline-none">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 mb-1">Posição</label>
                        <select name="posicao" id="modal_pos" required class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2.5 text-white text-sm focus:border-emerald-500 focus:outline-none">
                            <option value="ATA">ATA (Ataque)</option><option value="PE">PE (Ponta Esq)</option><option value="PD">PD (Ponta Dir)</option>
                            <option value="MEI">MEI (Meia)</option><option value="MC">MC (Meia Cen)</option><option value="VOL">VOL (Volante)</option>
                            <option value="ZAG">ZAG (Zagueiro)</option><option value="LE">LE (Lat Esq)</option><option value="LD">LD (Lat Dir)</option>
                            <option value="GOL">GOL (Goleiro)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 mb-1">OVR</label>
                        <input type="number" name="overall" id="modal_ovr" required min="40" max="99" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2.5 text-white text-sm focus:border-emerald-500 focus:outline-none">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-1">Idade</label>
                    <input type="number" name="idade" id="modal_idade" required min="15" max="45" value="25" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2.5 text-white text-sm focus:border-emerald-500 focus:outline-none">
                </div>
                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-3 rounded-lg mt-4 transition shadow-lg">Salvar Jogador</button>
            </form>
        </div>
    </div>

    <!-- MODAL DE DELETE INVISÍVEL PARA POST -->
    <form id="delete-form" action="index.php?page=app&tab=team&manage=1" method="POST" style="display:none;">
        <input type="hidden" name="action" value="deletar_jogador">
        <input type="hidden" name="jogador_id" id="delete_jogador_id" value="">
    </form>

    <script>
        const appState = <?php echo json_encode($app_data); ?>;
        
        // --- INICIALIZAÇÃO E DASHBOARD ---
        document.addEventListener('DOMContentLoaded', () => {
            if(appState && appState.sprint) {
                const params = new URLSearchParams(window.location.search);
                const tab = params.get('tab');
                if (tab) {
                    switchTab(tab);
                }
                if (tab === 'team' && params.get('manage') === '1') {
                    toggleManagePlayers();
                }
                document.getElementById('sprint-display').innerText = appState.sprint;
                const sel = document.getElementById('formation-select');
                if(sel) sel.innerHTML = appState.formations.map(f => `<option class="bg-slate-900" value="${f}">${f}</option>`).join('');
                
                // Popular Dashboard
                const gm = appState.gm_logado;
                document.getElementById('dash-rank').innerText = `${gm.posicao_ranking}º`;
                document.getElementById('dash-pts').innerText = `${gm.pontos} pts`;
                document.getElementById('dash-cap-val').innerText = gm.cap_sum;
                document.getElementById('dash-cap-limit').innerText = gm.cap_limit;
                if(!gm.cap_ok) { document.getElementById('dash-cap-card').classList.add('border-red-500'); document.getElementById('dash-cap-val').classList.add('text-red-400', 'animate-pulse'); }
                document.getElementById('dash-athletes').innerText = gm.total_atletas;
                document.getElementById('dash-trades').innerText = gm.trade_count;

                // Top 5 Ranking
                const top5 = [...appState.gms].sort((a,b)=>b.points-a.points).slice(0,5);
                document.getElementById('dash-top5').innerHTML = top5.map((g, i) => `
                    <div class="flex justify-between items-center p-2 rounded bg-slate-900/50 border border-slate-700/50">
                        <div><span class="font-black text-slate-500 w-6 inline-block">${i+1}º</span> <span class="font-bold text-white text-sm">${g.teamName}</span></div>
                        <span class="text-emerald-400 font-mono text-sm">${g.points} pt</span>
                    </div>`).join('');

                renderPitch(); renderBench(); renderRanking(); renderCrudList(); renderAllPlayersDatabase();
            }
        });

        // --- NAVEGAÇÃO DE TABS ---
        function switchTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.getElementById(`tab-${tabId}`).classList.add('active');
            
            document.querySelectorAll('.nav-btn').forEach(btn => btn.className = 'nav-btn w-full flex items-center px-4 py-3 text-sm rounded-xl text-slate-400 hover:bg-slate-800 transition');
            const target = document.querySelector(`.nav-btn[data-target="${tabId}"]`);
            if(target) {
                if(tabId === 'admin') target.className = 'nav-btn w-full flex items-center px-4 py-3 text-sm rounded-xl text-red-400 bg-red-900/20 mt-4 border border-transparent';
                else target.className = 'nav-btn w-full flex items-center px-4 py-3 text-sm rounded-xl text-emerald-400 bg-emerald-900/20 font-bold border border-emerald-500/50';
            }
            if (window.innerWidth < 768) document.getElementById('sidebar').classList.add('-translate-x-full');
        }

        // --- DATABASE GERAL (Aba Jogadores) ---
        function renderAllPlayersDatabase() {
            const tbody = document.getElementById('all-players-body');
            if(!tbody) return;
            
            let html = '';
            appState.all_players.forEach(p => {
                let color = p.ovr >= 90 ? 'text-yellow-400' : (p.ovr >= 85 ? 'text-emerald-400' : 'text-slate-300');
                const isOwner = p.gm_dono === appState.gm_logado.nome_time;
                html += `
                <tr class="border-b border-slate-700/50 hover:bg-slate-800 transition">
                    <td class="p-4 text-center font-black ${color} text-lg">${p.ovr}</td>
                    <td class="p-4 font-bold text-white">${p.name}</td>
                    <td class="p-4"><span class="bg-slate-800 px-2 py-1 rounded text-[10px] font-bold text-slate-400">${p.pos}</span></td>
                    <td class="p-4 text-slate-300">${p.idade} anos</td>
                    <td class="p-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <span class="inline-block px-3 py-1 rounded-full text-xs font-bold ${p.gm_dono === 'Livre' ? 'bg-slate-700 text-slate-300' : 'bg-blue-900/30 text-blue-400 border border-blue-500/30'}">${p.gm_dono}</span>
                            ${isOwner ? `<button onclick='editPlayer(${JSON.stringify(p)})' class="text-blue-400 hover:text-blue-300" title="Editar"><i class="fa-solid fa-pen"></i></button>` : ''}
                        </div>
                    </td>
                </tr>`;
            });
            tbody.innerHTML = html;
        }

        // --- CRUD LOGIC (Gerenciar Jogadores) ---
        let manageMode = false;
        function toggleManagePlayers() {
            manageMode = !manageMode;
            if(manageMode) {
                document.getElementById('view-pitch').classList.add('hidden');
                document.getElementById('view-manage').classList.remove('hidden');
            } else {
                document.getElementById('view-manage').classList.add('hidden');
                document.getElementById('view-pitch').classList.remove('hidden');
            }
        }

        function renderCrudList() {
            const tbody = document.getElementById('crud-players-list');
            if(!tbody) return;
            tbody.innerHTML = appState.players.map(p => `
                <tr class="border-b border-slate-700 hover:bg-slate-700/20">
                    <td class="p-3 font-bold text-emerald-400">${p.ovr}</td>
                    <td class="p-3 text-white font-medium">${p.name}</td>
                    <td class="p-3 text-xs">${p.pos}</td>
                    <td class="p-3 text-xs text-slate-400">${p.idade || 25}</td>
                    <td class="p-3 text-right space-x-2">
                        <button onclick='editPlayer(${JSON.stringify(p)})' class="text-blue-400 hover:text-blue-300"><i class="fa-solid fa-pen"></i></button>
                        <button onclick='deletePlayer(${p.id})' class="text-red-400 hover:text-red-300"><i class="fa-solid fa-trash"></i></button>
                    </td>
                </tr>
            `).join('');
        }

        function openPlayerModal() {
            document.getElementById('modal-title').innerText = "Novo Jogador";
            document.getElementById('modal_id').value = "";
            document.getElementById('modal_nome').value = "";
            document.getElementById('modal_ovr').value = "80";
            document.getElementById('modal_idade').value = "25";
            document.getElementById('player-modal').classList.remove('hidden');
        }

        function editPlayer(p) {
            document.getElementById('modal-title').innerText = "Editar " + p.name;
            document.getElementById('modal_id').value = p.id;
            document.getElementById('modal_nome').value = p.name;
            document.getElementById('modal_pos').value = p.pos;
            document.getElementById('modal_ovr').value = p.ovr;
            document.getElementById('modal_idade').value = p.idade || 25;
            document.getElementById('player-modal').classList.remove('hidden');
        }

        function closePlayerModal() { document.getElementById('player-modal').classList.add('hidden'); }
        
        function deletePlayer(id) {
            if(confirm("Deseja realmente dispensar este jogador do seu elenco?")) {
                document.getElementById('delete_jogador_id').value = id;
                document.getElementById('delete-form').submit();
            }
        }

        // --- CAMPINHO (Versão Enxuta) ---
        const formationMap = {
            '4-3-3': ['GOL','LD','ZAG','ZAG','LE','MC','MC','MEI','PD','ATA','PE'],
            '4-4-2': ['GOL','LD','ZAG','ZAG','LE','MC','MC','MEI','MEI','ATA','ATA'],
            '4-2-3-1': ['GOL','LD','ZAG','ZAG','LE','VOL','VOL','MEI','MEI','MEI','ATA'],
            '3-5-2': ['GOL','ZAG','ZAG','ZAG','MC','MC','MEI','MEI','LD','LE','ATA'],
            '5-3-2': ['GOL','LD','ZAG','ZAG','ZAG','LE','MC','MC','MEI','ATA','ATA']
        };

        function getSelectedIds() {
            const selects = Array.from(document.querySelectorAll('.pitch-select'));
            return selects.map(s => parseInt(s.value, 10)).filter(v => !Number.isNaN(v));
        }

        function renderPitch() {
            const formation = document.getElementById('formation-select')?.value || '4-3-3';
            const positions = formationMap[formation] || formationMap['4-3-3'];
            const pitch = document.getElementById('pitch-container');
            if (!pitch) return;

            const titulares = appState.players.filter(p => p.is_titular);
            const reservas = appState.players.filter(p => !p.is_titular);
            const lineup = [...titulares, ...reservas].slice(0, 11);

            const slots = positions.map((pos, idx) => {
                const current = lineup[idx];
                const options = appState.players.map(p => {
                    const selected = current && p.id === current.id ? 'selected' : '';
                    return `<option value="${p.id}" ${selected}>${p.name} (${p.pos}) OVR ${p.ovr}</option>`;
                }).join('');

                return `
                <div class="flex flex-col items-center gap-2">
                    <div class="text-[10px] font-bold text-slate-200 bg-slate-900/60 px-2 py-1 rounded">${pos}</div>
                    <select class="pitch-select bg-slate-900 border border-slate-700 text-xs text-white rounded-lg px-2 py-1" data-prev="${current ? current.id : ''}">
                        ${options}
                    </select>
                </div>`;
            }).join('');

            pitch.innerHTML = `
                <div class="absolute inset-0 p-4 grid grid-cols-2 sm:grid-cols-3 gap-3 place-items-center">
                    ${slots}
                </div>
            `;

            document.querySelectorAll('.pitch-select').forEach(sel => {
                sel.addEventListener('change', (e) => {
                    const val = parseInt(e.target.value, 10);
                    const selected = getSelectedIds();
                    const duplicates = selected.filter((v, i) => selected.indexOf(v) !== i);
                    if (duplicates.length > 0) {
                        const prev = e.target.getAttribute('data-prev');
                        if (prev) e.target.value = prev;
                        alert('Este jogador ja esta escalado.');
                        return;
                    }
                    e.target.setAttribute('data-prev', val);
                    renderBench();
                });
            });

            renderBench();
        }

        function renderBench() {
            const bench = document.getElementById('bench-list');
            if (!bench) return;
            const selected = new Set(getSelectedIds());
            const reservas = appState.players.filter(p => !selected.has(p.id));
            if (reservas.length === 0) {
                bench.innerHTML = `<div class="text-xs text-slate-500 p-2 text-center">Sem reservas.</div>`;
                return;
            }
            bench.innerHTML = reservas.map(p => `
                <div class="flex items-center justify-between bg-slate-800/50 border border-slate-700 rounded-lg px-3 py-2">
                    <div>
                        <div class="text-xs font-bold text-white">${p.name}</div>
                        <div class="text-[10px] text-slate-400">${p.pos} • OVR ${p.ovr}</div>
                    </div>
                    <button type="button" class="text-emerald-400 text-xs font-bold" onclick="swapIntoLineup(${p.id})">Escalar</button>
                </div>
            `).join('');
        }

        function swapIntoLineup(playerId) {
            const selects = Array.from(document.querySelectorAll('.pitch-select'));
            const selected = new Set(getSelectedIds());
            if (selected.has(playerId)) return;
            const target = selects.find(s => s && s.value);
            if (!target) return;
            target.value = playerId;
            target.setAttribute('data-prev', playerId);
            renderBench();
        }

        document.getElementById('lineup-form')?.addEventListener('submit', (e) => {
            const ids = getSelectedIds();
            document.getElementById('titulares-input').value = ids.join(',');
        });
        function renderRanking() { /* igual */ }
    </script>
<?php endif; ?>
</body>
</html>