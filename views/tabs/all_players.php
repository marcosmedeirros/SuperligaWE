<section id="tab-all_players" class="tab-content">
    <div class="bg-slate-800 rounded-2xl p-6 shadow-xl border border-slate-700">
        <div class="flex flex-col md:flex-row justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-white"><i class="fa-solid fa-database text-slate-400 mr-2"></i> Database Global</h2>
                <p class="text-xs text-slate-400 mt-1">Lista de todos os jogadores ativos no simulador.</p>
            </div>
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
