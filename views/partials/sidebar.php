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
        <form action="index.php" method="POST">
            <input type="hidden" name="action" value="logout">
            <button class="w-full bg-slate-800 hover:bg-red-900/30 text-xs py-3 rounded-xl font-bold text-slate-400 hover:text-red-400 transition"><i class="fa-solid fa-power-off mr-2"></i>Sair</button>
        </form>
    </div>
</aside>
