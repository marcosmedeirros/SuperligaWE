<?php
$saves  = $page_data['saves'] ?? [1 => null, 2 => null];
$usuario_nome = $_SESSION['ecofut_usuario_nome'] ?? 'Treinador';

function fmt_saldo(int $v): string {
    if ($v >= 1_000_000) return 'R$ ' . number_format($v / 1_000_000, 1, ',', '.') . 'M';
    return 'R$ ' . number_format($v / 1_000, 0, ',', '.') . 'K';
}
function fmt_data(string $d): string {
    return date('d/m/Y H:i', strtotime($d));
}
?>

<!-- navbar topo -->
<nav class="border-b border-slate-800 bg-slate-900/80 backdrop-blur sticky top-0 z-50">
    <div class="max-w-4xl mx-auto px-4 py-3 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <i class="fas fa-futbol text-green-400 text-lg"></i>
            <span class="text-xl font-black"><span class="text-white">ECO</span><span class="text-green-400">FUT</span></span>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-slate-400 text-sm hidden sm:block">Olá, <strong class="text-white"><?= htmlspecialchars($usuario_nome) ?></strong></span>
            <form method="POST" action="?page=saves">
                <input type="hidden" name="action" value="logout">
                <button class="text-slate-400 hover:text-red-400 text-sm flex items-center gap-1.5 transition">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </button>
            </form>
        </div>
    </div>
</nav>

<div class="max-w-4xl mx-auto px-4 py-12">

    <!-- cabeçalho -->
    <div class="text-center mb-12">
        <h2 class="text-3xl font-black text-white mb-2">Selecionar Save</h2>
        <p class="text-slate-400">Você tem 2 slots de save. Iniciar um novo jogo apaga o save anterior do slot.</p>
    </div>

    <!-- slots -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php for ($slot = 1; $slot <= 2; $slot++):
            $save = $saves[$slot];
            $vazio = ($save === null);
        ?>
        <div class="bg-slate-900 border <?= $vazio ? 'border-slate-800 border-dashed' : 'border-slate-700' ?> rounded-2xl overflow-hidden shadow-xl group relative">

            <!-- badge slot -->
            <div class="absolute top-4 right-4">
                <span class="text-xs font-bold bg-slate-800 text-slate-400 px-2 py-1 rounded-full">SLOT <?= $slot ?></span>
            </div>

            <?php if (!$vazio): ?>
            <!-- save existente -->
            <div class="p-6">
                <div class="flex items-start gap-4 mb-5">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-green-500/20 to-green-700/20 border border-green-500/30 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-shield-halved text-green-400 text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white"><?= htmlspecialchars($save['nome_time']) ?></h3>
                        <p class="text-sm text-slate-400">Treinador: <span class="text-slate-300"><?= htmlspecialchars($save['nome_treinador']) ?></span></p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 mb-6">
                    <div class="bg-slate-800 rounded-xl p-3">
                        <p class="text-xs text-slate-500 mb-0.5">Temporada</p>
                        <p class="text-lg font-bold text-white"><?= (int)$save['temporada'] ?>ª</p>
                    </div>
                    <div class="bg-slate-800 rounded-xl p-3">
                        <p class="text-xs text-slate-500 mb-0.5">Saldo</p>
                        <p class="text-lg font-bold text-green-400"><?= fmt_saldo((int)$save['saldo']) ?></p>
                    </div>
                </div>

                <p class="text-xs text-slate-600 mb-4"><i class="fas fa-clock mr-1"></i> Salvo em <?= fmt_data($save['updated_at']) ?></p>

                <!-- botão continuar -->
                <form method="POST" action="?page=saves">
                    <input type="hidden" name="action" value="carregar_save">
                    <input type="hidden" name="slot" value="<?= $slot ?>">
                    <button class="w-full bg-green-600 hover:bg-green-500 text-white font-bold py-3 rounded-xl transition-all shadow-lg shadow-green-500/20 mb-3">
                        <i class="fas fa-play mr-2"></i> Continuar
                    </button>
                </form>

                <!-- botão novo jogo (apaga) -->
                <button onclick="abrirModalNovo(<?= $slot ?>, true)"
                    class="w-full bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white font-semibold py-2.5 rounded-xl transition text-sm">
                    <i class="fas fa-rotate-right mr-1.5 text-orange-400"></i> Novo Jogo <span class="text-orange-400 text-xs">(apaga este save)</span>
                </button>
            </div>

            <?php else: ?>
            <!-- slot vazio -->
            <div class="p-6 flex flex-col items-center justify-center min-h-[280px] text-center">
                <div class="w-16 h-16 rounded-2xl bg-slate-800 border-2 border-dashed border-slate-700 flex items-center justify-center mb-4">
                    <i class="fas fa-plus text-slate-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-400 mb-1">Slot Vazio</h3>
                <p class="text-slate-600 text-sm mb-6">Nenhum jogo salvo aqui</p>
                <button onclick="abrirModalNovo(<?= $slot ?>, false)"
                    class="bg-green-600 hover:bg-green-500 text-white font-bold px-8 py-3 rounded-xl transition-all shadow-lg shadow-green-500/20">
                    <i class="fas fa-plus mr-2"></i> Novo Jogo
                </button>
            </div>
            <?php endif; ?>
        </div>
        <?php endfor; ?>
    </div>
