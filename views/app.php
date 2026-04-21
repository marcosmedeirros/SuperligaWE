<?php
$save       = $page_data['save'] ?? [];
$nome_time  = $save['nome_time'] ?? 'Meu Time';
$treinador  = $save['nome_treinador'] ?? 'Treinador';
$temporada  = $save['temporada'] ?? 1;
$saldo      = $save['saldo'] ?? 0;
$slot       = $_SESSION['ecofut_save_slot'] ?? 1;
?>

<div class="min-h-screen flex flex-col">

    <!-- topbar -->
    <header class="bg-slate-900 border-b border-slate-800 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <i class="fas fa-futbol text-green-400"></i>
                <span class="font-black text-lg hidden sm:block"><span class="text-white">ECO</span><span class="text-green-400">FUT</span></span>
            </div>

            <div class="flex items-center gap-2 sm:gap-6 text-sm">
                <div class="text-center hidden md:block">
                    <p class="text-slate-500 text-xs">Time</p>
                    <p class="font-bold text-white"><?= htmlspecialchars($nome_time) ?></p>
                </div>
                <div class="text-center">
                    <p class="text-slate-500 text-xs">Temporada</p>
                    <p class="font-bold text-white"><?= $temporada ?>ª</p>
                </div>
                <div class="text-center">
                    <p class="text-slate-500 text-xs">Saldo</p>
                    <p class="font-bold text-green-400">R$ <?= number_format($saldo, 0, ',', '.') ?></p>
                </div>
                <div class="flex items-center gap-2">
                    <form method="POST" action="?page=app">
                        <input type="hidden" name="action" value="sair_save">
                        <button class="text-slate-500 hover:text-white text-xs flex items-center gap-1 transition px-2 py-1 rounded-lg hover:bg-slate-800">
                            <i class="fas fa-floppy-disk"></i> <span class="hidden sm:inline">Saves</span>
                        </button>
                    </form>
                    <form method="POST" action="?page=app">
                        <input type="hidden" name="action" value="logout">
                        <button class="text-slate-500 hover:text-red-400 text-xs flex items-center gap-1 transition px-2 py-1 rounded-lg hover:bg-slate-800">
                            <i class="fas fa-sign-out-alt"></i> <span class="hidden sm:inline">Sair</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <!-- conteúdo principal (placeholder — próximas etapas) -->
    <main class="flex-1 flex items-center justify-center p-8">
        <div class="text-center max-w-lg">
            <div class="inline-flex items-center justify-center w-24 h-24 rounded-2xl bg-green-500/10 border border-green-500/20 mb-6">
                <i class="fas fa-futbol text-green-400 text-4xl"></i>
            </div>
            <h2 class="text-3xl font-black text-white mb-3">
                Bem-vindo ao <span class="text-green-400"><?= htmlspecialchars($nome_time) ?></span>!
            </h2>
            <p class="text-slate-400 mb-2">Treinador: <strong class="text-white"><?= htmlspecialchars($treinador) ?></strong></p>
            <p class="text-slate-400 mb-8">O elenco, campeonatos e demais módulos serão construídos nas próximas etapas.</p>

            <div class="grid grid-cols-3 gap-4 mb-8">
                <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
                    <i class="fas fa-calendar-alt text-green-400 text-xl mb-2"></i>
                    <p class="text-xs text-slate-500">Temporada</p>
                    <p class="text-xl font-black text-white"><?= $temporada ?>ª</p>
                </div>
                <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
                    <i class="fas fa-coins text-yellow-400 text-xl mb-2"></i>
                    <p class="text-xs text-slate-500">Saldo inicial</p>
                    <p class="text-xl font-black text-green-400">10M</p>
                </div>
                <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
                    <i class="fas fa-floppy-disk text-blue-400 text-xl mb-2"></i>
                    <p class="text-xs text-slate-500">Save Slot</p>
                    <p class="text-xl font-black text-white"><?= $slot ?></p>
                </div>
            </div>

            <div class="bg-slate-900 border border-green-500/20 rounded-xl p-5 text-left text-sm text-slate-400">
                <p class="text-green-400 font-bold mb-2"><i class="fas fa-road mr-2"></i>Próximas etapas do EcoFut:</p>
                <ul class="space-y-1.5">
                    <li><i class="fas fa-check text-slate-600 mr-2"></i> <s>Sistema de login e saves</s> <span class="text-green-400 text-xs">✓ Concluído</span></li>
                    <li><i class="fas fa-clock text-yellow-500 mr-2"></i> Elenco, jogadores e atributos detalhados</li>
                    <li><i class="fas fa-clock text-slate-600 mr-2"></i> Motor de simulação de partidas</li>
                    <li><i class="fas fa-clock text-slate-600 mr-2"></i> Campeonatos e tabelas</li>
                    <li><i class="fas fa-clock text-slate-600 mr-2"></i> Finanças e mercado de transferências</li>
                </ul>
            </div>
        </div>
    </main>
</div>
