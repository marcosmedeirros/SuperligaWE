<?php
/**
 * FANTASY FC - ARQUITETURA SINGLE-FILE PHP COM AUTENTICAÇÃO E BANCO (PDO)
 */
session_start();

// ------------------------------------------------------------------
// 0. CONEXÃO COM O BANCO DE DADOS (MariaDB / MySQL)
// ------------------------------------------------------------------
$db_host = 'localhost'; // Padrão Hostinger/cPanel
$db_name = 'u289267434_futfantasy';
$db_user = 'u289267434_futfantasy';
$db_pass = 'Tu#@EX/K>&=2';

$pdo = null;
$db_connected = false;

try {
    // Configura o PDO para lançar Exceptions em caso de erro e usar UTF-8
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db_connected = true;
} catch (PDOException $e) {
    // Para nosso protótipo não quebrar se a tabela não existir, engolimos o erro temporariamente.
    // Em produção, você logaria isso: error_log($e->getMessage());
    $db_connected = false;
}

// ------------------------------------------------------------------
// 1. CONTROLADOR DE REQUISIÇÕES (Lógica de Backend)
// ------------------------------------------------------------------
$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // A. CADASTRO
    if ($action === 'register') {
        $nome = $_POST['nome'] ?? '';
        $time = $_POST['time'] ?? '';
        $email = $_POST['email'] ?? '';
        $senha = $_POST['senha'] ?? '';
        
        if ($db_connected) {
            // Exemplo REAL de como você faria o insert:
            /*
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO gm (nome, nome_do_time, email, senha) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nome, $time, $email, $hash]);
            */
        }
        
        header("Location: ?page=login&msg=registered");
        exit;
    }

    // B. LOGIN
    if ($action === 'login') {
        $email = $_POST['email'] ?? '';
        $senha = $_POST['senha'] ?? '';
        
        if (!empty($email) && !empty($senha)) {
            // Exemplo REAL (Descomente quando tiver as tabelas):
            /*
            $stmt = $pdo->prepare("SELECT id, nome_do_time, senha FROM gm WHERE email = ?");
            $stmt->execute([$email]);
            $gm = $stmt->fetch();
            if ($gm && password_verify($senha, $gm['senha'])) {
                $_SESSION['logged_in'] = true;
                $_SESSION['gm_name'] = $gm['nome_do_time'];
                header("Location: ?page=app");
                exit;
            } else { ... erro ... }
            */

            // Fluxo Simulado
            $_SESSION['logged_in'] = true;
            $_SESSION['gm_name'] = "Marcos Medeiros";
            header("Location: ?page=app");
            exit;
        } else {
            $msg = "Credenciais inválidas.";
            $msgType = "error";
        }
    }

    // C. RECUPERAÇÃO DE SENHA
    if ($action === 'forgot') {
        header("Location: ?page=login&msg=reset_sent");
        exit;
    }

    // D. LOGOUT
    if ($action === 'logout') {
        session_destroy();
        header("Location: ?page=login");
        exit;
    }
}

// Mensagens vindas por GET
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'registered') { $msg = "Conta criada! Faça o login."; $msgType = "success"; }
    elseif ($_GET['msg'] === 'reset_sent') { $msg = "Link de recuperação enviado."; $msgType = "success"; }
}

// ------------------------------------------------------------------
// 2. ROTEAMENTO DE PÁGINAS (Frontend View)
// ------------------------------------------------------------------
$page = $_GET['page'] ?? 'login';

if ($page === 'app' && !isset($_SESSION['logged_in'])) { header("Location: ?page=login"); exit; }
if (in_array($page, ['login', 'register', 'forgot']) && isset($_SESSION['logged_in'])) { header("Location: ?page=app"); exit; }

