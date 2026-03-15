<section id="tab-dashboard" class="tab-content active">
    <h2 class="text-2xl font-bold text-white mb-6"><i class="fa-solid fa-chart-line text-emerald-400 mr-2"></i> Command Center</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-slate-800 p-5 rounded-2xl border border-slate-700 shadow-lg flex items-center justify-between">
            <div>
                <p class="text-xs text-slate-400 uppercase font-bold tracking-wider mb-1">Posicao Ranking</p>
                <h3 class="text-3xl font-black text-yellow-400" id="dash-rank">--</h3>
                <p class="text-xs text-emerald-400 mt-1" id="dash-pts">-- pts</p>
            </div>
            <div class="w-12 h-12 bg-slate-900 rounded-full flex items-center justify-center border border-slate-700 text-yellow-400 text-xl"><i class="fa-solid fa-trophy"></i></div>
        </div>

        <div class="bg-slate-800 p-5 rounded-2xl border border-slate-700 shadow-lg flex items-center justify-between" id="dash-cap-card">
            <div>
                <p class="text-xs text-slate-400 uppercase font-bold tracking-wider mb-1">Cap (Top 8 OVR)</p>
                <h3 class="text-3xl font-black text-white" id="dash-cap-val">--</h3>
                <p class="text-xs text-slate-400 mt-1">Limite: <span id="dash-cap-limit"></span></p>
            </div>
            <div class="w-12 h-12 bg-slate-900 rounded-full flex items-center justify-center border border-slate-700 text-xl"><i class="fa-solid fa-wallet"></i></div>
        </div>

        <div class="bg-slate-800 p-5 rounded-2xl border border-slate-700 shadow-lg flex items-center justify-between">
            <div>
                <p class="text-xs text-slate-400 uppercase font-bold tracking-wider mb-1">Tamanho do Elenco</p>
                <h3 class="text-3xl font-black text-white" id="dash-athletes">--</h3>
                <p class="text-xs text-slate-400 mt-1">Jogadores Ativos</p>
            </div>
            <div class="w-12 h-12 bg-slate-900 rounded-full flex items-center justify-center border border-slate-700 text-blue-400 text-xl"><i class="fa-solid fa-users"></i></div>
        </div>

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
        <div class="bg-slate-800 rounded-2xl p-6 border border-slate-700 shadow-lg">
            <h3 class="text-lg font-bold text-white mb-4"><i class="fa-solid fa-fire text-orange-500 mr-2"></i> Top 5 GMs</h3>
            <div class="space-y-3" id="dash-top5"></div>
        </div>
        <div class="bg-slate-800 rounded-2xl p-6 border border-slate-700 shadow-lg">
            <h3 class="text-lg font-bold text-white mb-4"><i class="fa-solid fa-envelope-open-text text-emerald-500 mr-2"></i> Propostas de Negocio</h3>
            <div class="bg-slate-900/50 rounded-xl p-6 text-center border border-dashed border-slate-700">
                <i class="fa-regular fa-folder-open text-3xl text-slate-600 mb-2"></i>
                <p class="text-slate-400 text-sm">Nenhuma proposta recebida no momento.</p>
            </div>
        </div>
    </div>
</section>