</div>

<!-- ── MODAL NOVO JOGO ──────────────────────────────────────────── -->
<div id="modal-novo" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <!-- backdrop -->
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="fecharModal()"></div>

    <div class="relative bg-slate-900 border border-slate-700 rounded-2xl p-8 w-full max-w-md shadow-2xl z-10">
        <button onclick="fecharModal()" class="absolute top-4 right-4 text-slate-500 hover:text-white transition">
            <i class="fas fa-times text-xl"></i>
        </button>

        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-xl bg-green-500/10 border border-green-500/30 mb-3">
                <i class="fas fa-futbol text-green-400 text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-white">Novo Jogo</h3>
            <p id="modal-aviso" class="text-sm text-orange-400 mt-1 hidden">
                <i class="fas fa-triangle-exclamation mr-1"></i> O save atual neste slot será apagado!
            </p>
        </div>

        <?php if ($msg && $msg_tipo === 'erro'): ?>
            <div class="flex items-center gap-2 bg-red-500/10 border border-red-500/30 text-red-400 rounded-xl px-4 py-3 mb-5 text-sm">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="?page=saves" class="space-y-4">
            <input type="hidden" name="action" value="novo_save">
            <input type="hidden" name="slot" id="modal-slot" value="1">

            <div>
                <label class="block text-sm text-slate-400 mb-1.5">Nome do treinador</label>
                <div class="relative">
                    <i class="fas fa-user-tie absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 text-sm"></i>
                    <input type="text" name="nome_treinador" required minlength="2" maxlength="50"
                        class="w-full bg-slate-800 border border-slate-700 rounded-xl pl-10 pr-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-green-500 focus:ring-1 focus:ring-green-500 transition"
                        placeholder="Ex: Guardiola">
                </div>
            </div>

            <div>
                <label class="block text-sm text-slate-400 mb-1.5">Nome do time</label>
                <div class="relative">
                    <i class="fas fa-shield-halved absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 text-sm"></i>
                    <input type="text" name="nome_time" required minlength="2" maxlength="60"
                        class="w-full bg-slate-800 border border-slate-700 rounded-xl pl-10 pr-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-green-500 focus:ring-1 focus:ring-green-500 transition"
                        placeholder="Ex: Flamengo">
                </div>
            </div>

            <div class="bg-slate-800/50 border border-slate-700 rounded-xl p-3 text-xs text-slate-500">
                <i class="fas fa-info-circle text-slate-600 mr-1"></i>
                Você começará na temporada 1 com R$ 10.000.000 de saldo.
                Seleção do elenco será feita na próxima etapa.
            </div>

            <button type="submit"
                class="w-full bg-green-600 hover:bg-green-500 text-white font-bold py-3 rounded-xl transition-all shadow-lg shadow-green-500/20">
                Começar! <i class="fas fa-arrow-right ml-1"></i>
            </button>
        </form>
    </div>
</div>

<script>
function abrirModalNovo(slot, temSave) {
    document.getElementById('modal-slot').value = slot;
    document.getElementById('modal-aviso').classList.toggle('hidden', !temSave);
    const modal = document.getElementById('modal-novo');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}
function fecharModal() {
    const modal = document.getElementById('modal-novo');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') fecharModal(); });

<?php if ($msg && $msg_tipo === 'erro'): ?>
// reabre modal se houve erro de validação
abrirModalNovo(<?= (int)($_POST['slot'] ?? 1) ?>, <?= ($saves[(int)($_POST['slot'] ?? 1)] !== null ? 'true' : 'false') ?>);
<?php endif; ?>
</script>
