<?php include __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-1 overflow-y-auto bg-slate-900 p-4 md:p-8 relative">
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

    <?php include __DIR__ . '/tabs/dashboard.php'; ?>
    <?php include __DIR__ . '/tabs/team.php'; ?>
    <?php include __DIR__ . '/tabs/all_players.php'; ?>
    <?php include __DIR__ . '/tabs/market.php'; ?>
    <?php include __DIR__ . '/tabs/ranking.php'; ?>
    <?php include __DIR__ . '/tabs/admin.php'; ?>
</main>

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
                    <label class="block text-xs font-bold text-slate-400 mb-1">Posicao</label>
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

<form id="delete-form" action="index.php?page=app&tab=team&manage=1" method="POST" style="display:none;">
    <input type="hidden" name="action" value="deletar_jogador">
    <input type="hidden" name="jogador_id" id="delete_jogador_id" value="">
</form>
