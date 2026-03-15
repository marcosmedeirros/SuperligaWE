<?php
/**
 * FANTASY FC - ARQUITETURA SINGLE-FILE PHP COM AUTENTICAÇÃO
 * Marcos, aqui simulamos um Front Controller. 
 * O fluxo é: POST (ações de form) -> Processa -> Redireciona via GET (Páginas)
 */
session_start();

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
        
        // Simulação: Cadastro feito com sucesso
        header("Location: ?page=login&msg=registered");
        exit;
    }

    // B. LOGIN
    if ($action === 'login') {
        $email = $_POST['email'] ?? '';
        $senha = $_POST['senha'] ?? '';
        
        // Simulação: Aceita qualquer login que não seja vazio
        if (!empty($email) && !empty($senha)) {
            $_SESSION['logged_in'] = true;
            $_SESSION['gm_name'] = "Marcos Medeiros"; // Viria do BD
            header("Location: ?page=app");
            exit;
        } else {
            $msg = "Credenciais inválidas.";
            $msgType = "error";
        }
    }

    // C. RECUPERAÇÃO DE SENHA
    if ($action === 'forgot') {
        $email = $_POST['email'] ?? '';
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

// Mensagens de Sucesso vindas por GET
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'registered') {
        $msg = "Conta criada! Verifique seu e-mail para validar o cadastro.";
        $msgType = "success";
    } elseif ($_GET['msg'] === 'reset_sent') {
        $msg = "Se o e-mail existir, um link de recuperação foi enviado.";
        $msgType = "success";
    }
}

// ------------------------------------------------------------------
// 2. ROTEAMENTO DE PÁGINAS (Frontend View)
// ------------------------------------------------------------------
$page = $_GET['page'] ?? 'login';

// Proteção de Rota (Guards)
if ($page === 'app' && !isset($_SESSION['logged_in'])) {
    header("Location: ?page=login");
    exit;
}
if (in_array($page, ['login', 'register', 'forgot']) && isset($_SESSION['logged_in'])) {
    header("Location: ?page=app");
    exit;
}

