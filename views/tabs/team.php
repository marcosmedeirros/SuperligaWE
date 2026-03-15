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
                            <th class="p-3">Idade</th><th class="p-3 rounded-tr-lg text-right">Acoes</th>
                        </tr>
                    </thead>
                    <tbody id="crud-players-list"></tbody>
                </table>
            </div>
        </div>
    </div>
</section>
