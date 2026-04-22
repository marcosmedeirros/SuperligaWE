<?php
$saves        = $page_data['saves'] ?? [1 => null, 2 => null];
$times        = $page_data['times'] ?? [];
$usuario_nome = $_SESSION['ecofut_usuario_nome'] ?? 'Treinador';

function fmt_saldo(int $v): string {
    if ($v >= 1_000_000) return 'R$ ' . number_format($v / 1_000_000, 1, ',', '.') . 'M';
    return 'R$ ' . number_format($v / 1_000, 0, ',', '.') . 'K';
}
function fmt_data(?string $d): string {
    if (!$d) return '—';
    return date('d/m/Y H:i', strtotime($d));
}
?>

<nav class="border-b border-slate-800 bg-slate-900/80 backdrop-blur sticky top-0 z-50">
    <div class="max-w-5xl mx-auto px-4 py-3 flex items-center justify-between">
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

<div class="max-w-5xl mx-auto px-4 py-12">

    <?php if (!($db_connected ?? true)): ?>
    <div class="flex items-center gap-3 bg-red-500/10 border border-red-500/30 text-red-400 rounded-xl px-5 py-4 mb-8 text-sm">
        <i class="fas fa-exclamation-triangle text-lg flex-shrink-0"></i>
        <span>Banco de dados indisponível no momento. Tente novamente em instantes.</span>
    </div>
    <?php elseif (empty($times)): ?>
    <div class="flex items-center gap-3 bg-yellow-500/10 border border-yellow-500/30 text-yellow-400 rounded-xl px-5 py-4 mb-8 text-sm">
        <i class="fas fa-circle-exclamation text-lg flex-shrink-0"></i>
        <span>Nenhum time disponível. Os dados do jogo podem estar sendo carregados — recarregue a página.</span>
    </div>
    <?php endif; ?>

    <div class="text-center mb-12">
        <h2 class="text-3xl font-black text-white mb-2">Selecionar Save</h2>
        <p class="text-slate-400">Você tem 2 slots de save. Iniciar um novo jogo apaga o save anterior do slot.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php for ($slot = 1; $slot <= 2; $slot++):
            $save  = $saves[$slot];
            $vazio = ($save === null);
        ?>
        <div class="bg-slate-900 border <?= $vazio ? 'border-slate-800 border-dashed' : 'border-slate-700' ?> rounded-2xl overflow-hidden shadow-xl relative">

            <div class="absolute top-4 right-4">
                <span class="text-xs font-bold bg-slate-800 text-slate-400 px-2 py-1 rounded-full">SLOT <?= $slot ?></span>
            </div>

            <?php if (!$vazio): ?>
            <div class="p-6">
                <div class="flex items-start gap-4 mb-5">
                    <div class="w-14 h-14 rounded-xl flex-shrink-0 flex items-center justify-center"
                         style="background: linear-gradient(135deg, <?= htmlspecialchars($save['cor1'] ?? '#22c55e') ?>33, <?= htmlspecialchars($save['cor1'] ?? '#22c55e') ?>11); border: 1px solid <?= htmlspecialchars($save['cor1'] ?? '#22c55e') ?>44">
                        <i class="fas fa-shield-halved text-2xl" style="color: <?= htmlspecialchars($save['cor1'] ?? '#22c55e') ?>"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white"><?= htmlspecialchars($save['nome_time']) ?></h3>
                        <p class="text-sm text-slate-400">Treinador: <span class="text-slate-300"><?= htmlspecialchars($save['nome_treinador']) ?></span></p>
                        <p class="text-xs text-slate-500 mt-0.5">Rodada <?= (int)($save['rodada_atual'] ?? 1) ?> · T<?= (int)$save['temporada'] ?>ª</p>
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

                <form method="POST" action="?page=saves">
                    <input type="hidden" name="action" value="carregar_save">
                    <input type="hidden" name="slot" value="<?= $slot ?>">
                    <button class="w-full bg-green-600 hover:bg-green-500 text-white font-bold py-3 rounded-xl transition-all shadow-lg shadow-green-500/20 mb-3">
                        <i class="fas fa-play mr-2"></i> Continuar
                    </button>
                </form>

                <button onclick="abrirModalNovo(<?= $slot ?>, true)"
                    class="w-full bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white font-semibold py-2.5 rounded-xl transition text-sm">
                    <i class="fas fa-rotate-right mr-1.5 text-orange-400"></i> Novo Jogo <span class="text-orange-400 text-xs">(apaga este save)</span>
                </button>
            </div>

            <?php else: ?>
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
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="fecharModal()"></div>

    <div class="relative bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-2xl shadow-2xl z-10 max-h-[90vh] flex flex-col">

        <!-- cabeçalho do modal -->
        <div class="p-6 border-b border-slate-800 flex-shrink-0">
            <button onclick="fecharModal()" class="absolute top-4 right-4 text-slate-500 hover:text-white transition">
                <i class="fas fa-times text-xl"></i>
            </button>
            <div class="flex items-center gap-3 mb-1">
                <div class="w-10 h-10 rounded-xl bg-green-500/10 border border-green-500/30 flex items-center justify-center">
                    <i class="fas fa-futbol text-green-400"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-white">Novo Jogo</h3>
                    <p id="modal-aviso" class="text-xs text-orange-400 hidden">
                        <i class="fas fa-triangle-exclamation mr-1"></i> O save atual neste slot será apagado!
                    </p>
                </div>
            </div>
        </div>

        <!-- corpo rolável -->
        <div class="overflow-y-auto flex-1 p-6">

            <?php if (isset($msg) && $msg && isset($msg_tipo) && $msg_tipo === 'erro'): ?>
            <div class="flex items-center gap-2 bg-red-500/10 border border-red-500/30 text-red-400 rounded-xl px-4 py-3 mb-5 text-sm">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($msg) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="?page=saves" id="form-novo">
                <input type="hidden" name="action" value="novo_save">
                <input type="hidden" name="slot" id="modal-slot" value="1">
                <input type="hidden" name="time_id" id="modal-time-id" value="">

                <!-- Nome do treinador -->
                <div class="mb-5">
                    <label class="block text-sm font-medium text-slate-300 mb-1.5">Nome do Treinador</label>
                    <div class="relative">
                        <i class="fas fa-user-tie absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 text-sm"></i>
                        <input type="text" name="nome_treinador" id="inp-treinador" required minlength="2" maxlength="50"
                            class="w-full bg-slate-800 border border-slate-700 rounded-xl pl-10 pr-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-green-500 focus:ring-1 focus:ring-green-500 transition"
                            placeholder="Ex: Guardiola" value="<?= htmlspecialchars($_POST['nome_treinador'] ?? '') ?>">
                    </div>
                </div>

                <!-- Seleção de time -->
                <div class="mb-5">
                    <label class="block text-sm font-medium text-slate-300 mb-2">Escolha seu time</label>
                    <p id="lbl-time-selecionado" class="text-xs text-slate-500 mb-3">Nenhum time selecionado — clique em um time abaixo</p>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2" id="grade-times">
                        <?php foreach ($times as $t): ?>
                        <button type="button"
                            onclick="selecionarTime(<?= (int)$t['id'] ?>, '<?= addslashes($t['nome']) ?>', '<?= addslashes($t['apelido']) ?>', '<?= addslashes($t['cor1']) ?>', '<?= addslashes($t['cor2']) ?>')"
                            data-id="<?= $t['id'] ?>"
                            class="time-btn flex flex-col items-center gap-1.5 p-3 rounded-xl border border-slate-700 bg-slate-800 hover:border-green-500/50 hover:bg-slate-700 transition-all text-center group">

                            <!-- escudo mini com cores do time -->
                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-xs font-black flex-shrink-0"
                                 style="background: linear-gradient(135deg, <?= htmlspecialchars($t['cor1']) ?>, <?= htmlspecialchars($t['cor2']) ?>); color: <?= htmlspecialchars($t['cor2']) === '#FFFFFF' || htmlspecialchars($t['cor2']) === '#ffffff' ? '#000' : '#fff' ?>">
                                <?= htmlspecialchars($t['apelido']) ?>
                            </div>
                            <span class="text-xs font-semibold text-slate-300 group-hover:text-white leading-tight"><?= htmlspecialchars($t['nome']) ?></span>

                            <!-- barra de força -->
                            <div class="w-full bg-slate-700 rounded-full h-1 mt-0.5">
                                <div class="h-1 rounded-full" style="width: <?= $t['forca_base'] ?>%; background: <?= htmlspecialchars($t['cor1']) ?>"></div>
                            </div>
                            <span class="text-[10px] text-slate-500">Força <?= $t['forca_base'] ?></span>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="bg-slate-800/50 border border-slate-700 rounded-xl p-3 text-xs text-slate-500 mb-5">
                    <i class="fas fa-info-circle text-slate-600 mr-1"></i>
                    Temporada 1 · Saldo inicial R$ 10.000.000 · Série A com 20 times · 38 rodadas
                </div>

                <button type="submit" id="btn-comecar" disabled
                    class="w-full bg-green-600 hover:bg-green-500 disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold py-3 rounded-xl transition-all shadow-lg shadow-green-500/20">
                    Começar! <i class="fas fa-arrow-right ml-1"></i>
                </button>
            </form>
        </div>
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