// ------------------------------------------------------------------
// 3. DADOS DO APP (Carregados apenas se a página for o app)
// ------------------------------------------------------------------
$app_data = [];
if ($page === 'app') {
    $sprint_atual = 1;
    $max_sprints = 15;
    $formacoes_eafc26 = ['4-4-2', '4-3-3', '4-2-3-1', '3-5-2', '5-3-2'];
    $gms = [];
    for ($i = 1; $i <= 20; $i++) {
        $gms[] = ['id' => $i, 'name' => "GM " . $i, 'teamName' => "Time Fantasy " . $i, 'points' => rand(10, 50)];
    }
    $players = [
        ['id' => 1, 'name' => 'Vini Jr.', 'pos' => 'ATA', 'realTeam' => 'Real Madrid', 'league' => 'La Liga', 'ovr' => 90],
        ['id' => 2, 'name' => 'Haaland', 'pos' => 'ATA', 'realTeam' => 'Man. City', 'league' => 'Premier League', 'ovr' => 91],
        ['id' => 3, 'name' => 'Bellingham', 'pos' => 'MEI', 'realTeam' => 'Real Madrid', 'league' => 'La Liga', 'ovr' => 89],
        ['id' => 4, 'name' => 'Mbappé', 'pos' => 'ATA', 'realTeam' => 'Real Madrid', 'league' => 'La Liga', 'ovr' => 91],
        ['id' => 5, 'name' => 'De Bruyne', 'pos' => 'MEI', 'realTeam' => 'Man. City', 'league' => 'Premier League', 'ovr' => 90],
        ['id' => 6, 'name' => 'Van Dijk', 'pos' => 'ZAG', 'realTeam' => 'Liverpool', 'league' => 'Premier League', 'ovr' => 89],
        ['id' => 7, 'name' => 'Alisson', 'pos' => 'GOL', 'realTeam' => 'Liverpool', 'league' => 'Premier League', 'ovr' => 89],
    ];
    $app_data = ['sprint' => $sprint_atual, 'max_sprints' => $max_sprints, 'gms' => $gms, 'players' => $players, 'formations' => $formacoes_eafc26];
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
        /* Impede scroll no body quando o menu mobile está aberto */
        body.menu-open { overflow: hidden; }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 font-sans antialiased h-screen w-full overflow-hidden flex flex-col md:flex-row">

<?php if ($page === 'login' || $page === 'register' || $page === 'forgot'): ?>
    <!-- ========================================== -->
    <!-- TELA DE AUTENTICAÇÃO (RESPONSIVA)          -->
    <!-- ========================================== -->
    <div class="flex-1 flex items-center justify-center p-4 md:p-8 overflow-y-auto">
        <div class="w-full max-w-md bg-slate-800/80 backdrop-blur-md p-6 md:p-8 rounded-2xl border border-slate-700 shadow-2xl relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-emerald-400 to-cyan-500"></div>
            
            <div class="text-center mb-8 mt-2">
                <h1 class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-cyan-500 tracking-tight">
                    FANTASY FC
                </h1>
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
                    <button type="submit" class="w-full bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-500 hover:to-emerald-400 text-white font-bold py-3.5 px-4 rounded-xl transition shadow-lg shadow-emerald-900/20 mt-2">
                        ENTRAR NO JOGO
                    </button>
                </form>
                <div class="text-center mt-8">
                    <p class="text-slate-400 text-sm">Não é um GM ainda? <a href="?page=register" class="text-emerald-500 font-bold hover:text-emerald-400 transition ml-1">Criar franquia</a></p>
                </div>

            <?php elseif ($page === 'register'): ?>
                <form action="index.php" method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="register">
                    <div>
                        <label class="block text-slate-400 text-xs font-bold mb-2 ml-1">NOME DO GM (SEU NOME)</label>
                        <input type="text" name="nome" required class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-emerald-500 transition" placeholder="Ex: Marcos Medeiros">
                    </div>
                    <div>
                        <label class="block text-slate-400 text-xs font-bold mb-2 ml-1">NOME DA FRANQUIA (TIME)</label>
                        <input type="text" name="time" required class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-emerald-500 transition" placeholder="Ex: Inter de Milão">
                    </div>
                    <div>
                        <label class="block text-slate-400 text-xs font-bold mb-2 ml-1">E-MAIL</label>
                        <input type="email" name="email" required class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-emerald-500 transition" placeholder="seu@email.com">
                    </div>
                    <div>
                        <label class="block text-slate-400 text-xs font-bold mb-2 ml-1">SENHA</label>
                        <input type="password" name="senha" required minlength="6" class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-emerald-500 transition" placeholder="Crie uma senha forte">
                    </div>
                    <button type="submit" class="w-full bg-gradient-to-r from-emerald-600 to-emerald-500 text-white font-bold py-3.5 px-4 rounded-xl transition shadow-lg shadow-emerald-900/20 mt-2">
                        FINALIZAR CADASTRO
                    </button>
                </form>
                <div class="text-center mt-6">
                    <p class="text-slate-400 text-sm"><a href="?page=login" class="text-slate-400 hover:text-white transition"><i class="fa-solid fa-arrow-left mr-2"></i> Voltar ao Login</a></p>
                </div>

            <?php elseif ($page === 'forgot'): ?>
                <form action="index.php" method="POST" class="space-y-5">
                    <input type="hidden" name="action" value="forgot">
                    <p class="text-slate-400 text-sm text-center mb-6 leading-relaxed">Digite o e-mail cadastrado. Enviaremos um link de acesso seguro para redefinir a senha do seu GM.</p>
                    <div>
                        <label class="block text-slate-400 text-xs font-bold mb-2 ml-1">E-MAIL CADASTRADO</label>
                        <input type="email" name="email" required class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-emerald-500 transition" placeholder="seu@email.com">
                    </div>
                    <button type="submit" class="w-full bg-slate-700 hover:bg-slate-600 text-white font-bold py-3.5 px-4 rounded-xl transition mt-2">
                        ENVIAR LINK DE RECUPERAÇÃO
                    </button>
                </form>
                <div class="text-center mt-6">
                    <p class="text-slate-400 text-sm"><a href="?page=login" class="text-slate-400 hover:text-white transition"><i class="fa-solid fa-arrow-left mr-2"></i> Voltar ao Login</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php elseif ($page === 'app'): ?>
    <!-- ========================================== -->
    <!-- APLICAÇÃO PRINCIPAL (SÓ VÊ SE LOGADO)      -->
    <!-- ========================================== -->

    <!-- MOBILE HEADER (Aparece apenas no celular) -->
    <header class="md:hidden flex-none bg-slate-950 border-b border-slate-800 p-4 flex justify-between items-center z-40 relative">
        <h1 class="text-xl font-black text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-cyan-500 tracking-tight">
            FANTASY FC
        </h1>
        <button id="mobile-menu-btn" class="text-slate-400 hover:text-white focus:outline-none p-2 rounded-lg bg-slate-900 border border-slate-800">
            <i class="fa-solid fa-bars text-xl"></i>
        </button>
    </header>

    <!-- OVERLAY ESCURO (Fundo opaco quando o menu mobile está aberto) -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-40 hidden md:hidden transition-opacity"></div>

    <!-- SIDEBAR RESPONSIVA -->
    <!-- No Desktop: Fica na esquerda flexível. No Mobile: Fica escondida (translate-x-full) e sobrepõe a tela (absolute/z-50) -->
    <aside id="sidebar" class="w-64 bg-slate-950 border-r border-slate-800 flex flex-col justify-between absolute md:relative inset-y-0 left-0 transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out z-50 h-full">
        <div class="flex-1 overflow-y-auto">
            <div class="p-6 border-b border-slate-800 flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-cyan-500 tracking-tight">
                        FANTASY FC
                    </h1>
                    <p class="text-xs text-slate-500 mt-1">EAFC26 Sprint Manager</p>
                </div>
                <!-- Botão de fechar no mobile -->
                <button id="close-menu-btn" class="md:hidden text-slate-500 hover:text-white">
                    <i class="fa-solid fa-xmark text-2xl"></i>
                </button>
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
                <div class="w-10 h-10 rounded-lg bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center font-bold text-white shadow-inner">
                    GM
                </div>
                <div class="flex-1 overflow-hidden">
                    <div class="text-sm font-bold text-white truncate"><?php echo $_SESSION['gm_name'] ?? 'Usuário'; ?></div>
                    <div class="text-xs text-emerald-500 flex items-center"><span class="w-2 h-2 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span> Online</div>
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

    <!-- MAIN CONTENT DO APP -->
    <main class="flex-1 overflow-y-auto bg-slate-900 w-full relative z-0">
        <div class="max-w-6xl mx-auto p-4 md:p-8">
            
            <!-- HEADER GERAL -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 md:mb-8 bg-slate-800/80 p-4 md:p-5 rounded-2xl border border-slate-700 backdrop-blur-sm shadow-sm gap-4">
                <div>
                    <h2 class="text-slate-400 text-xs uppercase tracking-wider font-bold mb-1">Status da Simulação</h2>
                    <div class="text-lg md:text-xl font-bold text-white flex items-center">
                        <i class="fa-solid fa-calendar-days mr-2 text-emerald-500"></i> Sprint Atual: 
                        <span id="sprint-display" class="ml-2 bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 px-3 py-1 rounded-lg text-sm md:text-base"></span>
                    </div>
                </div>
                <div class="text-left md:text-right w-full md:w-auto">
                    <div class="text-slate-400 text-xs uppercase tracking-wider font-bold mb-1">Seu Time</div>
                    <div class="text-base md:text-lg font-bold text-white bg-slate-900 px-4 py-2 rounded-lg border border-slate-700 inline-block">Time Fantasy 1</div>
                </div>
            </div>

            <!-- TABS DO JOGO -->
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

            <!-- MEU ELENCO -->
            <section id="tab-team" class="tab-content">
                <div class="bg-slate-800 rounded-2xl p-4 md:p-6 shadow-xl border border-slate-700">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                        <h2 class="text-xl md:text-2xl font-bold text-white flex items-center"><i class="fa-solid fa-tshirt mr-3 text-blue-400"></i> Gestão do Elenco</h2>
                        <div class="bg-slate-900 border border-slate-700 rounded-lg p-2 flex items-center w-full sm:w-auto">
                            <i class="fa-solid fa-chess-board text-slate-500 ml-2 mr-2"></i>
                            <select class="bg-transparent text-white focus:outline-none text-sm font-bold w-full">
                                <option>4-3-3 Ofensivo</option>
                                <option>4-4-2 Clássico</option>
                                <option>3-5-2</option>
                            </select>
                        </div>
                    </div>
                    
                    <h3 class="text-emerald-400 font-bold mb-4 text-sm uppercase tracking-wider border-b border-slate-700 pb-2"><i class="fa-regular fa-circle-check mr-1"></i> Titulares</h3>
                    <div id="starters-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 md:gap-4"></div>
                </div>
            </section>

            <!-- DRAFT ROOM -->
            <section id="tab-draft" class="tab-content">
                <div class="bg-slate-800 rounded-2xl p-4 md:p-6 shadow-xl border border-slate-700">
                    <div class="text-center mb-8 bg-slate-900 p-6 md:p-8 rounded-2xl border border-emerald-900/50 relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-500/10 rounded-full blur-3xl -mr-10 -mt-10"></div>
                        <h2 class="text-2xl md:text-4xl font-black text-white mb-2 uppercase tracking-wide relative z-10">Draft Room</h2>
                        <p class="text-slate-400 text-sm mb-6 relative z-10">Sistema Snake (Ida e Volta)</p>
                        <div class="inline-flex items-center bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 px-6 py-3 rounded-xl text-base md:text-lg font-bold shadow-[0_0_15px_rgba(16,185,129,0.15)] relative z-10">
                            <i class="fa-solid fa-stopwatch mr-3 animate-pulse"></i> Sua vez de escolher!
                        </div>
                    </div>

                    <h3 class="text-slate-300 font-bold mb-4 text-lg"><i class="fa-solid fa-list mr-2"></i> Jogadores Disponíveis</h3>
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
                    <p class="text-slate-400 max-w-md mx-auto text-sm leading-relaxed">Área reservada para Leilões e Trocas (Trades) entre os GMs. Será destrancada após a finalização do Draft Inicial.</p>
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

    <!-- MOTOR JAVASCRIPT REAPROVEITADO E ADAPTADO PARA MOBILE -->
    <script>
        const appState = <?php echo json_encode($app_data); ?>;
        
        // --- CONTROLE DO MENU MOBILE ---
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const closeMenuBtn = document.getElementById('close-menu-btn');

        function toggleMobileMenu() {
            const isClosed = sidebar.classList.contains('-translate-x-full');
            if (isClosed) {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
                document.body.classList.add('menu-open'); // Impede scroll
            } else {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
                document.body.classList.remove('menu-open');
            }
        }

        if(mobileMenuBtn) mobileMenuBtn.addEventListener('click', toggleMobileMenu);
        if(closeMenuBtn) closeMenuBtn.addEventListener('click', toggleMobileMenu);
        if(overlay) overlay.addEventListener('click', toggleMobileMenu);

        // --- INICIALIZAÇÃO DO APP ---
        document.addEventListener('DOMContentLoaded', () => {
            if(appState && appState.sprint) {
                document.getElementById('sprint-display').innerText = `${appState.sprint} / ${appState.max_sprints}`;
                renderRanking();
                renderMyTeam();
                renderDraft();
                renderAdminPanel();
            }
        });

        function switchTab(tabId) {
            // Alterna o conteúdo
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.getElementById(`tab-${tabId}`).classList.add('active');
            
            // Alterna os botões do menu
            document.querySelectorAll('.nav-btn').forEach(btn => {
                btn.className = 'nav-btn w-full flex items-center px-4 py-3.5 text-sm font-medium rounded-xl text-slate-400 hover:bg-slate-800 hover:text-slate-200 transition';
            });
            const activeBtn = document.querySelector(`.nav-btn[data-target="${tabId}"]`);
            if (activeBtn) {
                activeBtn.className = tabId === 'admin' 
                    ? 'nav-btn w-full flex items-center px-4 py-3.5 text-sm font-medium rounded-xl bg-red-900/20 text-red-400 mt-6 border border-red-500/30' 
                    : 'nav-btn w-full flex items-center px-4 py-3.5 text-sm font-medium rounded-xl bg-emerald-900/30 text-emerald-400 border border-emerald-500/50';
            }

            // Fecha o menu mobile se estiver aberto (UX Padrão)
            if (window.innerWidth < 768 && sidebar && !sidebar.classList.contains('-translate-x-full')) {
                toggleMobileMenu();
            }
        }

        // --- FUNÇÕES DE RENDERIZAÇÃO MELHORADAS VISUALMENTE ---
        function renderRanking() {
            const sortedGMs = [...appState.gms].sort((a, b) => b.points - a.points);
            document.getElementById('ranking-body').innerHTML = sortedGMs.map((gm, i) => `
                <tr class="border-b border-slate-700/50 hover:bg-slate-700/20 transition group">
                    <td class="px-4 py-4 font-black ${i === 0 ? 'text-yellow-400 text-lg' : 'text-slate-500'}">${i + 1}º</td>
                    <td class="px-4 py-4">
                        <div class="font-bold text-white">${gm.teamName}</div>
                        <div class="text-[11px] text-slate-500 mt-0.5 uppercase tracking-wide"><i class="fa-solid fa-user mr-1"></i> ${gm.name}</div>
                    </td>
                    <td class="px-4 py-4 text-right">
                        <span class="bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-700 text-emerald-400 font-mono font-bold text-base md:text-lg group-hover:border-emerald-500/50 transition">${gm.points}</span>
                    </td>
                </tr>`).join('');
        }

        function renderMyTeam() {
            document.getElementById('starters-list').innerHTML = appState.players.slice(0, 11).map(p => `
                <div class="bg-slate-900 p-3 md:p-4 rounded-xl border border-slate-700 flex justify-between items-center hover:border-slate-500 transition">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-lg bg-slate-800 border border-slate-700 flex items-center justify-center text-xs font-black mr-3 ${p.pos === 'GOL' ? 'text-yellow-500' : 'text-slate-400'}">${p.pos}</div>
                        <div>
                            <div class="text-white font-bold text-sm md:text-base">${p.name}</div>
                            <div class="text-[10px] text-slate-500 uppercase tracking-widest mt-0.5">${p.realTeam}</div>
                        </div>
                    </div>
                    <div class="bg-emerald-900/20 border border-emerald-900/50 text-emerald-400 px-2 py-1 rounded text-sm md:text-base font-black">${p.ovr}</div>
                </div>`).join('');
        }

        function renderDraft() {
            document.getElementById('draft-players-list').innerHTML = appState.players.map(p => `
                <div class="bg-slate-900 p-4 rounded-xl border border-slate-700 flex justify-between items-center hover:border-emerald-500/50 transition group">
                    <div>
                        <div class="text-white font-bold flex items-center text-sm md:text-base">
                            <span class="text-[10px] bg-slate-800 text-slate-400 px-2 py-0.5 rounded mr-2 font-black">${p.pos}</span>
                            ${p.name}
                        </div>
                        <div class="text-xs text-slate-500 mt-1">${p.realTeam} <span class="text-emerald-500 font-bold ml-1">OVR ${p.ovr}</span></div>
                    </div>
                    <button class="bg-emerald-600 hover:bg-emerald-500 text-white px-3 py-1.5 rounded-lg text-xs font-bold transition shadow shadow-emerald-900/50 md:opacity-0 group-hover:opacity-100">
                        Draftar
                    </button>
                </div>`).join('');
        }

        function renderAdminPanel() {
            document.getElementById('admin-points-list').innerHTML = appState.gms.map(gm => `
                <div class="bg-slate-900 p-4 rounded-xl border border-slate-700 flex flex-col justify-between">
                    <div>
                        <div class="text-white font-bold text-sm truncate mb-1" title="${gm.teamName}">${gm.teamName}</div>
                        <div class="text-[10px] text-slate-500 uppercase tracking-wide mb-3">${gm.name}</div>
                    </div>
                    
                    <div class="bg-slate-950 p-2 rounded-lg text-center border border-slate-800 mb-3">
                        <span class="text-[10px] text-slate-500 uppercase font-bold">Total Sprint</span><br>
                        <span class="text-2xl text-emerald-400 font-mono font-black">${gm.points}</span>
                    </div>

                    <div class="grid grid-cols-3 gap-1.5">
                        <button class="bg-emerald-900/30 hover:bg-emerald-600 text-emerald-500 hover:text-white border border-emerald-500/30 py-2 rounded-lg text-xs font-bold transition">+3</button>
                        <button class="bg-blue-900/30 hover:bg-blue-600 text-blue-500 hover:text-white border border-blue-500/30 py-2 rounded-lg text-xs font-bold transition">+1</button>
                        <button class="bg-red-900/30 hover:bg-red-600 text-red-500 hover:text-white border border-red-500/30 py-2 rounded-lg text-xs font-bold transition">-1</button>
                    </div>
                </div>`).join('');
        }
    </script>
<?php endif; ?>
</body>
</html>