// ------------------------------------------------------------------
// 3. DADOS DO APP (Hydration)
// ------------------------------------------------------------------
$app_data = [];
if ($page === 'app') {
    // Se o banco estiver conectado, busque de verdade. Se não, use o Mock.
    if ($db_connected) {
        // $gms = $pdo->query("SELECT id, nome as name, nome_do_time as teamName, pontos as points FROM gm")->fetchAll();
        // $players = $pdo->query("SELECT * FROM jogador")->fetchAll();
    }

    // Dados base para o protótipo funcionar
    $formacoes_eafc26 = ['4-3-3', '4-4-2', '4-2-3-1', '3-5-2', '5-3-2'];
    $gms = [];
    for ($i = 1; $i <= 20; $i++) { $gms[] = ['id' => $i, 'name' => "GM " . $i, 'teamName' => "Time Fantasy " . $i, 'points' => rand(10, 50)]; }
    $players = [
        ['id' => 7, 'name' => 'Alisson', 'pos' => 'GOL', 'realTeam' => 'Liverpool', 'ovr' => 89], // O primeiro é sempre goleiro na lógica do campo
        ['id' => 6, 'name' => 'Van Dijk', 'pos' => 'ZAG', 'realTeam' => 'Liverpool', 'ovr' => 89],
        ['id' => 15, 'name' => 'Araújo', 'pos' => 'ZAG', 'realTeam' => 'Barcelona', 'ovr' => 86],
        ['id' => 18, 'name' => 'Hakimi', 'pos' => 'LD', 'realTeam' => 'PSG', 'ovr' => 84],
        ['id' => 19, 'name' => 'Davies', 'pos' => 'LE', 'realTeam' => 'Bayern', 'ovr' => 84],
        ['id' => 8, 'name' => 'Rodri', 'pos' => 'VOL', 'realTeam' => 'Man. City', 'ovr' => 90],
        ['id' => 5, 'name' => 'De Bruyne', 'pos' => 'MEI', 'realTeam' => 'Man. City', 'ovr' => 90],
        ['id' => 3, 'name' => 'Bellingham', 'pos' => 'MEI', 'realTeam' => 'Real Madrid', 'ovr' => 89],
        ['id' => 1, 'name' => 'Vini Jr.', 'pos' => 'PE', 'realTeam' => 'Real Madrid', 'ovr' => 90],
        ['id' => 9, 'name' => 'Salah', 'pos' => 'PD', 'realTeam' => 'Liverpool', 'ovr' => 89],
        ['id' => 2, 'name' => 'Haaland', 'pos' => 'ATA', 'realTeam' => 'Man. City', 'ovr' => 91],
        // Banco
        ['id' => 10, 'name' => 'Ederson', 'pos' => 'GOL', 'realTeam' => 'Man. City', 'ovr' => 88],
        ['id' => 4, 'name' => 'Mbappé', 'pos' => 'ATA', 'realTeam' => 'Real Madrid', 'ovr' => 91],
        ['id' => 11, 'name' => 'Saka', 'pos' => 'MD', 'realTeam' => 'Arsenal', 'ovr' => 87],
        ['id' => 12, 'name' => 'Musiala', 'pos' => 'MEI', 'realTeam' => 'Bayern', 'ovr' => 87],
        ['id' => 13, 'name' => 'Leão', 'pos' => 'PE', 'realTeam' => 'Milan', 'ovr' => 86],
    ];
    $app_data = ['sprint' => 1, 'max_sprints' => 15, 'gms' => $gms, 'players' => $players, 'formations' => $formacoes_eafc26, 'db_status' => $db_connected];
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
        ::-webkit-scrollbar-thumb:hover { background: #475569; }
        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeIn 0.3s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        body.menu-open { overflow: hidden; }
        
        /* Estilos do Campo de Futebol */
        .pitch-pattern {
            background-color: #065f46;
            background-image: repeating-linear-gradient(0deg, transparent, transparent 10%, rgba(255,255,255,0.05) 10%, rgba(255,255,255,0.05) 20%);
        }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 font-sans antialiased h-screen w-full overflow-hidden flex flex-col md:flex-row">

<?php if ($page === 'login' || $page === 'register' || $page === 'forgot'): ?>
    <!-- TELA DE AUTENTICAÇÃO -->
    <div class="flex-1 flex items-center justify-center p-4 md:p-8 overflow-y-auto">
        <div class="w-full max-w-md bg-slate-800/80 backdrop-blur-md p-6 md:p-8 rounded-2xl border border-slate-700 shadow-2xl relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-emerald-400 to-cyan-500"></div>
            
            <div class="text-center mb-8 mt-2">
                <h1 class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-cyan-500 tracking-tight">FANTASY FC</h1>
                <p class="text-slate-400 text-xs md:text-sm mt-1 uppercase tracking-widest">Sprint Manager</p>
            </div>

            <?php if (!empty($msg)): ?>
                <div class="mb-6 p-4 rounded-lg text-sm font-medium text-center <?php echo $msgType === 'success' ? 'bg-emerald-900/30 text-emerald-400 border border-emerald-500/50' : 'bg-red-900/30 text-red-400 border border-red-500/50'; ?>">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <?php if ($page === 'login'): ?>
                <form action="index.php" method="POST" class="space-y-5">
                    <input type="hidden" name="action" value="login">
                    <div>
                        <label class="block text-slate-400 text-xs font-bold mb-2 ml-1">E-MAIL</label>
                        <input type="email" name="email" required class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition" placeholder="seu@email.com">
                    </div>
                    <div>
                        <div class="flex justify-between items-center mb-2 ml-1">
                            <label class="block text-slate-400 text-xs font-bold">SENHA</label>
                            <a href="?page=forgot" class="text-xs text-emerald-500 hover:text-emerald-400 transition">Esqueceu a senha?</a>
                        </div>
                        <input type="password" name="senha" required class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition" placeholder="••••••••">
                    </div>
                    <button type="submit" class="w-full bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-500 hover:to-emerald-400 text-white font-bold py-3.5 px-4 rounded-xl transition shadow-lg shadow-emerald-900/20 mt-2">ENTRAR NO JOGO</button>
                </form>
                <div class="text-center mt-8">
                    <p class="text-slate-400 text-sm">Não é um GM ainda? <a href="?page=register" class="text-emerald-500 font-bold hover:text-emerald-400 transition ml-1">Criar franquia</a></p>
                </div>
            <?php elseif ($page === 'register'): ?>
                <!-- Cadastro Omitido por brevidade no Protótipo visual -->
                <form action="index.php" method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="register">
                    <input type="text" name="nome" required class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-emerald-500 transition" placeholder="Nome Completo">
                    <input type="email" name="email" required class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-emerald-500 transition" placeholder="E-mail">
                    <input type="password" name="senha" required class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-emerald-500 transition" placeholder="Senha">
                    <button type="submit" class="w-full bg-gradient-to-r from-emerald-600 to-emerald-500 text-white font-bold py-3.5 px-4 rounded-xl transition mt-2">FINALIZAR CADASTRO</button>
                </form>
                <div class="text-center mt-6"><a href="?page=login" class="text-slate-400 hover:text-white transition"><i class="fa-solid fa-arrow-left mr-2"></i> Voltar</a></div>
            <?php elseif ($page === 'forgot'): ?>
                <form action="index.php" method="POST" class="space-y-5">
                    <input type="hidden" name="action" value="forgot">
                    <input type="email" name="email" required class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-emerald-500 transition" placeholder="E-mail Cadastrado">
                    <button type="submit" class="w-full bg-slate-700 hover:bg-slate-600 text-white font-bold py-3.5 px-4 rounded-xl transition mt-2">RECUPERAR</button>
                </form>
                <div class="text-center mt-6"><a href="?page=login" class="text-slate-400 hover:text-white transition"><i class="fa-solid fa-arrow-left mr-2"></i> Voltar</a></div>
            <?php endif; ?>
        </div>
    </div>

<?php elseif ($page === 'app'): ?>
    <!-- MOBILE HEADER -->
    <header class="md:hidden flex-none bg-slate-950 border-b border-slate-800 p-4 flex justify-between items-center z-40 relative">
        <h1 class="text-xl font-black text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-cyan-500 tracking-tight">FANTASY FC</h1>
        <button id="mobile-menu-btn" class="text-slate-400 hover:text-white focus:outline-none p-2 rounded-lg bg-slate-900 border border-slate-800"><i class="fa-solid fa-bars text-xl"></i></button>
    </header>

    <div id="sidebar-overlay" class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-40 hidden md:hidden transition-opacity"></div>

    <!-- SIDEBAR -->
    <aside id="sidebar" class="w-64 bg-slate-950 border-r border-slate-800 flex flex-col justify-between absolute md:relative inset-y-0 left-0 transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out z-50 h-full">
        <div class="flex-1 overflow-y-auto">
            <div class="p-6 border-b border-slate-800 flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-cyan-500 tracking-tight">FANTASY FC</h1>
                    <p class="text-xs text-slate-500 mt-1">EAFC26 Sprint Manager</p>
                </div>
                <button id="close-menu-btn" class="md:hidden text-slate-500 hover:text-white"><i class="fa-solid fa-xmark text-2xl"></i></button>
            </div>
            
            <nav class="p-4 space-y-2" id="nav-menu">
                <button onclick="switchTab('ranking')" class="nav-btn w-full flex items-center px-4 py-3.5 text-sm font-medium rounded-xl bg-emerald-900/30 text-emerald-400 border border-emerald-500/50" data-target="ranking">
                    <i class="fa-solid fa-trophy w-6 text-center"></i> Ranking & Sprint
                </button>
                <button onclick="switchTab('team')" class="nav-btn w-full flex items-center px-4 py-3.5 text-sm font-medium rounded-xl text-slate-400 hover:bg-slate-800 hover:text-slate-200 transition" data-target="team">
                    <i class="fa-solid fa-shield-halved w-6 text-center"></i> Meu Elenco
                </button>
                <button onclick="switchTab('draft')" class="nav-btn w-full flex items-center px-4 py-3.5 text-sm font-medium rounded-xl text-slate-400 hover:bg-slate-800 hover:text-slate-200 transition" data-target="draft">
                    <i class="fa-solid fa-users-viewfinder w-6 text-center"></i> Draft Snake
                </button>
                <button onclick="switchTab('market')" class="nav-btn w-full flex items-center px-4 py-3.5 text-sm font-medium rounded-xl text-slate-400 hover:bg-slate-800 hover:text-slate-200 transition" data-target="market">
                    <i class="fa-solid fa-money-bill-transfer w-6 text-center"></i> Mercado
                </button>
                <button onclick="switchTab('admin')" class="nav-btn w-full flex items-center px-4 py-3.5 text-sm font-medium rounded-xl text-red-400 hover:bg-red-900/20 hover:text-red-300 transition mt-6 border border-transparent hover:border-red-900/50" data-target="admin">
                    <i class="fa-solid fa-gear w-6 text-center"></i> Admin Panel
                </button>
            </nav>
        </div>

        <div class="p-4 border-t border-slate-800 bg-slate-950 shrink-0">
            <div class="flex items-center space-x-3 mb-4 p-2 bg-slate-900 rounded-xl border border-slate-800">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center font-bold text-white shadow-inner">GM</div>
                <div class="flex-1 overflow-hidden">
                    <div class="text-sm font-bold text-white truncate"><?php echo $_SESSION['gm_name'] ?? 'Usuário'; ?></div>
                    <div class="text-[10px] text-emerald-500 flex items-center"><span class="w-2 h-2 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span> Online (DB: <span id="db-status-badge"></span>)</div>
                </div>
            </div>
            <form action="index.php" method="POST">
                <input type="hidden" name="action" value="logout">
                <button type="submit" class="w-full bg-slate-800 hover:bg-red-900/30 text-slate-300 hover:text-red-400 hover:border-red-900/50 text-xs py-3 rounded-xl transition border border-slate-700 font-bold flex justify-center items-center">
                    <i class="fa-solid fa-arrow-right-from-bracket mr-2"></i> Sair do Painel
                </button>
            </form>
        </div>
    </aside>

    <main class="flex-1 overflow-y-auto bg-slate-900 w-full relative z-0">
        <div class="max-w-6xl mx-auto p-4 md:p-8">
            
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 md:mb-8 bg-slate-800/80 p-4 md:p-5 rounded-2xl border border-slate-700 backdrop-blur-sm shadow-sm gap-4">
                <div>
                    <h2 class="text-slate-400 text-xs uppercase tracking-wider font-bold mb-1">Status da Simulação</h2>
                    <div class="text-lg md:text-xl font-bold text-white flex items-center">
                        <i class="fa-solid fa-calendar-days mr-2 text-emerald-500"></i> Sprint Atual: <span id="sprint-display" class="ml-2 bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 px-3 py-1 rounded-lg text-sm md:text-base"></span>
                    </div>
                </div>
                <div class="text-left md:text-right w-full md:w-auto">
                    <div class="text-slate-400 text-xs uppercase tracking-wider font-bold mb-1">Seu Time</div>
                    <div class="text-base md:text-lg font-bold text-white bg-slate-900 px-4 py-2 rounded-lg border border-slate-700 inline-block">Time Fantasy 1</div>
                </div>
            </div>

            <!-- RANKING -->
            <section id="tab-ranking" class="tab-content active">
                <div class="bg-slate-800 rounded-2xl p-4 md:p-6 shadow-xl border border-slate-700">
                    <h2 class="text-xl md:text-2xl font-bold text-white mb-6 flex items-center"><i class="fa-solid fa-ranking-star mr-3 text-yellow-400"></i> Classificação Geral</h2>
                    <div class="overflow-x-auto -mx-4 md:mx-0">
                        <table class="w-full text-left text-slate-300 min-w-[500px]">
                            <thead class="bg-slate-900/50 text-slate-400 text-xs uppercase font-semibold">
                                <tr>
                                    <th class="px-4 py-3 md:rounded-tl-lg w-16">Pos</th>
                                    <th class="px-4 py-3">Franquia / GM</th>
                                    <th class="px-4 py-3 md:rounded-tr-lg text-right w-24">Pontos</th>
                                </tr>
                            </thead>
                            <tbody id="ranking-body"></tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- MEU ELENCO & CAMPINHO -->
            <section id="tab-team" class="tab-content">
                <div class="bg-slate-800 rounded-2xl p-4 md:p-6 shadow-xl border border-slate-700">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4 border-b border-slate-700 pb-4">
                        <h2 class="text-xl md:text-2xl font-bold text-white flex items-center"><i class="fa-solid fa-tshirt mr-3 text-blue-400"></i> Meu Elenco / Escalação</h2>
                        <div class="bg-slate-900 border border-slate-600 rounded-lg p-2 flex items-center w-full sm:w-auto shadow-inner">
                            <i class="fa-solid fa-chess-board text-emerald-400 ml-2 mr-2"></i>
                            <select id="formation-select" onchange="renderPitch()" class="bg-transparent text-white focus:outline-none text-sm font-bold w-full cursor-pointer appearance-none">
                                <!-- Preenchido pelo JS -->
                            </select>
                            <i class="fa-solid fa-chevron-down text-slate-500 text-xs mr-2 ml-2 pointer-events-none"></i>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- O CAMPINHO DE FUTEBOL -->
                        <div class="lg:col-span-2 flex justify-center">
                            <div id="pitch-container" class="relative w-full max-w-lg aspect-[3/4] pitch-pattern rounded-lg border-4 border-white overflow-hidden shadow-2xl">
                                <!-- Linhas SVG simuladas via CSS no JS -->
                            </div>
                        </div>

                        <!-- BANCO DE RESERVAS -->
                        <div class="bg-slate-900 rounded-xl p-4 border border-slate-700 h-fit">
                            <h3 class="text-slate-400 font-bold mb-4 text-sm uppercase tracking-wider flex items-center"><i class="fa-solid fa-chair mr-2"></i> Banco de Reservas</h3>
                            <div id="bench-list" class="space-y-3">
                                <!-- Preenchido pelo JS -->
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- DRAFT ROOM -->
            <section id="tab-draft" class="tab-content">
                <div class="bg-slate-800 rounded-2xl p-4 md:p-6 shadow-xl border border-slate-700">
                    <div class="text-center mb-8 bg-slate-900 p-6 md:p-8 rounded-2xl border border-emerald-900/50 relative overflow-hidden">
                        <h2 class="text-2xl md:text-4xl font-black text-white mb-2 uppercase tracking-wide relative z-10">Draft Room</h2>
                        <div class="inline-flex items-center bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 px-6 py-3 rounded-xl text-base md:text-lg font-bold mt-4 shadow-[0_0_15px_rgba(16,185,129,0.15)] relative z-10">
                            <i class="fa-solid fa-stopwatch mr-3 animate-pulse"></i> Sua vez de escolher!
                        </div>
                    </div>
                    <div id="draft-players-list" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 md:gap-4 text-left"></div>
                </div>
            </section>

            <!-- MERCADO -->
            <section id="tab-market" class="tab-content">
                <div class="bg-slate-800 rounded-2xl p-10 shadow-xl border border-slate-700 text-center py-20">
                    <div class="w-24 h-24 bg-slate-900 rounded-full flex items-center justify-center mx-auto mb-6 border border-slate-700 shadow-inner">
                        <i class="fa-solid fa-money-bill-transfer text-4xl text-slate-500"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-white mb-3">Central de Negócios</h2>
                    <p class="text-slate-400 max-w-md mx-auto text-sm leading-relaxed">Em breve.</p>
                </div>
            </section>

            <!-- ADMIN PANEL -->
            <section id="tab-admin" class="tab-content">
                <div class="bg-slate-800 rounded-2xl p-4 md:p-6 shadow-xl border-2 border-red-900/30">
                    <div class="flex items-center mb-6 pb-4 border-b border-slate-700">
                        <div class="w-12 h-12 rounded-xl bg-red-500/10 flex items-center justify-center mr-4 border border-red-500/20 text-red-500">
                            <i class="fa-solid fa-lock text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-xl md:text-2xl font-bold text-white">Painel do Administrador</h2>
                            <p class="text-slate-400 text-xs md:text-sm">Ajuste de pontuações pós-simulação no EAFC.</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4" id="admin-points-list"></div>
                </div>
            </section>
        </div>
    </main>

    <script>
        const appState = <?php echo json_encode($app_data); ?>;
        
        // --- MAPA DE FORMAÇÕES DO CAMPINHO (Defesa, Meio, Ataque) ---
        const formationMap = {
            '4-4-2': [4, 4, 2],
            '4-3-3': [4, 3, 3],
            '4-2-3-1': [4, 5, 1], // Simplificando linhas pro flexbox
            '3-5-2': [3, 5, 2],
            '5-3-2': [5, 3, 2]
        };

        // --- INICIALIZAÇÃO ---
        document.addEventListener('DOMContentLoaded', () => {
            if(appState && appState.sprint) {
                document.getElementById('sprint-display').innerText = `${appState.sprint} / ${appState.max_sprints}`;
                document.getElementById('db-status-badge').innerText = appState.db_status ? 'ON' : 'OFF';
                document.getElementById('db-status-badge').className = appState.db_status ? 'text-emerald-400 font-bold' : 'text-red-400 font-bold';
                
                // Popula select de formações
                const select = document.getElementById('formation-select');
                select.innerHTML = appState.formations.map(f => `<option value="${f}">${f}</option>`).join('');
                
                renderRanking();
                renderPitch(); // Renderiza o Campinho
                renderBench(); // Renderiza os Reservas
                renderDraft();
                renderAdminPanel();
            }
        });

        // Controle Menu/Abas
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        function toggleMobileMenu() {
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
            document.body.classList.toggle('menu-open');
        }
        document.getElementById('mobile-menu-btn')?.addEventListener('click', toggleMobileMenu);
        document.getElementById('close-menu-btn')?.addEventListener('click', toggleMobileMenu);
        overlay?.addEventListener('click', toggleMobileMenu);

        function switchTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.getElementById(`tab-${tabId}`).classList.add('active');
            
            document.querySelectorAll('.nav-btn').forEach(btn => {
                btn.className = 'nav-btn w-full flex items-center px-4 py-3.5 text-sm font-medium rounded-xl text-slate-400 hover:bg-slate-800 hover:text-slate-200 transition';
            });
            const activeBtn = document.querySelector(`.nav-btn[data-target="${tabId}"]`);
            if (activeBtn) activeBtn.className = tabId === 'admin' 
                    ? 'nav-btn w-full flex items-center px-4 py-3.5 text-sm font-medium rounded-xl bg-red-900/20 text-red-400 mt-6 border border-red-500/30' 
                    : 'nav-btn w-full flex items-center px-4 py-3.5 text-sm font-medium rounded-xl bg-emerald-900/30 text-emerald-400 border border-emerald-500/50';

            if (window.innerWidth < 768 && !sidebar.classList.contains('-translate-x-full')) toggleMobileMenu();
        }

        // --- RENDERIZAÇÃO DO CAMPINHO (A MÁGICA) ---
        function renderPitch() {
            const formation = document.getElementById('formation-select').value;
            const structure = formationMap[formation] || [4, 3, 3]; // Default
            
            // Assume 1 Goleiro e 10 de linha
            const starters = appState.players.slice(0, 11);
            const gk = starters[0]; // Goleiro é sempre o índice 0 no nosso mock
            const outfield = starters.slice(1);
            
            // Separa os jogadores nas linhas (Defesa -> Meio -> Ataque)
            // Para desenhar visualmente, vamos do Ataque para a Defesa (top to bottom)
            let rows = [];
            let index = 0;
            structure.forEach(count => {
                rows.push(outfield.slice(index, index + count));
                index += count;
            });
            rows.reverse(); // Inverte para renderizar o ataque no topo do HTML

            // HTML Base do Campo
            let html = `
                <!-- Elementos visuais do campo (linhas) -->
                <div class="absolute inset-0 border border-white/40 m-2"></div>
                <div class="absolute top-1/2 left-0 w-full border-t border-white/40 transform -translate-y-1/2"></div>
                <div class="absolute top-1/2 left-1/2 w-24 h-24 border border-white/40 rounded-full transform -translate-x-1/2 -translate-y-1/2"></div>
                <!-- Áreas -->
                <div class="absolute bottom-2 left-1/2 w-40 h-20 border border-white/40 transform -translate-x-1/2 border-b-0"></div>
                <div class="absolute bottom-22 left-1/2 w-16 h-8 border border-white/40 rounded-t-full transform -translate-x-1/2 border-b-0 opacity-50"></div>
                
                <div class="absolute top-2 left-1/2 w-40 h-20 border border-white/40 transform -translate-x-1/2 border-t-0"></div>
                
                <!-- Container de Jogadores -->
                <div class="absolute inset-0 flex flex-col justify-between py-6 px-4 z-10">
            `;

            // Renderiza Linhas (Ataque -> Meio -> Defesa)
            rows.forEach(row => {
                html += `<div class="flex justify-around items-center w-full">`;
                row.forEach(p => { html += createPlayerNode(p); });
                html += `</div>`;
            });

            // Renderiza Goleiro (Bottom)
            html += `<div class="flex justify-center items-center w-full mt-4">`;
            html += createPlayerNode(gk, true);
            html += `</div></div>`;

            document.getElementById('pitch-container').innerHTML = html;
        }

        // Criar a carta redondinha do jogador no campo
        function createPlayerNode(p, isGk = false) {
            if(!p) return `<div class="w-10 h-10"></div>`; // Placeholder se faltar jogador
            
            // Define cor baseado no Overall (OVR)
            let ovrColor = 'bg-emerald-500';
            if(p.ovr >= 90) ovrColor = 'bg-yellow-500 text-black';
            else if(p.ovr < 85) ovrColor = 'bg-blue-500';
            
            if(isGk) ovrColor = 'bg-slate-300 text-black';

            return `
                <div class="flex flex-col items-center group cursor-pointer transform hover:scale-110 transition duration-200">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full ${ovrColor} border-2 border-white flex items-center justify-center shadow-lg relative">
                        <span class="font-black text-sm sm:text-base drop-shadow-md">${p.ovr}</span>
                        <div class="absolute -top-2 -right-2 bg-slate-900 border border-slate-600 text-white text-[9px] px-1 rounded font-bold">${p.pos}</div>
                    </div>
                    <div class="bg-slate-900/90 backdrop-blur border border-slate-600 px-2 py-0.5 rounded text-[10px] sm:text-xs text-white font-bold mt-1 text-center truncate max-w-[70px] shadow-lg">
                        ${p.name.split(' ')[0]}
                    </div>
                </div>
            `;
        }

        function renderBench() {
            const bench = appState.players.slice(11);
            document.getElementById('bench-list').innerHTML = bench.map(p => `
                <div class="bg-slate-800 p-3 rounded-lg flex justify-between items-center border border-slate-700 hover:border-slate-500 transition cursor-pointer">
                    <div class="flex items-center">
                        <span class="text-[10px] bg-slate-900 text-slate-400 px-1.5 py-0.5 rounded font-bold w-8 text-center mr-2">${p.pos}</span>
                        <div>
                            <div class="text-white font-bold text-sm">${p.name}</div>
                            <div class="text-[10px] text-slate-500 uppercase">${p.realTeam}</div>
                        </div>
                    </div>
                    <div class="text-slate-300 font-black bg-slate-900 px-2 py-1 rounded text-sm">${p.ovr}</div>
                </div>
            `).join('');
        }

        function renderRanking() {
            const sortedGMs = [...appState.gms].sort((a, b) => b.points - a.points);
            document.getElementById('ranking-body').innerHTML = sortedGMs.map((gm, i) => `
                <tr class="border-b border-slate-700/50 hover:bg-slate-700/20 transition group">
                    <td class="px-4 py-4 font-black ${i === 0 ? 'text-yellow-400 text-lg' : 'text-slate-500'}">${i + 1}º</td>
                    <td class="px-4 py-4"><div class="font-bold text-white">${gm.teamName}</div></td>
                    <td class="px-4 py-4 text-right"><span class="bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-700 text-emerald-400 font-mono font-bold">${gm.points}</span></td>
                </tr>`).join('');
        }

        function renderDraft() {
            document.getElementById('draft-players-list').innerHTML = appState.players.map(p => `
                <div class="bg-slate-900 p-4 rounded-xl border border-slate-700 flex justify-between items-center">
                    <div><span class="text-[10px] bg-slate-800 text-slate-400 px-2 py-0.5 rounded mr-2">${p.pos}</span> <span class="text-white text-sm font-bold">${p.name}</span></div>
                    <button class="bg-emerald-600 px-3 py-1.5 rounded-lg text-xs font-bold text-white">Draft</button>
                </div>`).join('');
        }

        function renderAdminPanel() {
            document.getElementById('admin-points-list').innerHTML = appState.gms.map(gm => `
                <div class="bg-slate-900 p-4 rounded-xl border border-slate-700 flex flex-col justify-between">
                    <div class="text-white font-bold text-sm truncate mb-3">${gm.teamName}</div>
                    <div class="bg-slate-950 p-2 rounded-lg text-center mb-3 text-emerald-400 font-mono text-2xl">${gm.points}</div>
                    <div class="grid grid-cols-3 gap-1.5">
                        <button class="bg-emerald-900/30 text-emerald-500 py-2 rounded text-xs font-bold">+3</button>
                        <button class="bg-blue-900/30 text-blue-500 py-2 rounded text-xs font-bold">+1</button>
                        <button class="bg-red-900/30 text-red-500 py-2 rounded text-xs font-bold">-1</button>
                    </div>
                </div>`).join('');
        }
    </script>
<?php endif; ?>
</body>
</html>