function selecionarTime(id, nome, apelido, cor1, cor2) {
    // Remove seleção anterior
    document.querySelectorAll('.time-btn').forEach(btn => {
        btn.classList.remove('border-green-500', 'bg-green-500/10', 'ring-2', 'ring-green-500/40');
        btn.classList.add('border-slate-700', 'bg-slate-800');
    });

    // Seleciona este
    const btn = document.querySelector(`.time-btn[data-id="${id}"]`);
    if (btn) {
        btn.classList.add('border-green-500', 'bg-green-500/10', 'ring-2', 'ring-green-500/40');
        btn.classList.remove('border-slate-700', 'bg-slate-800');
    }

    document.getElementById('modal-time-id').value = id;
    document.getElementById('lbl-time-selecionado').innerHTML =
        `<i class="fas fa-check-circle text-green-400 mr-1"></i><span class="text-green-400 font-semibold">${nome}</span> selecionado`;
    document.getElementById('btn-comecar').disabled = false;
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') fecharModal(); });

<?php if (isset($msg) && $msg && isset($msg_tipo) && $msg_tipo === 'erro'): ?>
abrirModalNovo(<?= (int)($_POST['slot'] ?? 1) ?>, <?= ($saves[(int)($_POST['slot'] ?? 1)] !== null ? 'true' : 'false') ?>);
<?php endif; ?>
</script>
