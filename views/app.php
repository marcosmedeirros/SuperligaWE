<?php
$save          = $page_data['save']                 ?? [];
$elenco        = $page_data['elenco']               ?? [];
$classificacao = $page_data['classificacao']         ?? [];
$partidas      = $page_data['partidas']              ?? [];
$financas      = $page_data['financas']              ?? [];
$mercado       = $page_data['mercado']               ?? [];
$time          = $page_data['time']                  ?? [];
$proxima       = $page_data['proxima_partida']       ?? null;
$ultima        = $page_data['ultima_partida']        ?? null;
$copa          = $page_data['copa']                  ?? [];
$artilheiros   = $page_data['artilheiros']           ?? [];
$jovemRevelado = $page_data['jovem_revelado']        ?? null;
$notifTemp     = $page_data['notificacao_temporada'] ?? null;

$nome_time  = $save['nome_time']      ?? 'Meu Time';
$treinador  = $save['nome_treinador'] ?? 'Treinador';
$temporada  = $save['temporada']      ?? 1;
$saldo      = $save['saldo']          ?? 0;
$rodada     = $save['rodada_atual']   ?? 1;
$slot       = $_SESSION['ecofut_save_slot'] ?? 1;
$timeId     = $save['time_id']        ?? 0;

// Formation & lineup from dados_json
$dadosJsonParsed = json_decode($save['dados_json'] ?? '{}', true) ?: [];
$formacaoAtual   = $dadosJsonParsed['formacao'] ?? '4-3-3';
$bancoSalvosIds  = $dadosJsonParsed['banco_ids'] ?? [];
$taticaAtual     = $dadosJsonParsed['tatica']    ?? 'normal';

// Match setup (partida pendente de simulação do cliente)
$matchSetup = $page_data['match_setup'] ?? null;

// Flash aba after save escalação
$flashAba = $_SESSION['ecofut_flash_aba'] ?? null;
unset($_SESSION['ecofut_flash_aba']);

// Elenco data for JS
$elencoEscalacao = array_map(fn($j) => [
    'id'             => (int)$j['id'],
    'nome'           => $j['nome'],
    'posicao'        => $j['posicao'],
    'forca'          => (int)$j['forca'],
    'potencial'      => (int)($j['potencial']      ?? 70),
    'titular'        => (int)($j['titular']        ?? 0),
    'energia'        => (int)($j['energia']        ?? 100),
    'moral'          => (int)($j['moral']          ?? 75),
    'contundido'     => (int)($j['contundido']     ?? 0),
    'suspenso'       => (int)($j['suspenso']       ?? 0),
    'meses_contrato' => (int)($j['meses_contrato'] ?? 12),
    'lesoes_rodadas' => (int)($j['lesoes_rodadas'] ?? 0),
], $elenco);

$cor1 = $time['cor1'] ?? '#22c55e';
$cor2 = $time['cor2'] ?? '#ffffff';

// Helpers
function appSaldo(int $v): string {
    if (abs($v) >= 1_000_000) return ($v < 0 ? '-' : '') . 'R$ ' . number_format(abs($v) / 1_000_000, 1, ',', '.') . 'M';
    if (abs($v) >= 1_000)     return ($v < 0 ? '-' : '') . 'R$ ' . number_format(abs($v) / 1_000, 0, ',', '.') . 'K';
    return ($v < 0 ? '-' : '') . 'R$ ' . abs($v);
}
function forcaBar(int $f): string {
    $cor = $f >= 75 ? '#22c55e' : ($f >= 65 ? '#eab308' : '#ef4444');
    return "<div class='w-full bg-slate-700 rounded-full h-1.5'><div class='h-1.5 rounded-full' style='width:{$f}%;background:{$cor}'></div></div>";
}
function posLabel(string $p): string {
    $map = ['GOL'=>'GK','ZAG'=>'CB','LD'=>'RB','LE'=>'LB','VOL'=>'CDM','MC'=>'CM','MEI'=>'CAM','PE'=>'LW','PD'=>'RW','ATA'=>'ST'];
    return $map[$p] ?? $p;
}
function posColor(string $p): string {
    $map = ['GOL'=>'#f59e0b','ZAG'=>'#3b82f6','LD'=>'#06b6d4','LE'=>'#06b6d4','VOL'=>'#8b5cf6','MC'=>'#8b5cf6','MEI'=>'#ec4899','PE'=>'#22c55e','PD'=>'#22c55e','ATA'=>'#ef4444'];
    return $map[$p] ?? '#94a3b8';
}

// Monta escalação para campo
$titulares = array_filter($elenco, fn($j) => ($j['titular'] ?? 0) == 1);
usort($titulares, fn($a,$b) => strcmp($a['posicao'],$b['posicao']));

// Posições no campo (layout 4-3-3 aproximado)
$posicoesCampo = [
    'GOL' => ['bottom'=>'8%',  'left'=>'50%'],
    'ZAG' => ['bottom'=>'22%', 'left'=>'50%'],
    'LD'  => ['bottom'=>'22%', 'left'=>'80%'],
    'LE'  => ['bottom'=>'22%', 'left'=>'20%'],
    'VOL' => ['bottom'=>'40%', 'left'=>'50%'],
    'MC'  => ['bottom'=>'40%', 'left'=>'35%'],
    'MEI' => ['bottom'=>'55%', 'left'=>'50%'],
    'PE'  => ['bottom'=>'70%', 'left'=>'20%'],
    'PD'  => ['bottom'=>'70%', 'left'=>'80%'],
    'ATA' => ['bottom'=>'78%', 'left'=>'50%'],
];
?>

<div class="min-h-screen flex flex-col bg-slate-950">

<!-- ── TOPBAR ──────────────────────────────────────────────────────────────── -->
<header class="bg-slate-900 border-b border-slate-800 sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-3 py-2.5 flex items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <i class="fas fa-futbol text-green-400 text-sm"></i>
            <span class="font-black text-base hidden sm:block"><span class="text-white">ECO</span><span class="text-green-400">FUT</span></span>
        </div>

        <!-- stats rápidos -->
        <div class="flex items-center gap-1 sm:gap-4 text-xs">
            <div class="text-center hidden md:block">
                <p class="text-slate-500 text-[10px]">Time</p>
                <p class="font-bold text-white"><?= htmlspecialchars($nome_time) ?></p>
            </div>
            <div class="text-center hidden sm:block">
                <p class="text-slate-500 text-[10px]">Temporada</p>
                <p class="font-bold text-white"><?= $temporada ?>ª</p>
            </div>
            <div class="text-center">
                <p class="text-slate-500 text-[10px]">Rodada</p>
                <p class="font-bold text-white"><?= $rodada ?>/38</p>
            </div>
            <div class="text-center">
                <p class="text-slate-500 text-[10px]">Saldo</p>
                <p class="font-bold text-green-400"><?= appSaldo($saldo) ?></p>
            </div>
            <div class="flex items-center gap-1 ml-1">
                <form method="POST" action="?page=app">
                    <input type="hidden" name="action" value="sair_save">
                    <button title="Saves" class="text-slate-500 hover:text-white text-xs flex items-center gap-1 transition px-2 py-1.5 rounded-lg hover:bg-slate-800">
                        <i class="fas fa-floppy-disk"></i><span class="hidden sm:inline ml-1">Saves</span>
                    </button>
                </form>
                <form method="POST" action="?page=app">
                    <input type="hidden" name="action" value="logout">
                    <button title="Sair" class="text-slate-500 hover:text-red-400 text-xs flex items-center gap-1 transition px-2 py-1.5 rounded-lg hover:bg-slate-800">
                        <i class="fas fa-sign-out-alt"></i><span class="hidden sm:inline ml-1">Sair</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- abas de navegação -->
    <div class="max-w-7xl mx-auto px-3 flex gap-0.5 overflow-x-auto pb-0 border-t border-slate-800">
        <?php
        $abas = [
            ['id'=>'dashboard',    'icon'=>'fa-house',         'label'=>'Início'],
            ['id'=>'elenco',       'icon'=>'fa-users',         'label'=>'Elenco'],
            ['id'=>'campeonato',   'icon'=>'fa-trophy',        'label'=>'Campeonato'],
            ['id'=>'estatisticas', 'icon'=>'fa-chart-bar',     'label'=>'Estatísticas'],
            ['id'=>'financas',     'icon'=>'fa-coins',         'label'=>'Finanças'],
            ['id'=>'mercado',      'icon'=>'fa-arrows-rotate', 'label'=>'Mercado'],
        ];
        foreach ($abas as $i => $aba): ?>
        <button onclick="trocarAba('<?= $aba['id'] ?>')" id="tab-btn-<?= $aba['id'] ?>"
            class="tab-nav-btn flex items-center gap-1.5 px-3 py-2.5 text-xs font-semibold whitespace-nowrap border-b-2 transition-all <?= $i === 0 ? 'border-green-500 text-green-400' : 'border-transparent text-slate-500 hover:text-slate-300' ?>">
            <i class="fas <?= $aba['icon'] ?>"></i> <?= $aba['label'] ?>
        </button>
        <?php endforeach; ?>
    </div>
</header>

<main class="flex-1 max-w-7xl mx-auto w-full px-3 py-4">

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- ABA: DASHBOARD                                                            -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<section id="tab-dashboard" class="tab-content active">

    <?php if (isset($page_data['flash_resultado'])): $fr = $page_data['flash_resultado']; ?>
    <div class="bg-slate-900 border border-slate-700 rounded-2xl p-5 mb-5 flex items-center gap-4">
        <div class="text-center flex-1">
            <p class="text-xs text-slate-500 mb-1"><?= htmlspecialchars($fr['nome_casa']) ?></p>
            <p class="text-4xl font-black text-white"><?= $fr['gols_casa'] ?></p>
        </div>
        <div class="text-slate-500 font-bold text-xl">×</div>
        <div class="text-center flex-1">
            <p class="text-xs text-slate-500 mb-1"><?= htmlspecialchars($fr['nome_fora']) ?></p>
            <p class="text-4xl font-black text-white"><?= $fr['gols_fora'] ?></p>
        </div>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        <?php
        // Posição na tabela
        $posicao = 'N/A';
        foreach ($classificacao as $i => $c) {
            if ((int)$c['time_id'] === $timeId) { $posicao = $i + 1; break; }
        }
        $minhaClass = null;
        foreach ($classificacao as $c) {
            if ((int)$c['time_id'] === $timeId) { $minhaClass = $c; break; }
        }
        ?>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="text-xs text-slate-500 mb-1"><i class="fas fa-list-ol mr-1 text-yellow-400"></i>Posição</p>
            <p class="text-3xl font-black text-white"><?= is_numeric($posicao) ? $posicao.'º' : 'N/A' ?></p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="text-xs text-slate-500 mb-1"><i class="fas fa-star mr-1 text-green-400"></i>Pontos</p>
            <p class="text-3xl font-black text-green-400"><?= $minhaClass ? $minhaClass['pontos'] : 0 ?></p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="text-xs text-slate-500 mb-1"><i class="fas fa-futbol mr-1 text-blue-400"></i>Saldo de Gols</p>
            <?php $sg = $minhaClass ? ($minhaClass['gols_pro'] - $minhaClass['gols_contra']) : 0; ?>
            <p class="text-3xl font-black <?= $sg >= 0 ? 'text-blue-400' : 'text-red-400' ?>"><?= ($sg > 0 ? '+' : '') . $sg ?></p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="text-xs text-slate-500 mb-1"><i class="fas fa-coins mr-1 text-yellow-400"></i>Saldo</p>
            <p class="text-2xl font-black text-green-400"><?= appSaldo($saldo) ?></p>
        </div>
    </div>

    <!-- Notificação de fim de temporada -->
    <?php if ($notifTemp): ?>
    <?php
    $ntCor  = $notifTemp['rebaixado'] ? 'border-red-500/40' : ($notifTemp['campeao'] ? 'border-yellow-400/40' : 'border-slate-700');
    $ntIcon = $notifTemp['rebaixado'] ? 'fa-arrow-down text-red-400' : ($notifTemp['campeao'] ? 'fa-trophy text-yellow-400' : 'fa-flag-checkered text-slate-400');
    $ntMsg  = $notifTemp['rebaixado']
        ? "Rebaixado! Temporada {$notifTemp['temporada']} encerrada no {$notifTemp['posicao']}º lugar. Multa de R$1M aplicada."
        : ($notifTemp['campeao']
            ? "CAMPEÃO! Temporada {$notifTemp['temporada']} encerrada em 1º lugar!"
            : "Temporada {$notifTemp['temporada']} encerrada — {$notifTemp['posicao']}º lugar." . ($notifTemp['continental'] ? " Vaga continental garantida!" : ""));
    ?>
    <div class="bg-slate-900 border <?= $ntCor ?> rounded-2xl p-4 mb-4">
        <p class="text-sm font-bold text-white mb-1"><i class="fas <?= $ntIcon ?> mr-2"></i><?= htmlspecialchars($ntMsg) ?></p>
        <?php if (!empty($notifTemp['jogadores_saindo'])): ?>
        <p class="text-xs text-slate-500 mt-1"><i class="fas fa-door-open mr-1"></i>Contratos encerrados: <?= implode(', ', array_map('htmlspecialchars', $notifTemp['jogadores_saindo'])) ?></p>
        <?php endif; ?>
        <p class="text-xs text-slate-600 mt-1">Nova temporada iniciada. Bom jogo!</p>
    </div>
    <?php endif; ?>

    <!-- Jovem revelado disponível -->
    <?php if ($jovemRevelado): ?>
    <div class="bg-slate-900 border border-purple-500/40 rounded-2xl p-4 mb-4">
        <p class="text-xs font-bold text-purple-400 mb-2"><i class="fas fa-star mr-1.5"></i>Jovem Revelado Disponível!</p>
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="font-bold text-white text-sm"><?= htmlspecialchars($jovemRevelado['nome']) ?></p>
                <p class="text-xs text-slate-400"><?= $jovemRevelado['posicao'] ?> · OVR <?= $jovemRevelado['forca'] ?> · Pot <?= $jovemRevelado['potencial'] ?> · <?= $jovemRevelado['idade'] ?> anos</p>
            </div>
            <form method="POST" action="?page=app">
                <input type="hidden" name="action" value="contratar_revelacao">
                <button class="bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold px-4 py-2 rounded-lg transition">
                    <i class="fas fa-user-plus mr-1"></i>Contratar Grátis
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Partida pendente (match_setup na sessão) -->
    <?php if ($matchSetup): ?>
    <div class="bg-slate-900 border border-yellow-500/40 rounded-2xl p-5 mb-4">
        <p class="text-xs font-bold text-yellow-400 mb-3"><i class="fas fa-circle-exclamation mr-1.5"></i>Partida pendente — Rodada <?= (int)$matchSetup['rodada'] ?></p>
        <div class="flex items-center justify-between gap-3 mb-4">
            <div class="text-center flex-1">
                <p class="font-bold text-white text-sm"><?= htmlspecialchars($matchSetup['nome_casa']) ?></p>
                <?php if ((int)$matchSetup['meu_time_id'] === (int)$matchSetup['time_casa_id']): ?>
                <span class="text-[10px] text-green-400 font-semibold">VOCÊ</span>
                <?php endif; ?>
            </div>
            <div class="text-slate-600 font-bold">VS</div>
            <div class="text-center flex-1">
                <p class="font-bold text-white text-sm"><?= htmlspecialchars($matchSetup['nome_fora']) ?></p>
                <?php if ((int)$matchSetup['meu_time_id'] === (int)$matchSetup['time_fora_id']): ?>
                <span class="text-[10px] text-green-400 font-semibold">VOCÊ</span>
                <?php endif; ?>
            </div>
        </div>
        <button onclick="abrirViewer()" class="w-full bg-yellow-600 hover:bg-yellow-500 text-white font-bold py-2.5 rounded-xl text-sm transition">
            <i class="fas fa-play-circle mr-1.5"></i> Ver Partida
        </button>
    </div>
    <?php endif; ?>

    <!-- Próxima partida + Últimas partidas -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Próxima partida -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <h3 class="text-sm font-bold text-white mb-4"><i class="fas fa-calendar-alt text-green-400 mr-2"></i>Próxima Partida</h3>
            <?php if ($matchSetup): ?>
            <p class="text-slate-500 text-sm text-center py-4">Jogue a partida pendente antes de avançar.</p>
            <?php elseif ($proxima): ?>
            <div class="flex items-center justify-between gap-3">
                <div class="text-center flex-1">
                    <p class="text-xs text-slate-500 mb-1">Rodada <?= $proxima['rodada'] ?></p>
                    <p class="font-bold text-white text-sm"><?= htmlspecialchars($proxima['nome_casa']) ?></p>
                    <?php if ((int)$proxima['time_casa_id'] === $timeId): ?>
                    <span class="text-[10px] text-green-400 font-semibold">VOCÊ</span>
                    <?php endif; ?>
                </div>
                <div class="text-slate-600 font-bold">VS</div>
                <div class="text-center flex-1">
                    <p class="text-xs text-slate-500 mb-1">&nbsp;</p>
                    <p class="font-bold text-white text-sm"><?= htmlspecialchars($proxima['nome_fora']) ?></p>
                    <?php if ((int)$proxima['time_fora_id'] === $timeId): ?>
                    <span class="text-[10px] text-green-400 font-semibold">VOCÊ</span>
                    <?php endif; ?>
                </div>
            </div>
            <form method="POST" action="?page=app" class="mt-4">
                <input type="hidden" name="action" value="avancar_rodada">
                <button class="w-full bg-green-600 hover:bg-green-500 text-white font-bold py-2.5 rounded-xl text-sm transition">
                    <i class="fas fa-play mr-1.5"></i> Simular Rodada <?= $rodada ?>
                </button>
            </form>
            <?php else: ?>
            <p class="text-slate-500 text-sm text-center py-4">
                <?= $rodada > 38 ? 'Temporada encerrada!' : 'Nenhuma partida agendada.' ?>
            </p>
            <?php endif; ?>
        </div>

        <!-- Últimas 5 partidas do usuário -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <h3 class="text-sm font-bold text-white mb-4"><i class="fas fa-clock text-blue-400 mr-2"></i>Últimas Partidas</h3>
            <?php
            $ultimas5 = array_filter($partidas, fn($p) =>
                $p['status'] === 'jogada' &&
                ((int)$p['time_casa_id'] === $timeId || (int)$p['time_fora_id'] === $timeId)
            );
            $ultimas5 = array_slice(array_values(array_reverse($ultimas5)), 0, 5);
            if (!empty($ultimas5)): ?>
            <div class="space-y-2">
                <?php foreach ($ultimas5 as $p):
                    $ehCasa = (int)$p['time_casa_id'] === $timeId;
                    $meuG = $ehCasa ? $p['gols_casa'] : $p['gols_fora'];
                    $advG = $ehCasa ? $p['gols_fora'] : $p['gols_casa'];
                    $adv  = $ehCasa ? $p['nome_fora'] : $p['nome_casa'];
                    $res  = $meuG > $advG ? 'V' : ($meuG === $advG ? 'E' : 'D');
                    $resCor = $res === 'V' ? 'text-green-400' : ($res === 'E' ? 'text-yellow-400' : 'text-red-400');
                    $resBg  = $res === 'V' ? 'bg-green-500/10' : ($res === 'E' ? 'bg-yellow-500/10' : 'bg-red-500/10');
                ?>
                <div class="flex items-center gap-2 <?= $resBg ?> rounded-lg px-3 py-1.5">
                    <span class="text-xs font-black <?= $resCor ?> w-5 text-center"><?= $res ?></span>
                    <span class="text-xs text-slate-400 flex-1 truncate">vs <?= htmlspecialchars($adv) ?></span>
                    <span class="text-xs font-bold text-white"><?= $meuG ?>-<?= $advG ?></span>
                    <span class="text-[10px] text-slate-600">R<?= $p['rodada'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-slate-500 text-sm text-center py-4">Nenhuma partida jogada ainda.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- ABA: ELENCO — Escalação Interativa                                        -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<section id="tab-elenco" class="tab-content">

    <?php if (isset($msg) && $msg && isset($msg_tipo) && $msg_tipo === 'erro' && ($_POST['action'] ?? '') === 'salvar_escalacao'): ?>
    <div class="flex items-center gap-2 bg-red-500/10 border border-red-500/30 text-red-400 rounded-xl px-4 py-3 mb-4 text-sm">
        <i class="fas fa-exclamation-circle flex-shrink-0"></i> <?= htmlspecialchars($msg) ?>
    </div>
    <?php endif; ?>

    <!-- Barra: formação + tática + contadores + salvar -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 mb-4">
        <div class="flex flex-wrap items-center gap-2 mb-3">
            <span class="text-xs text-slate-500 font-semibold">Formação:</span>
            <div id="formacao-pills" class="flex flex-wrap gap-1.5"></div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs text-slate-500 font-semibold">Estilo:</span>
            <div id="tatica-pills" class="flex gap-1.5"></div>
            <div class="ml-auto flex items-center gap-3 flex-wrap">
                <span class="text-xs text-slate-500"><span id="cnt-tit" class="text-white font-bold">0</span><span class="text-slate-600">/11 tit</span></span>
                <span class="text-xs text-slate-500"><span id="cnt-banco" class="text-white font-bold">0</span><span class="text-slate-600">/6 banco</span></span>
                <form method="POST" action="?page=app" id="form-escalacao" class="inline">
                    <input type="hidden" name="action" value="salvar_escalacao">
                    <input type="hidden" name="formacao" id="input-formacao" value="">
                    <input type="hidden" name="tatica" id="input-tatica" value="<?= htmlspecialchars($taticaAtual) ?>">
                    <input type="hidden" name="titular_ids" id="input-tit-ids" value="">
                    <input type="hidden" name="banco_ids" id="input-banco-ids" value="">
                    <button type="submit" class="bg-green-600 hover:bg-green-500 text-white text-xs font-bold px-4 py-2 rounded-lg transition flex items-center gap-1.5">
                        <i class="fas fa-save"></i> Salvar Escalação
                    </button>
                </form>
            </div>
        </div>
        <p class="text-[10px] text-slate-600 mt-2"><i class="fas fa-info-circle mr-1"></i>Selecione um jogador da lista e clique num slot no campo para escalar. Clique em dois jogadores para trocar posições.</p>
    </div>

    <!-- Contratos expirando -->
    <?php $contratosExpirando = array_filter($elenco, fn($j) => (int)($j['meses_contrato'] ?? 12) <= 6); ?>
    <?php if (!empty($contratosExpirando)): ?>
    <div class="bg-slate-900 border border-orange-500/30 rounded-2xl p-4 mb-4">
        <h3 class="text-sm font-bold text-orange-400 mb-3"><i class="fas fa-clock mr-2"></i>Contratos Expirando</h3>
        <div class="space-y-2">
            <?php foreach ($contratosExpirando as $j):
                $custRen = (int)$j['salario'] * 3;
                $podRen  = $saldo >= $custRen;
                $mesCor  = (int)$j['meses_contrato'] <= 2 ? 'text-red-400' : 'text-orange-400';
            ?>
            <div class="flex items-center gap-3 p-2.5 bg-slate-800/50 rounded-xl">
                <div class="flex-1 min-w-0">
                    <span class="text-sm text-white font-medium"><?= htmlspecialchars($j['nome']) ?></span>
                    <span class="text-xs <?= $mesCor ?> ml-2"><?= (int)$j['meses_contrato'] <= 0 ? 'Expirando!' : $j['meses_contrato'].' meses' ?></span>
                    <span class="text-xs text-slate-500 ml-1">· <?= posLabel($j['posicao']) ?> OVR <?= $j['forca'] ?></span>
                </div>
                <form method="POST" action="?page=app" class="inline flex-shrink-0">
                    <input type="hidden" name="action" value="renovar_contrato">
                    <input type="hidden" name="elenco_id" value="<?= $j['id'] ?>">
                    <button <?= $podRen ? '' : 'disabled' ?>
                        class="text-xs font-bold px-3 py-1.5 rounded-lg transition <?= $podRen ? 'bg-orange-600 hover:bg-orange-500 text-white' : 'bg-slate-700 text-slate-500 cursor-not-allowed' ?>">
                        Renovar (<?= appSaldo($custRen) ?>)
                    </button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Campo + listas -->
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">

        <!-- Campo tático -->
        <div class="lg:col-span-3 bg-slate-900 border border-slate-800 rounded-2xl p-3">
            <div id="campo-wrapper" class="pitch-pattern rounded-xl relative overflow-hidden" style="height:440px; cursor:default">
                <!-- Linhas decorativas do campo -->
                <div class="absolute inset-x-0 top-1/2 h-px bg-white/10"></div>
                <div class="absolute w-14 h-14 rounded-full border border-white/10 pointer-events-none" style="top:calc(50% - 1.75rem);left:calc(50% - 1.75rem)"></div>
                <div class="absolute pointer-events-none" style="left:15%;width:70%;top:3%;height:19%;border:1px solid rgba(255,255,255,.08)"></div>
                <div class="absolute pointer-events-none" style="left:15%;width:70%;bottom:3%;height:19%;border:1px solid rgba(255,255,255,.08)"></div>
                <div class="absolute pointer-events-none" style="left:31%;width:38%;top:3%;height:10%;border:1px solid rgba(255,255,255,.05)"></div>
                <div class="absolute pointer-events-none" style="left:31%;width:38%;bottom:3%;height:10%;border:1px solid rgba(255,255,255,.05)"></div>
                <!-- Slots renderizados pelo JS -->
            </div>
        </div>

        <!-- Listas de jogadores -->
        <div class="lg:col-span-2 flex flex-col gap-3" style="max-height:440px;overflow-y:auto">

            <!-- Titulares -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden flex-shrink-0">
                <div class="px-4 py-2.5 border-b border-slate-800 flex items-center justify-between" style="background:rgba(34,197,94,.05)">
                    <span class="text-xs font-bold text-green-400"><i class="fas fa-star mr-1.5"></i>Titulares</span>
                    <span class="text-xs text-slate-500" id="tit-count">0/11</span>
                </div>
                <div id="lista-titulares" class="overflow-y-auto" style="max-height:185px"></div>
            </div>

            <!-- Banco -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden flex-shrink-0">
                <div class="px-4 py-2.5 border-b border-slate-800 flex items-center justify-between" style="background:rgba(59,130,246,.05)">
                    <span class="text-xs font-bold text-blue-400"><i class="fas fa-chair mr-1.5"></i>Banco de Reservas</span>
                    <span class="text-xs text-slate-500" id="banco-count">0/6</span>
                </div>
                <div id="lista-banco" class="overflow-y-auto" style="max-height:148px"></div>
            </div>

            <!-- Não Relacionados -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden flex-shrink-0">
                <div class="px-4 py-2.5 border-b border-slate-800 flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-400"><i class="fas fa-user-slash mr-1.5"></i>Não Relacionados</span>
                    <span class="text-xs text-slate-500" id="nr-count">0</span>
                </div>
                <div id="lista-nr" class="overflow-y-auto" style="max-height:185px"></div>
            </div>

        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- ABA: CAMPEONATO                                                           -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<section id="tab-campeonato" class="tab-content">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        <!-- Tabela de classificação -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
            <div class="p-4 border-b border-slate-800">
                <h3 class="text-sm font-bold text-white"><i class="fas fa-trophy text-yellow-400 mr-2"></i>Série A — Classificação</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="bg-slate-800">
                        <tr class="text-slate-400">
                            <th class="py-2 pl-3 pr-1 text-left">#</th>
                            <th class="py-2 px-2 text-left">Time</th>
                            <th class="py-2 px-1 text-center">J</th>
                            <th class="py-2 px-1 text-center">V</th>
                            <th class="py-2 px-1 text-center">E</th>
                            <th class="py-2 px-1 text-center">D</th>
                            <th class="py-2 px-1 text-center">GP</th>
                            <th class="py-2 px-1 text-center">GC</th>
                            <th class="py-2 px-1 text-center">SG</th>
                            <th class="py-2 px-2 text-center font-bold text-white">Pts</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        <?php foreach ($classificacao as $i => $c):
                            $meu  = (int)$c['time_id'] === $timeId;
                            $zona = $i < 6 ? 'bg-blue-500/5' : ($i >= 16 ? 'bg-red-500/5' : '');
                            $sg   = $c['gols_pro'] - $c['gols_contra'];
                        ?>
                        <tr class="hover:bg-slate-800/50 <?= $zona ?> <?= $meu ? 'border-l-2 border-green-500' : '' ?>">
                            <td class="py-2 pl-3 pr-1 font-bold <?= $i < 4 ? 'text-green-400' : ($i < 6 ? 'text-blue-400' : ($i >= 16 ? 'text-red-400' : 'text-slate-400')) ?>">
                                <?= $i+1 ?>
                            </td>
                            <td class="py-2 px-2 font-semibold <?= $meu ? 'text-green-400' : 'text-white' ?> truncate max-w-[100px]">
                                <?= htmlspecialchars($c['nome'] ?? 'Time') ?>
                            </td>
                            <td class="py-2 px-1 text-center text-slate-400"><?= $c['jogos'] ?></td>
                            <td class="py-2 px-1 text-center text-green-400"><?= $c['vitorias'] ?></td>
                            <td class="py-2 px-1 text-center text-yellow-400"><?= $c['empates'] ?></td>
                            <td class="py-2 px-1 text-center text-red-400"><?= $c['derrotas'] ?></td>
                            <td class="py-2 px-1 text-center text-slate-400"><?= $c['gols_pro'] ?></td>
                            <td class="py-2 px-1 text-center text-slate-400"><?= $c['gols_contra'] ?></td>
                            <td class="py-2 px-1 text-center <?= $sg >= 0 ? 'text-slate-300' : 'text-red-400' ?>"><?= ($sg>0?'+':'').$sg ?></td>
                            <td class="py-2 px-2 text-center font-black text-white"><?= $c['pontos'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-3 border-t border-slate-800 flex gap-4 text-[10px] text-slate-500">
                <span><span class="text-green-400 font-bold">Verde</span> = Libertadores</span>
                <span><span class="text-blue-400 font-bold">Azul</span> = Sul-Americana</span>
                <span><span class="text-red-400 font-bold">Vermelho</span> = Rebaixamento</span>
            </div>
        </div>

        <!-- Artilharia -->
        <?php if (!empty($artilheiros)): ?>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
            <div class="p-4 border-b border-slate-800">
                <h3 class="text-sm font-bold text-white"><i class="fas fa-futbol text-green-400 mr-2"></i>Artilharia — Seu Elenco</h3>
            </div>
            <div class="divide-y divide-slate-800">
                <?php foreach ($artilheiros as $i => $art): ?>
                <div class="px-4 py-2.5 flex items-center gap-3">
                    <span class="text-xs font-bold text-slate-500 w-5">#{<?= $i+1 ?>}</span>
                    <span class="text-xs font-semibold text-white flex-1"><?= htmlspecialchars($art['nome']) ?></span>
                    <span class="text-[10px] text-slate-500"><?= $art['posicao'] ?></span>
                    <?php if ($art['gols'] > 0): ?><span class="text-xs font-bold text-green-400">⚽ <?= $art['gols'] ?></span><?php endif; ?>
                    <?php if ($art['assists'] > 0): ?><span class="text-xs font-bold text-blue-400 ml-1">🅰 <?= $art['assists'] ?></span><?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Jogos da rodada atual -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
            <div class="p-4 border-b border-slate-800 flex items-center justify-between">
                <h3 class="text-sm font-bold text-white"><i class="fas fa-calendar-alt text-green-400 mr-2"></i>Rodada <?= $rodada ?></h3>
                <span class="text-xs text-slate-500">de 38</span>
            </div>
            <div class="divide-y divide-slate-800 overflow-y-auto" style="max-height:400px">
                <?php
                $jogosRodada = array_filter($partidas, fn($p) => (int)$p['rodada'] === $rodada);
                $jogosAnt    = array_filter($partidas, fn($p) => (int)$p['rodada'] === $rodada - 1 && $p['status'] === 'jogada');
                $exibir      = !empty($jogosAnt) ? $jogosAnt : $jogosRodada;
                foreach ($exibir as $p):
                    $minha = (int)$p['time_casa_id'] === $timeId || (int)$p['time_fora_id'] === $timeId;
                ?>
                <div class="px-4 py-3 flex items-center justify-between gap-2 <?= $minha ? 'bg-green-500/5' : '' ?>">
                    <span class="text-xs <?= $minha ? 'text-white font-bold' : 'text-slate-300' ?> text-right flex-1 truncate"><?= htmlspecialchars($p['nome_casa'] ?? '-') ?></span>
                    <div class="text-center flex-shrink-0 min-w-[60px]">
                        <?php if ($p['status'] === 'jogada'): ?>
                        <span class="font-black text-white text-base"><?= $p['gols_casa'] ?> <span class="text-slate-500">-</span> <?= $p['gols_fora'] ?></span>
                        <?php else: ?>
                        <span class="text-slate-600 text-xs">vs</span>
                        <?php endif; ?>
                    </div>
                    <span class="text-xs <?= $minha ? 'text-white font-bold' : 'text-slate-300' ?> text-left flex-1 truncate"><?= htmlspecialchars($p['nome_fora'] ?? '-') ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Copa Nacional -->
    <?php if (!empty($copa)): ?>
    <?php
    $copaFases = ['quartas' => 'Quartas de Final', 'semifinal' => 'Semifinal', 'final' => 'Final'];
    $copaAgrupado = [];
    foreach ($copa as $c) { $copaAgrupado[$c['fase']][] = $c; }
    ?>
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden mt-4">
        <div class="p-4 border-b border-slate-800">
            <h3 class="text-sm font-bold text-white"><i class="fas fa-shield-alt text-yellow-400 mr-2"></i>Copa Nacional</h3>
        </div>
        <div class="p-4 space-y-4">
            <?php foreach (['quartas','semifinal','final'] as $fase):
                if (empty($copaAgrupado[$fase])) continue; ?>
            <div>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2"><?= $copaFases[$fase] ?></p>
                <div class="space-y-1.5">
                    <?php foreach ($copaAgrupado[$fase] as $cm):
                        $minha = ((int)$cm['time_casa_id'] === $timeId || (int)$cm['time_fora_id'] === $timeId);
                        $jogada = $cm['status'] === 'jogada';
                    ?>
                    <div class="flex items-center gap-2 px-3 py-2 rounded-lg <?= $minha ? 'bg-green-500/8 border border-green-500/20' : 'bg-slate-800/40' ?>">
                        <span class="text-xs <?= $minha && (int)$cm['time_casa_id'] === $timeId ? 'text-green-400 font-bold' : 'text-slate-300' ?> text-right flex-1 truncate"><?= htmlspecialchars($cm['nome_casa']) ?></span>
                        <span class="text-center flex-shrink-0 min-w-[56px] text-center">
                            <?php if ($jogada): ?>
                            <span class="font-black text-white text-sm"><?= $cm['gols_casa'] ?>–<?= $cm['gols_fora'] ?></span>
                            <?php else: ?>
                            <span class="text-slate-600 text-xs">vs</span>
                            <?php endif; ?>
                        </span>
                        <span class="text-xs <?= $minha && (int)$cm['time_fora_id'] === $timeId ? 'text-green-400 font-bold' : 'text-slate-300' ?> text-left flex-1 truncate"><?= htmlspecialchars($cm['nome_fora']) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</section>

<?php /* tab-jogar removida — funcionalidade integrada ao dashboard */ ?>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- MODAL: MATCH VIEWER                                                        -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div id="match-viewer" class="fixed inset-0 z-[200] hidden flex-col bg-slate-950 overflow-hidden">

    <!-- Header: placar + relogio -->
    <div class="flex-shrink-0 bg-slate-900 border-b border-slate-800 px-4 py-3">
        <div class="max-w-4xl mx-auto flex items-center gap-3">
            <!-- Time Casa -->
            <div class="flex-1 text-right">
                <p id="mv-nome-casa" class="text-sm font-bold text-white truncate"></p>
            </div>
            <!-- Placar + Relogio -->
            <div class="flex-shrink-0 text-center min-w-[120px]">
                <div class="flex items-center justify-center gap-2">
                    <span id="mv-gols-casa" class="text-3xl font-black text-white">0</span>
                    <span class="text-slate-600 text-xl font-bold">–</span>
                    <span id="mv-gols-fora" class="text-3xl font-black text-white">0</span>
                </div>
                <div class="flex items-center justify-center gap-1.5 mt-1">
                    <div id="mv-status-dot" class="w-2 h-2 rounded-full bg-green-400" style="animation:pulse 1s infinite"></div>
                    <span id="mv-relogio" class="text-xs font-bold text-green-400">0'</span>
                </div>
            </div>
            <!-- Time Fora -->
            <div class="flex-1 text-left">
                <p id="mv-nome-fora" class="text-sm font-bold text-white truncate"></p>
            </div>
            <!-- Fechar (só aparece depois do fim de jogo) -->
            <button id="mv-close-btn" onclick="fecharViewer()" class="ml-2 text-slate-500 hover:text-white transition flex-shrink-0" style="display:none">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
    </div>

    <!-- Corpo -->
    <div class="flex-1 overflow-hidden max-w-4xl mx-auto w-full flex gap-0 md:gap-4 p-3 md:p-4 min-h-0">

        <!-- Eventos (esquerda) -->
        <div class="flex-1 flex flex-col min-w-0">
            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Lance a lance</p>
            <div id="mv-eventos" class="flex-1 overflow-y-auto space-y-1 min-h-0 pb-2"></div>
        </div>

        <!-- Notas dos jogadores (direita) -->
        <div class="w-48 md:w-56 flex-shrink-0 flex flex-col ml-3">
            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Notas</p>
            <div id="mv-notas" class="flex-1 overflow-y-auto min-h-0 space-y-0.5 pb-2"></div>
        </div>
    </div>

    <!-- Controles -->
    <div class="flex-shrink-0 bg-slate-900 border-t border-slate-800 px-4 py-3">
        <div class="max-w-4xl mx-auto flex flex-col gap-2">
            <!-- Linha 1: Play / Subs / Velocidade / Progresso -->
            <div class="flex items-center gap-2 flex-wrap">
                <button id="mv-btn-play" onclick="mvTogglePlay()" class="bg-blue-700 hover:bg-blue-600 text-white text-xs font-bold px-4 py-2 rounded-lg transition flex items-center gap-1.5">
                    <i id="mv-play-icon" class="fas fa-pause"></i> <span id="mv-play-label">Pausar</span>
                </button>
                <button id="mv-btn-subs" onclick="mvToggleSubs()" class="bg-slate-700 hover:bg-slate-600 text-white text-xs font-bold px-4 py-2 rounded-lg transition flex items-center gap-1.5">
                    <i class="fas fa-exchange-alt"></i> <span id="mv-subs-label">Subs 0/5</span>
                </button>
                <div class="flex items-center gap-1.5">
                    <span class="text-xs text-slate-500">Vel:</span>
                    <?php foreach ([1,2,4] as $spd): ?>
                    <button onclick="mvSetSpeed(<?= $spd ?>)" data-speed="<?= $spd ?>" class="mv-speed-btn text-xs px-2 py-1 rounded-lg transition <?= $spd===1?'bg-slate-600 text-white':'bg-slate-800 text-slate-400 hover:bg-slate-700' ?>"><?= $spd ?>×</button>
                    <?php endforeach; ?>
                    <button onclick="mvInstant()" class="text-xs px-2 py-1 rounded-lg bg-slate-800 text-slate-400 hover:bg-slate-700 transition">Fim</button>
                </div>
                <div class="ml-auto">
                    <div class="w-24 h-1.5 bg-slate-800 rounded-full overflow-hidden">
                        <div id="mv-progress-fill" class="h-full bg-green-500 rounded-full transition-all" style="width:0%"></div>
                    </div>
                </div>
            </div>
            <!-- Linha 2: Tática -->
            <div class="flex items-center gap-2">
                <span class="text-xs text-slate-500">Tática:</span>
                <button id="mv-tat-btn-defensivo" onclick="mvSetTatica('defensivo')" style="font-size:11px;font-weight:700;padding:4px 10px;border-radius:8px;border:1px solid #334155;background:#1e293b;color:#94a3b8;cursor:pointer;transition:all .2s">Defensivo</button>
                <button id="mv-tat-btn-normal"    onclick="mvSetTatica('normal')"    style="font-size:11px;font-weight:700;padding:4px 10px;border-radius:8px;border:1px solid #334155;background:#1e293b;color:#94a3b8;cursor:pointer;transition:all .2s">Normal</button>
                <button id="mv-tat-btn-ataque"    onclick="mvSetTatica('ataque')"    style="font-size:11px;font-weight:700;padding:4px 10px;border-radius:8px;border:1px solid #334155;background:#1e293b;color:#94a3b8;cursor:pointer;transition:all .2s">Ataque</button>
                <span class="text-[10px] text-slate-600 ml-1">(<span id="mv-tat-label">—</span>)</span>
            </div>
        </div>
    </div>

    <!-- Painel de substituições (overlay dentro do viewer) -->
    <div id="mv-sub-panel" class="hidden absolute inset-0 z-10 flex flex-col p-4" style="position:absolute;background:#020617">
        <div class="max-w-lg mx-auto w-full flex flex-col flex-1 min-h-0">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-white font-bold text-sm"><i class="fas fa-exchange-alt text-yellow-400 mr-2"></i>Substituição (<span id="mv-sub-count">0</span>/5 feitas)</h3>
                <button onclick="mvToggleSubs()" class="text-slate-400 hover:text-white transition"><i class="fas fa-times"></i></button>
            </div>
            <div class="flex gap-3 flex-1 overflow-hidden">
                <div class="flex-1 flex flex-col min-h-0">
                    <p class="text-xs text-red-400 font-bold uppercase mb-2">Sai (Campo)</p>
                    <div id="mv-sub-list-out" class="flex-1 overflow-y-auto space-y-1 min-h-0"></div>
                </div>
                <div class="w-px bg-slate-800 flex-shrink-0 self-stretch"></div>
                <div class="flex-1 flex flex-col min-h-0">
                    <p class="text-xs text-green-400 font-bold uppercase mb-2">Entra (Banco)</p>
                    <div id="mv-sub-list-in" class="flex-1 overflow-y-auto space-y-1 min-h-0"></div>
                </div>
            </div>
            <button id="mv-sub-confirm" onclick="mvConfirmSub()" disabled class="mt-4 w-full bg-green-700 hover:bg-green-600 disabled:bg-slate-800 disabled:text-slate-600 text-white font-bold py-3 rounded-xl transition text-sm">
                <i class="fas fa-check mr-2"></i>Confirmar Substituição
            </button>
        </div>
    </div>

    <!-- Popup fim de jogo -->
    <div id="mv-end-popup" class="hidden absolute inset-0 bg-slate-950/97 z-20 overflow-y-auto" style="position:absolute">
        <div class="min-h-full flex items-center justify-center p-4">
            <div class="bg-slate-900 border border-slate-700 rounded-2xl p-6 w-full max-w-lg">
                <p class="text-center text-xs text-slate-500 mb-1">FIM DE JOGO</p>
                <p id="mv-end-rodada" class="text-center text-xs text-slate-600 mb-5"></p>
                <div class="flex items-center justify-center gap-4 mb-2">
                    <div class="text-center flex-1"><p id="mv-end-nome-casa" class="text-sm font-bold text-white"></p></div>
                    <div class="text-center flex-shrink-0">
                        <p id="mv-end-score" class="text-4xl font-black"></p>
                    </div>
                    <div class="text-center flex-1"><p id="mv-end-nome-fora" class="text-sm font-bold text-white"></p></div>
                </div>
                <div class="mb-4 border-t border-slate-800 pt-4">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                        <div>
                            <p id="mv-end-nome-casa-col" class="text-[10px] font-bold text-slate-500 uppercase mb-1 truncate"></p>
                            <div id="mv-end-eventos-casa"></div>
                        </div>
                        <div>
                            <p id="mv-end-nome-fora-col" class="text-[10px] font-bold text-slate-500 uppercase mb-1 truncate text-right"></p>
                            <div id="mv-end-eventos-fora"></div>
                        </div>
                    </div>
                </div>
                <div id="mv-end-notas" class="mb-6 border-t border-slate-800 pt-4"></div>
                <button onclick="mvSubmitResult()" class="w-full bg-green-600 hover:bg-green-500 text-white font-bold py-3 rounded-xl transition">
                    <i class="fas fa-home mr-2"></i>Voltar ao Início
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- ABA: ESTATÍSTICAS                                                         -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<section id="tab-estatisticas" class="tab-content">
    <?php
    // Separa goleiros, defensores, meias e atacantes para ordenação por posição + OVR
    $posOrdem = ['GOL'=>0,'ZAG'=>1,'LD'=>2,'LE'=>3,'VOL'=>4,'MC'=>5,'MEI'=>6,'PE'=>7,'PD'=>8,'ATA'=>9];
    $elencoStats = $elenco;
    usort($elencoStats, function($a, $b) use ($posOrdem) {
        $pa = $posOrdem[$a['posicao']] ?? 99;
        $pb = $posOrdem[$b['posicao']] ?? 99;
        if ($pa !== $pb) return $pa - $pb;
        return (int)$b['forca'] - (int)$a['forca'];
    });
    $totalPartidas = max(1, array_sum(array_column($elencoStats, 'gols')) > 0
        ? (int)(($elencoStats[0]['partidas'] ?? 0) > 0 ? $elencoStats[0]['partidas'] : 0) : 0);
    ?>
    <!-- Cards resumo -->
    <?php
    $totGols    = array_sum(array_column($elencoStats, 'gols'));
    $totAssists = array_sum(array_column($elencoStats, 'assists'));
    $totAmar    = array_sum(array_column($elencoStats, 'amarelos'));
    $totVerm    = array_sum(array_column($elencoStats, 'vermelhos'));
    $jogadoresComPartida = array_filter($elencoStats, fn($j) => ($j['partidas'] ?? 0) > 0);
    $melhorNotaRow  = !empty($jogadoresComPartida) ? array_reduce($jogadoresComPartida, fn($carry, $j) => (!$carry || ($j['nota_total'] / $j['partidas']) > ($carry['nota_total'] / $carry['partidas'])) ? $j : $carry) : null;
    $artilheiro = !empty($elencoStats) ? array_reduce($elencoStats, fn($c, $j) => (!$c || (int)$j['gols'] > (int)$c['gols']) ? $j : $c) : null;
    ?>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 text-center">
            <p class="text-xs text-slate-500 mb-1"><i class="fas fa-futbol text-green-400 mr-1"></i>Gols</p>
            <p class="text-3xl font-black text-green-400"><?= $totGols ?></p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 text-center">
            <p class="text-xs text-slate-500 mb-1"><i class="fas fa-hands-helping text-blue-400 mr-1"></i>Assistências</p>
            <p class="text-3xl font-black text-blue-400"><?= $totAssists ?></p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 text-center">
            <p class="text-xs text-slate-500 mb-1">Artilheiro</p>
            <p class="text-sm font-bold text-white"><?= $artilheiro && $artilheiro['gols'] > 0 ? htmlspecialchars($artilheiro['nome']) : '—' ?></p>
            <?php if ($artilheiro && $artilheiro['gols'] > 0): ?>
            <p class="text-2xl font-black text-green-400"><?= $artilheiro['gols'] ?> <span class="text-sm font-normal text-slate-500">gols</span></p>
            <?php endif; ?>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 text-center">
            <p class="text-xs text-slate-500 mb-1">Melhor Nota</p>
            <?php if ($melhorNotaRow): ?>
            <p class="text-sm font-bold text-white"><?= htmlspecialchars($melhorNotaRow['nome']) ?></p>
            <p class="text-2xl font-black text-yellow-400"><?= number_format($melhorNotaRow['nota_total'] / $melhorNotaRow['partidas'], 1) ?></p>
            <?php else: ?>
            <p class="text-2xl font-black text-slate-600">—</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tabela principal -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
        <div class="p-4 border-b border-slate-800 flex items-center justify-between">
            <h3 class="text-sm font-bold text-white"><i class="fas fa-chart-bar text-blue-400 mr-2"></i>Estatísticas do Elenco</h3>
            <span class="text-xs text-slate-500"><?= count($elencoStats) ?> jogadores</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-800 sticky top-0">
                    <tr class="text-slate-400 text-center">
                        <th class="py-2.5 px-3 text-left">Jogador</th>
                        <th class="py-2.5 px-2">Pos</th>
                        <th class="py-2.5 px-2">OVR</th>
                        <th class="py-2.5 px-2" title="Partidas"><i class="fas fa-calendar-alt"></i></th>
                        <th class="py-2.5 px-2" title="Gols"><i class="fas fa-futbol"></i></th>
                        <th class="py-2.5 px-2" title="Assistências">🅰</th>
                        <th class="py-2.5 px-2" title="Amarelos"><span style="display:inline-block;width:8px;height:11px;background:#facc15;border-radius:1px;vertical-align:middle"></span></th>
                        <th class="py-2.5 px-2" title="Vermelhos"><span style="display:inline-block;width:8px;height:11px;background:#ef4444;border-radius:1px;vertical-align:middle"></span></th>
                        <th class="py-2.5 px-3" title="Nota Média"><i class="fas fa-star text-yellow-400"></i></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    <?php foreach ($elencoStats as $j):
                        $partidas   = (int)($j['partidas']   ?? 0);
                        $gols       = (int)($j['gols']       ?? 0);
                        $assists    = (int)($j['assists']     ?? 0);
                        $amarelos   = (int)($j['amarelos']    ?? 0);
                        $vermelhos  = (int)($j['vermelhos']   ?? 0);
                        $notaTotal  = (float)($j['nota_total'] ?? 0);
                        $notaMedia  = $partidas > 0 ? $notaTotal / $partidas : null;
                        $pCor       = posColor($j['posicao']);
                        $notaCor    = $notaMedia === null ? '#475569' : ($notaMedia >= 7.5 ? '#4ade80' : ($notaMedia >= 6.5 ? '#facc15' : ($notaMedia >= 5.5 ? '#fb923c' : '#f87171')));
                    ?>
                    <tr class="hover:bg-slate-800/30 transition-colors">
                        <td class="py-2.5 px-3">
                            <div class="flex items-center gap-2">
                                <span class="text-slate-300 font-medium"><?= htmlspecialchars($j['nome']) ?></span>
                                <?php if ($j['titular'] ?? 0): ?><span class="text-[9px] text-green-500 font-bold">TIT</span><?php endif; ?>
                                <?php if ($j['contundido'] ?? 0): ?><i class="fas fa-band-aid text-red-400 text-[9px]" title="Contundido"></i><?php endif; ?>
                            </div>
                        </td>
                        <td class="py-2.5 px-2 text-center">
                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded" style="background:<?= $pCor ?>22;color:<?= $pCor ?>"><?= $j['posicao'] ?></span>
                        </td>
                        <td class="py-2.5 px-2 text-center font-bold <?= (int)$j['forca'] >= 80 ? 'text-green-400' : ((int)$j['forca'] >= 70 ? 'text-yellow-400' : 'text-slate-300') ?>"><?= $j['forca'] ?></td>
                        <td class="py-2.5 px-2 text-center text-slate-400"><?= $partidas ?: '—' ?></td>
                        <td class="py-2.5 px-2 text-center font-bold <?= $gols > 0 ? 'text-green-400' : 'text-slate-600' ?>"><?= $gols ?: '—' ?></td>
                        <td class="py-2.5 px-2 text-center font-bold <?= $assists > 0 ? 'text-blue-400' : 'text-slate-600' ?>"><?= $assists ?: '—' ?></td>
                        <td class="py-2.5 px-2 text-center font-bold <?= $amarelos > 0 ? 'text-yellow-400' : 'text-slate-600' ?>"><?= $amarelos ?: '—' ?></td>
                        <td class="py-2.5 px-2 text-center font-bold <?= $vermelhos > 0 ? 'text-red-400' : 'text-slate-600' ?>"><?= $vermelhos ?: '—' ?></td>
                        <td class="py-2.5 px-3 text-center font-black text-sm" style="color:<?= $notaCor ?>"><?= $notaMedia !== null ? number_format($notaMedia, 1) : '—' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if (empty($jogadoresComPartida)): ?>
    <div class="text-center py-10 text-slate-500">
        <i class="fas fa-chart-bar text-4xl mb-3 block"></i>
        <p class="font-medium">Nenhuma partida simulada ainda</p>
        <p class="text-xs mt-1">As estatísticas aparecerão após a primeira rodada jogada</p>
    </div>
    <?php endif; ?>
</section>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- ABA: FINANÇAS                                                             -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<section id="tab-financas" class="tab-content">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-5">
        <?php
        $entradas = array_sum(array_map(fn($f) => max(0, $f['valor']), $financas));
        $saidas   = array_sum(array_map(fn($f) => abs(min(0, $f['valor'])), $financas));
        $saldoFin = $entradas - $saidas;
        ?>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="text-xs text-slate-500 mb-1"><i class="fas fa-arrow-up text-green-400 mr-1"></i>Total Entradas</p>
            <p class="text-2xl font-black text-green-400"><?= appSaldo($entradas) ?></p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="text-xs text-slate-500 mb-1"><i class="fas fa-arrow-down text-red-400 mr-1"></i>Total Saídas</p>
            <p class="text-2xl font-black text-red-400"><?= appSaldo($saidas) ?></p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="text-xs text-slate-500 mb-1"><i class="fas fa-wallet text-yellow-400 mr-1"></i>Saldo Atual</p>
            <p class="text-2xl font-black <?= $saldo >= 0 ? 'text-green-400' : 'text-red-400' ?>"><?= appSaldo($saldo) ?></p>
        </div>
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
        <div class="p-4 border-b border-slate-800">
            <h3 class="text-sm font-bold text-white"><i class="fas fa-receipt text-yellow-400 mr-2"></i>Histórico Financeiro</h3>
        </div>
        <div class="overflow-y-auto" style="max-height:400px">
            <table class="w-full text-xs">
                <thead class="sticky top-0 bg-slate-800">
                    <tr class="text-slate-400">
                        <th class="py-2 px-4 text-left">Descrição</th>
                        <th class="py-2 px-3 text-left">Categoria</th>
                        <th class="py-2 px-4 text-right">Valor</th>
                        <th class="py-2 px-3 text-right">Data</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    <?php foreach (array_reverse($financas) as $f): ?>
                    <tr class="hover:bg-slate-800/40">
                        <td class="py-2.5 px-4 text-slate-300"><?= htmlspecialchars($f['descricao']) ?></td>
                        <td class="py-2.5 px-3">
                            <?php
                            $catCor = ['patrocinio'=>'text-blue-400','bilheteria'=>'text-green-400','salarios'=>'text-red-400','transferencia'=>'text-yellow-400','premio'=>'text-yellow-400'];
                            $cc = $catCor[$f['categoria']] ?? 'text-slate-400';
                            ?>
                            <span class="<?= $cc ?>"><?= htmlspecialchars($f['categoria']) ?></span>
                        </td>
                        <td class="py-2.5 px-4 text-right font-bold <?= $f['valor'] >= 0 ? 'text-green-400' : 'text-red-400' ?>">
                            <?= ($f['valor'] >= 0 ? '+' : '') . appSaldo((int)$f['valor']) ?>
                        </td>
                        <td class="py-2.5 px-3 text-right text-slate-600"><?= date('d/m', strtotime($f['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- ABA: MERCADO                                                              -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<section id="tab-mercado" class="tab-content">
    <?php
    $janelaAberta = ($rodada >= 1 && $rodada <= 5) || ($rodada >= 19 && $rodada <= 22);
    ?>
    <!-- Janela de transferências -->
    <div class="flex items-center gap-3 mb-4 p-3 rounded-xl <?= $janelaAberta ? 'bg-green-500/10 border border-green-500/30' : 'bg-slate-800/50 border border-slate-700' ?>">
        <i class="fas fa-door-<?= $janelaAberta ? 'open text-green-400' : 'closed text-slate-500' ?>"></i>
        <div>
            <p class="text-xs font-bold <?= $janelaAberta ? 'text-green-400' : 'text-slate-400' ?>">
                Janela de Transferências: <?= $janelaAberta ? 'ABERTA' : 'FECHADA' ?>
            </p>
            <p class="text-[10px] text-slate-500">Contratações permitidas nas rodadas 1–5 e 19–22</p>
        </div>
        <span class="ml-auto text-xs text-slate-500">Rodada <?= $rodada ?></span>
    </div>

    <!-- Jovem Revelado no mercado -->
    <?php if ($jovemRevelado): ?>
    <div class="bg-slate-900 border border-purple-500/30 rounded-2xl p-4 mb-4">
        <h3 class="text-sm font-bold text-purple-400 mb-3"><i class="fas fa-star mr-2"></i>Revelação da Base</h3>
        <div class="flex items-center gap-4">
            <div class="flex-1">
                <p class="font-bold text-white"><?= htmlspecialchars($jovemRevelado['nome']) ?></p>
                <p class="text-xs text-slate-400 mt-0.5">
                    <?= $jovemRevelado['posicao'] ?> · OVR <span class="text-white font-bold"><?= $jovemRevelado['forca'] ?></span>
                    · Potencial <span class="text-purple-400 font-bold"><?= $jovemRevelado['potencial'] ?></span>
                    · <?= $jovemRevelado['idade'] ?> anos
                    · Salário <?= appSaldo($jovemRevelado['salario']) ?>/mês
                </p>
            </div>
            <form method="POST" action="?page=app">
                <input type="hidden" name="action" value="contratar_revelacao">
                <button class="bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition">
                    <i class="fas fa-user-plus mr-1"></i>Contratar Grátis
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        <!-- Jogadores disponíveis para compra -->
        <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
            <div class="p-4 border-b border-slate-800 flex items-center justify-between">
                <h3 class="text-sm font-bold text-white"><i class="fas fa-store text-yellow-400 mr-2"></i>Jogadores Disponíveis</h3>
                <span class="text-xs <?= $janelaAberta ? 'text-green-400' : 'text-orange-400' ?> text-xs"><?= $janelaAberta ? count($mercado).' no mercado' : 'Janela fechada' ?></span>
            </div>
            <div class="overflow-y-auto" style="max-height:480px">
                <table class="w-full text-xs">
                    <thead class="sticky top-0 bg-slate-800">
                        <tr class="text-slate-400">
                            <th class="py-2 px-3 text-left">Jogador</th>
                            <th class="py-2 px-2 text-center">Pos</th>
                            <th class="py-2 px-2 text-center">OVR</th>
                            <th class="py-2 px-2 text-center">Idade</th>
                            <th class="py-2 px-3 text-right">Valor</th>
                            <th class="py-2 px-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        <?php foreach ($mercado as $j):
                            $preco   = valorMercado((int)$j['forca'], (int)$j['idade']);
                            $pCor    = posColor($j['posicao']);
                            $podeCom = $saldo >= $preco;
                            // Idade como indicador de potencial
                            $ageBadge = $j['idade'] <= 21 ? '<span class="text-[9px] text-purple-400 font-bold">JOVEN</span>' : ($j['idade'] >= 32 ? '<span class="text-[9px] text-slate-500">VET</span>' : '');
                        ?>
                        <tr class="hover:bg-slate-800/50">
                            <td class="py-2.5 px-3 font-medium text-white">
                                <?= htmlspecialchars($j['nome']) ?>
                                <?= $ageBadge ?>
                            </td>
                            <td class="py-2 px-2 text-center">
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded" style="color:<?= $pCor ?>;background:<?= $pCor ?>22"><?= posLabel($j['posicao']) ?></span>
                            </td>
                            <td class="py-2 px-2 text-center font-bold text-white"><?= $j['forca'] ?></td>
                            <td class="py-2 px-2 text-center text-slate-400"><?= $j['idade'] ?></td>
                            <td class="py-2 px-3 text-right text-yellow-400 font-semibold"><?= appSaldo($preco) ?></td>
                            <td class="py-2 px-3 text-right">
                                <form method="POST" action="?page=app" class="inline">
                                    <input type="hidden" name="action" value="comprar_jogador">
                                    <input type="hidden" name="jogador_base_id" value="<?= $j['id'] ?>">
                                    <button <?= $podeCom ? '' : 'disabled' ?>
                                        class="px-2 py-1 rounded-lg text-[10px] font-bold transition <?= $podeCom ? 'bg-green-600 hover:bg-green-500 text-white' : 'bg-slate-700 text-slate-500 cursor-not-allowed' ?>">
                                        Contratar
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Vender jogadores do elenco -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
            <div class="p-4 border-b border-slate-800">
                <h3 class="text-sm font-bold text-white"><i class="fas fa-hand-holding-dollar text-green-400 mr-2"></i>Vender do Elenco</h3>
            </div>
            <div class="overflow-y-auto" style="max-height:480px divide-y divide-slate-800">
                <?php foreach ($elenco as $j):
                    $venda = precoVenda((int)$j['forca'], (int)$j['idade']);
                ?>
                <div class="flex items-center gap-2 px-3 py-2.5 border-b border-slate-800 hover:bg-slate-800/40">
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-white truncate"><?= htmlspecialchars($j['nome']) ?></p>
                        <p class="text-[10px] text-slate-500"><?= posLabel($j['posicao']) ?> · OVR <?= $j['forca'] ?> · <?= $j['idade'] ?> anos</p>
                    </div>
                    <span class="text-xs text-green-400 font-semibold flex-shrink-0"><?= appSaldo($venda) ?></span>
                    <form method="POST" action="?page=app">
                        <input type="hidden" name="action" value="vender_jogador">
                        <input type="hidden" name="elenco_id" value="<?= $j['id'] ?>">
                        <button class="text-[10px] font-bold bg-red-600/20 hover:bg-red-600/40 text-red-400 px-2 py-1 rounded-lg transition">
                            Vender
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

</main>
</div>

<!-- Formulário oculto para salvar resultado da partida -->
<form id="form-resultado-partida" method="POST" action="?page=app" style="display:none">
    <input type="hidden" name="action" value="salvar_partida_usuario">
    <input type="hidden" name="partida_id"      id="res-partida-id"      value="">
    <input type="hidden" name="gols_casa"       id="res-gols-casa"       value="">
    <input type="hidden" name="gols_fora"       id="res-gols-fora"       value="">
    <input type="hidden" name="stats_json"      id="res-stats-json"      value="">
    <input type="hidden" name="nova_formacao"   id="res-nova-formacao"   value="">
    <input type="hidden" name="nova_tatica"     id="res-nova-tatica"     value="">
    <input type="hidden" name="lesionados_json" id="res-lesionados-json" value="">
</form>

<script>
function trocarAba(id) {
    document.querySelectorAll('.tab-content').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.tab-nav-btn').forEach(b => {
        b.classList.remove('border-green-500','text-green-400');
        b.classList.add('border-transparent','text-slate-500');
    });
    document.getElementById('tab-' + id).classList.add('active');
    const btn = document.getElementById('tab-btn-' + id);
    if (btn) {
        btn.classList.remove('border-transparent','text-slate-500');
        btn.classList.add('border-green-500','text-green-400');
    }
}

window.MATCH_SETUP = <?= $matchSetup ? json_encode($matchSetup, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) : 'null' ?>;

<?php if (!empty($page_data['match_auto_open'])): ?>
trocarAba('dashboard');
setTimeout(() => { if (typeof abrirViewer === 'function') abrirViewer(); }, 200);
<?php endif; ?>
<?php if ($flashAba): ?>
trocarAba('<?= htmlspecialchars($flashAba) ?>');
<?php endif; ?>
<?php if (isset($msg) && $msg && ($_POST['action'] ?? '') === 'salvar_escalacao'): ?>
trocarAba('elenco');
<?php endif; ?>

// ── ESCALAÇÃO ─────────────────────────────────────────────────────────────────
(function() {
    const ELENCO = <?= json_encode($elencoEscalacao, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const FORMACAO_INICIAL = <?= json_encode($formacaoAtual) ?>;
    const BANCO_IDS_SALVOS = <?= json_encode($bancoSalvosIds) ?>;
    const TATICA_INICIAL   = <?= json_encode($taticaAtual) ?>;

    const POS_COLORS = {GOL:'#f59e0b',ZAG:'#3b82f6',LD:'#06b6d4',LE:'#06b6d4',VOL:'#8b5cf6',MC:'#8b5cf6',MEI:'#ec4899',PE:'#22c55e',PD:'#22c55e',ATA:'#ef4444'};
    const POS_LABELS = {GOL:'GK',ZAG:'CB',LD:'RB',LE:'LB',VOL:'CDM',MC:'CM',MEI:'CAM',PE:'LW',PD:'RW',ATA:'ST'};
    const pColor = p => POS_COLORS[p] || '#94a3b8';
    const pLabel = p => POS_LABELS[p] || p;

    const COMPAT = {
        GOL:['GOL'],
        ZAG:['ZAG','LD','LE'],
        LD:['LD','ZAG'],
        LE:['LE','ZAG'],
        VOL:['VOL','MC'],
        MC:['MC','VOL','MEI'],
        MEI:['MEI','MC','PE','PD'],
        PE:['PE','MEI','ATA'],
        PD:['PD','MEI','ATA'],
        ATA:['ATA','PE','PD'],
    };
    const isCompat = (slotPos, jogPos) => (COMPAT[slotPos] || [slotPos]).includes(jogPos);

    // Formations: 11 slots each with {pos, x (left%), y (bottom%)}
    const FORMACOES = {
        '4-3-3':[
            {pos:'GOL',x:50,y:5},
            {pos:'LD',x:82,y:21},{pos:'ZAG',x:62,y:21},{pos:'ZAG',x:38,y:21},{pos:'LE',x:18,y:21},
            {pos:'MC',x:65,y:43},{pos:'VOL',x:50,y:43},{pos:'MC',x:35,y:43},
            {pos:'PD',x:80,y:70},{pos:'ATA',x:50,y:76},{pos:'PE',x:20,y:70},
        ],
        '4-4-2':[
            {pos:'GOL',x:50,y:5},
            {pos:'LD',x:82,y:21},{pos:'ZAG',x:62,y:21},{pos:'ZAG',x:38,y:21},{pos:'LE',x:18,y:21},
            {pos:'PD',x:80,y:50},{pos:'MC',x:60,y:50},{pos:'MC',x:40,y:50},{pos:'PE',x:20,y:50},
            {pos:'ATA',x:62,y:72},{pos:'ATA',x:38,y:72},
        ],
        '4-2-3-1':[
            {pos:'GOL',x:50,y:5},
            {pos:'LD',x:82,y:21},{pos:'ZAG',x:62,y:21},{pos:'ZAG',x:38,y:21},{pos:'LE',x:18,y:21},
            {pos:'VOL',x:62,y:38},{pos:'VOL',x:38,y:38},
            {pos:'PD',x:80,y:57},{pos:'MEI',x:50,y:57},{pos:'PE',x:20,y:57},
            {pos:'ATA',x:50,y:76},
        ],
        '3-5-2':[
            {pos:'GOL',x:50,y:5},
            {pos:'ZAG',x:65,y:21},{pos:'ZAG',x:50,y:21},{pos:'ZAG',x:35,y:21},
            {pos:'PD',x:84,y:46},{pos:'VOL',x:65,y:46},{pos:'MC',x:50,y:46},{pos:'VOL',x:35,y:46},{pos:'PE',x:16,y:46},
            {pos:'ATA',x:62,y:72},{pos:'ATA',x:38,y:72},
        ],
        '5-3-2':[
            {pos:'GOL',x:50,y:5},
            {pos:'LD',x:90,y:21},{pos:'ZAG',x:72,y:21},{pos:'ZAG',x:50,y:21},{pos:'ZAG',x:28,y:21},{pos:'LE',x:10,y:21},
            {pos:'VOL',x:50,y:46},{pos:'MC',x:66,y:46},{pos:'MC',x:34,y:46},
            {pos:'ATA',x:62,y:72},{pos:'ATA',x:38,y:72},
        ],
        '4-1-4-1':[
            {pos:'GOL',x:50,y:5},
            {pos:'LD',x:82,y:21},{pos:'ZAG',x:62,y:21},{pos:'ZAG',x:38,y:21},{pos:'LE',x:18,y:21},
            {pos:'VOL',x:50,y:38},
            {pos:'PE',x:18,y:57},{pos:'MC',x:40,y:57},{pos:'MC',x:60,y:57},{pos:'PD',x:82,y:57},
            {pos:'ATA',x:50,y:76},
        ],
        '3-4-3':[
            {pos:'GOL',x:50,y:5},
            {pos:'ZAG',x:65,y:21},{pos:'ZAG',x:50,y:21},{pos:'ZAG',x:35,y:21},
            {pos:'LD',x:82,y:46},{pos:'VOL',x:60,y:46},{pos:'MC',x:40,y:46},{pos:'LE',x:18,y:46},
            {pos:'PE',x:20,y:70},{pos:'ATA',x:50,y:72},{pos:'PD',x:80,y:70},
        ],
    };

    // State
    let formacao = FORMACAO_INICIAL in FORMACOES ? FORMACAO_INICIAL : '4-3-3';
    let tatica   = ['normal','ataque','defensivo'].includes(TATICA_INICIAL) ? TATICA_INICIAL : 'normal';
    let slots = [];   // [{pos,x,y,jogadorId|null}, ...]
    let banco = [];   // [jogadorId, ...]
    let selecionado = null;
    let slotSel = null;

    const getJ = id => ELENCO.find(j => j.id === id) || null;
    const titIds = () => slots.filter(s => s.jogadorId !== null).map(s => s.jogadorId);
    const slotForJ = id => slots.find(s => s.jogadorId === id) || null;
    const naoRel = () => { const oc = new Set([...titIds(), ...banco]); return ELENCO.filter(j => !oc.has(j.id)); };

    function initState() {
        slots = FORMACOES[formacao].map(s => ({...s, jogadorId: null}));
        // Assign saved titulares
        ELENCO.filter(j => j.titular === 1).forEach(j => {
            const s = slots.find(s => s.pos === j.posicao && s.jogadorId === null);
            if (s) s.jogadorId = j.id;
        });
        // Assign banco from saved ids or default first 6 non-titulares
        const saved = Array.isArray(BANCO_IDS_SALVOS) ? BANCO_IDS_SALVOS : [];
        const naoTit = ELENCO.filter(j => j.titular !== 1);
        banco = saved.length > 0
            ? saved.filter(id => naoTit.some(j => j.id === id)).slice(0, 6)
            : naoTit.slice(0, 6).map(j => j.id);
    }

    function renderPills() {
        const c = document.getElementById('formacao-pills');
        if (!c) return;
        c.innerHTML = Object.keys(FORMACOES).map(f =>
            `<button type="button" onclick="EF.setFormacao('${f}')"
                class="px-2.5 py-1 rounded-lg text-xs font-bold transition-all ${f===formacao?'bg-green-600 text-white shadow shadow-green-500/30':'bg-slate-800 text-slate-400 hover:bg-slate-700 hover:text-white'}">${f}</button>`
        ).join('');
    }

    const TATICAS = {
        defensivo: {label:'Defensivo', hint:'Menos gols, mais defesa'},
        normal:    {label:'Normal',    hint:'Equilibrado'},
        ataque:    {label:'Ataque',    hint:'Mais gols, mais exposição'},
    };
    function renderTaticaPills() {
        const c = document.getElementById('tatica-pills');
        if (!c) return;
        c.innerHTML = Object.entries(TATICAS).map(([k, v]) => {
            const active = k === tatica;
            const col = k === 'ataque' ? '#ef4444' : (k === 'defensivo' ? '#3b82f6' : '#22c55e');
            const bg  = active ? col + '22' : '#1e293b';
            const brd = active ? col : '#334155';
            const tc  = active ? col : '#64748b';
            return `<button type="button" onclick="EF.setTatica('${k}')" title="${v.hint}"
                style="font-size:11px;font-weight:700;padding:4px 10px;border-radius:8px;border:1px solid ${brd};background:${bg};color:${tc};cursor:pointer;transition:all .2s">${v.label}</button>`;
        }).join('');
    }

    function renderCampo() {
        const wrapper = document.getElementById('campo-wrapper');
        if (!wrapper) return;
        wrapper.querySelectorAll('.ef-slot').forEach(el => el.remove());
        slots.forEach((slot, idx) => {
            const j = slot.jogadorId ? getJ(slot.jogadorId) : null;
            const isSS = slotSel === idx;
            const isJS = j && selecionado === j.id;
            const wrong = j && !isCompat(slot.pos, j.posicao);
            const hl = isSS || isJS;
            const bc = hl ? '#facc15' : (wrong ? '#ef4444' : pColor(slot.pos));
            const tc = hl ? '#facc15' : (wrong ? '#ef4444' : pColor(slot.pos));
            const bg = hl ? '#facc1533' : (wrong ? '#ef444422' : pColor(slot.pos) + '22');
            const bstyle = j ? 'solid' : 'dashed';
            const div = document.createElement('div');
            div.className = 'ef-slot absolute text-center cursor-pointer select-none z-10';
            div.style.cssText = `left:${slot.x}%;bottom:${slot.y}%;transform:translateX(-50%) translateY(50%);width:52px`;
            div.onclick = e => { e.stopPropagation(); EF.clickSlot(idx); };
            if (j) {
                const last = j.nome.split(' ').pop();
                const badge = j.contundido ? '🤕' : (j.suspenso ? '🟥' : '');
                div.innerHTML = `<div style="width:40px;height:40px;border-radius:50%;border:2px ${bstyle} ${bc};background:${bg};color:${tc};display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:900;margin:0 auto">${pLabel(slot.pos)}</div><p style="font-size:8px;color:#fff;font-weight:600;margin-top:2px;overflow:hidden;white-space:nowrap;max-width:52px;text-overflow:ellipsis">${last}${badge}</p>${wrong?'<p style="font-size:7px;color:#ef4444;line-height:1.1">⚠pos</p>':''}`;
            } else {
                const oc = isSS ? '#facc15' : 'rgba(255,255,255,0.18)';
                const ot = isSS ? '#facc15' : 'rgba(255,255,255,0.3)';
                div.innerHTML = `<div style="width:40px;height:40px;border-radius:50%;border:2px dashed ${oc};background:rgba(0,0,0,0.25);color:${ot};display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;margin:0 auto">${pLabel(slot.pos)}</div><p style="font-size:7px;color:#475569;margin-top:2px">vazio</p>`;
            }
            wrapper.appendChild(div);
        });
    }

    function playerRow(j, tipo) {
        const sel = selecionado === j.id;
        const sf = slotForJ(j.id);
        const wrong = tipo === 'tit' && sf && !isCompat(sf.pos, j.posicao);
        const ec = j.energia >= 70 ? '#22c55e' : (j.energia >= 40 ? '#eab308' : '#ef4444');
        const status = j.contundido
            ? `<i class="fas fa-bandage" style="color:#f87171;font-size:9px" title="Contundido — ${j.lesoes_rodadas}R"></i> `
            : (j.suspenso ? '<span style="display:inline-block;width:8px;height:11px;background:#facc15;border-radius:1px;vertical-align:middle"></span> ' : '');
        const nc = wrong ? '#f87171' : (sel ? '#86efac' : '#f1f5f9');
        const rowBg = sel ? 'background:rgba(71,85,105,0.6);outline:1px solid rgba(34,197,94,0.35);outline-offset:-1px;' : '';

        // Badge: duração de lesão
        const lesBadge = j.contundido && j.lesoes_rodadas > 0
            ? `<span style="font-size:8px;color:#f87171;font-weight:700;flex-shrink:0">${j.lesoes_rodadas}R</span>`
            : '';
        // Badge: contrato expirando
        const ctBadge = j.meses_contrato <= 3
            ? `<span style="font-size:8px;color:#f97316;font-weight:700;flex-shrink:0" title="Contrato: ${j.meses_contrato}m">⚠</span>`
            : (j.meses_contrato <= 6 ? `<span style="font-size:8px;color:#eab308;flex-shrink:0" title="Contrato: ${j.meses_contrato}m">⚠</span>` : '');
        // Badge: potencial acima da força atual
        const potBadge = (j.potencial - j.forca) >= 5
            ? `<span style="font-size:8px;color:#a78bfa;font-weight:700;flex-shrink:0" title="Potencial ${j.potencial}">↑${j.potencial}</span>`
            : '';

        let btns = '';
        if (tipo === 'tit')
            btns = `<button type="button" onclick="event.stopPropagation();EF.tirarTitular(${j.id})" style="font-size:9px;background:rgba(239,68,68,.15);color:#f87171;padding:1px 6px;border-radius:4px;margin-left:2px;flex-shrink:0;cursor:pointer">Tirar</button>`;
        else if (tipo === 'banco')
            btns = `<button type="button" onclick="event.stopPropagation();EF.escalarBanco(${j.id})" style="font-size:9px;background:rgba(34,197,94,.15);color:#4ade80;padding:1px 6px;border-radius:4px;margin-left:2px;flex-shrink:0;cursor:pointer">Escalar</button><button type="button" onclick="event.stopPropagation();EF.tirarBanco(${j.id})" style="font-size:9px;background:#1e293b;color:#94a3b8;padding:1px 6px;border-radius:4px;margin-left:2px;flex-shrink:0;cursor:pointer">Tirar</button>`;
        else
            btns = `<button type="button" onclick="event.stopPropagation();EF.addBanco(${j.id})" style="font-size:9px;background:rgba(59,130,246,.15);color:#60a5fa;padding:1px 6px;border-radius:4px;margin-left:2px;flex-shrink:0;cursor:pointer">Banco</button>`;
        return `<div onclick="EF.clickJogador(${j.id})" style="display:flex;align-items:center;gap:4px;padding:6px 12px;border-bottom:1px solid #1e293b;cursor:pointer;${rowBg}" onmouseenter="if(!this.style.outline)this.style.background='rgba(30,41,59,0.5)'" onmouseleave="this.style.background='${sel?'rgba(71,85,105,0.6)':''}'" >
            <span style="color:${pColor(j.posicao)};font-size:9px;font-weight:900;flex-shrink:0;width:24px">${pLabel(j.posicao)}</span>
            <span style="flex-shrink:0">${status}</span>
            <span style="font-size:11px;font-weight:600;color:${nc};flex:1;overflow:hidden;white-space:nowrap;text-overflow:ellipsis">${j.nome}</span>
            <span style="font-size:10px;color:#64748b;flex-shrink:0">${j.forca}</span>
            ${potBadge}${ctBadge}${lesBadge}
            <span style="font-size:9px;color:${ec};flex-shrink:0">${j.energia}⚡</span>
            ${wrong ? '<i class="fas fa-triangle-exclamation" style="color:#f87171;font-size:9px;flex-shrink:0"></i>' : ''}
            ${btns}
        </div>`;
    }

    function renderLista(cid, jogs, tipo) {
        const el = document.getElementById(cid);
        if (!el) return;
        el.innerHTML = jogs.length ? jogs.map(j => playerRow(j, tipo)).join('') : `<p style="font-size:11px;color:#475569;text-align:center;padding:10px">Nenhum</p>`;
    }

    function renderListas() {
        const tit = slots.filter(s => s.jogadorId !== null).map(s => getJ(s.jogadorId)).filter(Boolean);
        const bancoJogs = banco.map(id => getJ(id)).filter(Boolean);
        const nrJogs = naoRel();
        renderLista('lista-titulares', tit, 'tit');
        renderLista('lista-banco', bancoJogs, 'banco');
        renderLista('lista-nr', nrJogs, 'nr');
        const tc = tit.length, bc = banco.length, nc = nrJogs.length;
        document.getElementById('tit-count').textContent = tc + '/11';
        document.getElementById('banco-count').textContent = bc + '/6';
        document.getElementById('cnt-tit').textContent = tc;
        document.getElementById('cnt-banco').textContent = bc;
        document.getElementById('nr-count').textContent = nc;
    }

    function updateInputs() {
        const fi = document.getElementById('input-formacao');
        const ta = document.getElementById('input-tatica');
        const ti = document.getElementById('input-tit-ids');
        const bi = document.getElementById('input-banco-ids');
        if (fi) fi.value = formacao;
        if (ta) ta.value = tatica;
        if (ti) ti.value = titIds().join(',');
        if (bi) bi.value = banco.join(',');
    }

    function render() { renderPills(); renderTaticaPills(); renderCampo(); renderListas(); updateInputs(); }

    // ── Actions ──────────────────────────────────────────────────────────────
    window.EF = {
        setFormacao(f) {
            if (!FORMACOES[f]) return;
            const prev = slots.map(s => ({pos:s.pos, jogadorId:s.jogadorId}));
            formacao = f;
            slots = FORMACOES[f].map(s => ({...s, jogadorId:null}));
            prev.forEach(ps => {
                if (!ps.jogadorId) return;
                const j = getJ(ps.jogadorId);
                if (!j) return;
                const ns = slots.find(s => s.pos === j.posicao && s.jogadorId === null);
                if (ns) ns.jogadorId = j.id;
            });
            const reassigned = new Set(titIds());
            prev.forEach(ps => {
                if (ps.jogadorId && !reassigned.has(ps.jogadorId) && !banco.includes(ps.jogadorId) && banco.length < 6)
                    banco.push(ps.jogadorId);
            });
            selecionado = null; slotSel = null;
            render();
        },

        clickSlot(idx) {
            if (selecionado !== null) {
                const j = getJ(selecionado);
                if (!j) { selecionado = null; render(); return; }
                const existIdx = slots.findIndex(s => s.jogadorId === selecionado);
                const inBanco = banco.includes(selecionado);
                const prev = slots[idx].jogadorId;
                if (existIdx >= 0) {
                    slots[existIdx].jogadorId = prev;
                } else if (inBanco) {
                    banco = banco.filter(id => id !== selecionado);
                    if (prev !== null) banco.push(prev);
                } else {
                    if (prev !== null && banco.length < 6 && !banco.includes(prev)) banco.push(prev);
                }
                slots[idx].jogadorId = selecionado;
                selecionado = null; slotSel = null;
            } else {
                slotSel = slotSel === idx ? null : idx;
                selecionado = (slotSel !== null && slots[idx].jogadorId) ? slots[idx].jogadorId : null;
            }
            render();
        },

        clickJogador(id) {
            if (slotSel !== null && selecionado === null) {
                selecionado = id; EF.clickSlot(slotSel); return;
            }
            if (selecionado === id) { selecionado = null; slotSel = null; render(); return; }
            if (selecionado !== null) {
                const idA = selecionado, idB = id;
                const siA = slots.findIndex(s => s.jogadorId === idA);
                const siB = slots.findIndex(s => s.jogadorId === idB);
                const biA = banco.indexOf(idA), biB = banco.indexOf(idB);
                if (siA >= 0 && siB >= 0) {
                    [slots[siA].jogadorId, slots[siB].jogadorId] = [idB, idA];
                } else if (siA >= 0) {
                    slots[siA].jogadorId = idB;
                    if (biB >= 0) banco[biB] = idA;
                } else if (siB >= 0) {
                    slots[siB].jogadorId = idA;
                    if (biA >= 0) banco[biA] = idB;
                } else {
                    selecionado = id; render(); return;
                }
                selecionado = null; slotSel = null;
            } else {
                selecionado = id; slotSel = null;
            }
            render();
        },

        tirarTitular(id) {
            const s = slots.find(s => s.jogadorId === id);
            if (s) s.jogadorId = null;
            if (!banco.includes(id) && banco.length < 6) banco.push(id);
            if (selecionado === id) { selecionado = null; slotSel = null; }
            render();
        },

        escalarBanco(id) {
            const j = getJ(id); if (!j) return;
            let s = slots.find(s => s.jogadorId === null && s.pos === j.posicao);
            if (!s) s = slots.find(s => s.jogadorId === null && isCompat(s.pos, j.posicao));
            if (!s) s = slots.find(s => s.jogadorId === null);
            if (!s) return;
            banco = banco.filter(b => b !== id);
            s.jogadorId = id;
            render();
        },

        tirarBanco(id) { banco = banco.filter(b => b !== id); render(); },
        addBanco(id) { if (banco.length < 6 && !banco.includes(id)) { banco.push(id); render(); } },
        setTatica(t) { if (TATICAS[t]) { tatica = t; render(); } },
    };

    initState();
    render();

    const cw = document.getElementById('campo-wrapper');
    if (cw) cw.addEventListener('click', () => { selecionado = null; slotSel = null; render(); });
})();

// ── MATCH VIEWER ──────────────────────────────────────────────────────────────
(function() {
    let mvTimer = null;
    let mvMinuto = 0;
    let mvPaused = false;
    let mvSpeed = 1;
    let mvDelay = 900;
    let mvScore = {casa: 0, fora: 0};
    let mvNotas = {};     // nome → {nota, gols, assists, amarelos, vermelhos, posicao, titular, id}
    let mvSubCount = 0;
    let mvTitulares = []; // [{nome, posicao, id}]
    let mvBanco = [];     // [{nome, posicao, id}]
    let mvSubOut = null;
    let mvSubIn  = null;
    let mvTaticaAtual = 'normal';
    let mvFormacaoAtual = '4-3-3';
    let mvJogAdv = [];    // [{nome, posicao}] titulares do adversário
    let mvEvtLog = [];    // todos os eventos gerados (para o popup final)
    let mvLesionados = []; // ids de jogadores lesionados durante o jogo

    const SPEEDS = {1: 900, 2: 450, 4: 200};
    const POS_ORDER = {GOL:0,ZAG:1,LD:2,LE:3,VOL:4,MC:5,MEI:6,PE:7,PD:8,ATA:9};
    const SCORER_W = {GOL:0,ZAG:1,LD:1,LE:1,VOL:3,MC:5,MEI:8,PE:10,PD:10,ATA:15};

    function mvData() { return window.MATCH_SETUP || null; }

    function notaCor(n) {
        if (n >= 8.0) return '#4ade80';
        if (n >= 7.0) return '#a3e635';
        if (n >= 6.5) return '#facc15';
        if (n >= 5.5) return '#fb923c';
        return '#f87171';
    }

    function mvRenderNotas() {
        const el = document.getElementById('mv-notas');
        if (!el) return;
        const entries = Object.entries(mvNotas);
        const titulares = entries.filter(([,d]) => d.titular)
            .sort((a,b) => (POS_ORDER[a[1].posicao] ?? 99) - (POS_ORDER[b[1].posicao] ?? 99));
        const banco = entries.filter(([,d]) => !d.titular);

        function renderRow([nome, d]) {
            const nc = notaCor(d.nota);
            const last = nome.split(' ').pop();
            const pos = d.posicao ? `<span style="font-size:8px;color:#64748b;width:22px;flex-shrink:0">${d.posicao}</span>` : '';
            const badges = (d.gols > 0 ? `<span style="font-size:9px">⚽${d.gols}</span>` : '') + (d.assists > 0 ? `<span style="font-size:9px;color:#60a5fa">🅰${d.assists}</span>` : '');
            return `<div style="display:flex;align-items:center;gap:4px;padding:3px 6px;border-radius:6px;background:rgba(30,41,59,0.5)">
                ${pos}<span style="font-size:10px;color:#94a3b8;flex:1;overflow:hidden;white-space:nowrap;text-overflow:ellipsis">${last}</span>
                ${badges}
                <span style="font-size:11px;font-weight:900;color:${nc};flex-shrink:0">${d.nota.toFixed(1)}</span>
            </div>`;
        }

        const header = (label) => `<div style="font-size:9px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.05em;padding:5px 6px 2px">${label}</div>`;
        let html = '';
        if (titulares.length) html += header('Titulares') + titulares.map(renderRow).join('');
        if (banco.length)    html += header('Banco')     + banco.map(renderRow).join('');
        el.innerHTML = html;
    }

    function mvAddEvento(ev, nomeCasa, nomeFora, meuTimeId) {
        const el = document.getElementById('mv-eventos');
        if (!el) return;
        const isMeu = ev.time_id === meuTimeId;
        const nomeTime = ev.time === 'casa' ? nomeCasa : nomeFora;
        let icon = '', txt = '', rowColor = 'rgba(30,41,59,0.4)';

        if (ev.tipo === 'gol') {
            icon = '⚽';
            rowColor = isMeu ? 'rgba(34,197,94,0.15)' : 'rgba(239,68,68,0.1)';
            txt = `<strong style="color:${isMeu?'#4ade80':'#f87171'}">${ev.jogador}</strong>`;
            if (ev.assistido) txt += ` <span style="color:#94a3b8;font-size:9px">(assist: ${ev.assistido})</span>`;
            txt += ` <span style="color:#64748b;font-size:9px">${nomeTime}</span>`;
        } else if (ev.tipo === 'amarelo') {
            icon = '<span style="display:inline-block;width:9px;height:12px;background:#facc15;border-radius:1px;vertical-align:middle"></span>';
            txt = `<span style="color:#fde68a">${ev.jogador}</span> <span style="color:#64748b;font-size:9px">${nomeTime}</span>`;
        } else if (ev.tipo === 'vermelho') {
            icon = '<span style="display:inline-block;width:9px;height:12px;background:#ef4444;border-radius:1px;vertical-align:middle"></span>';
            txt = `<span style="color:#fca5a5">${ev.jogador}</span> <span style="color:#64748b;font-size:9px">${nomeTime}</span>`;
            rowColor = 'rgba(239,68,68,0.1)';
        } else if (ev.tipo === 'defesa') {
            icon = '<i class="fas fa-hand-paper" style="color:#60a5fa;font-size:10px"></i>';
            txt = `<span style="color:#93c5fd">Defesa</span> <span style="color:#64748b;font-size:9px">${nomeTime}</span>`;
        } else if (ev.tipo === 'falta') {
            icon = '<i class="fas fa-whistle" style="color:#94a3b8;font-size:10px"></i>';
            txt = `<span style="color:#94a3b8">Falta — ${ev.jogador}</span>`;
        } else if (ev.tipo === 'lesao') {
            icon = '🚑';
            txt = `<strong style="color:#f97316">${ev.jogador}</strong> <span style="color:#64748b;font-size:9px">saiu lesionado</span>`;
            rowColor = 'rgba(249,115,22,0.12)';
        } else if (ev.tipo === 'sub') {
            icon = '<i class="fas fa-exchange-alt" style="color:#a78bfa;font-size:10px"></i>';
            txt = ev.saiu
                ? `<span style="color:#4ade80">${ev.jogador}</span> <span style="color:#64748b;font-size:9px">↑</span> <span style="color:#f87171">${ev.saiu}</span> <span style="color:#64748b;font-size:9px">↓</span>`
                : `<span style="color:#a78bfa">${ev.jogador}</span>`;
            rowColor = 'rgba(139,92,246,0.08)';
        } else {
            return;
        }

        const div = document.createElement('div');
        div.style.cssText = `display:flex;align-items:center;gap:6px;padding:5px 8px;border-radius:8px;background:${rowColor};animation:fadeIn 0.3s ease`;
        div.innerHTML = `<span style="color:#64748b;font-size:10px;font-weight:700;flex-shrink:0;width:24px;text-align:right">${ev.minuto}'</span><span style="flex-shrink:0">${icon}</span><span style="font-size:11px;flex:1">${txt}</span>`;
        el.insertBefore(div, el.firstChild);
    }

    function mvUpdateNotas(ev, meuTimeId) {
        const isMeu = ev.time_id === meuTimeId;
        if (ev.tipo === 'gol') {
            if (isMeu && mvNotas[ev.jogador]) {
                mvNotas[ev.jogador].nota = Math.min(10, mvNotas[ev.jogador].nota + 1.33); // 3 gols = 10
                mvNotas[ev.jogador].gols++;
            }
            if (isMeu && ev.assistido && mvNotas[ev.assistido]) {
                mvNotas[ev.assistido].nota = Math.min(10, mvNotas[ev.assistido].nota + 1.0); // ~4 assist = 10
                mvNotas[ev.assistido].assists++;
            }
            if (!isMeu) {
                Object.keys(mvNotas).forEach(n => {
                    mvNotas[n].nota = Math.max(1, mvNotas[n].nota - 0.1);
                });
            }
        } else if (ev.tipo === 'defesa' && isMeu) {
            if (mvNotas[ev.jogador]) mvNotas[ev.jogador].nota = Math.min(10, mvNotas[ev.jogador].nota + 0.4);
        } else if (ev.tipo === 'amarelo' && isMeu) {
            if (mvNotas[ev.jogador]) {
                mvNotas[ev.jogador].nota = Math.max(1, mvNotas[ev.jogador].nota - 0.5);
                mvNotas[ev.jogador].amarelos = (mvNotas[ev.jogador].amarelos || 0) + 1;
            }
        } else if (ev.tipo === 'vermelho' && isMeu) {
            if (mvNotas[ev.jogador]) {
                mvNotas[ev.jogador].nota = Math.max(1, mvNotas[ev.jogador].nota - 1.5);
                mvNotas[ev.jogador].vermelhos = (mvNotas[ev.jogador].vermelhos || 0) + 1;
            }
        } else if (ev.tipo === 'lesao' && isMeu) {
            if (mvNotas[ev.jogador]) mvNotas[ev.jogador].nota = Math.max(1, mvNotas[ev.jogador].nota - 1.0);
        }
    }

    function pickByWeight(arr, wFn) {
        const total = arr.reduce((s,x) => s + wFn(x), 0);
        if (!total) return arr.length ? arr[Math.floor(Math.random() * arr.length)] : null;
        let r = Math.random() * total;
        for (const x of arr) { r -= wFn(x); if (r <= 0) return x; }
        return arr[arr.length - 1];
    }
    function pickScorer(pl) { return pl.length ? pickByWeight(pl, p => SCORER_W[p.posicao] || 5) : null; }
    function pickAssister(pl, exc) {
        const c = pl.filter(p => p !== exc && p.posicao !== 'GOL');
        return c.length ? c[Math.floor(Math.random() * c.length)] : null;
    }
    function calcLambdaPerMin(d) {
        const ratio    = d.forca_meu / (d.forca_adv || 65);
        const ratioAdv = (d.forca_adv || 65) / (d.forca_meu || 65);
        const hb  = d.eh_casa ? 1.1 : 1.0;
        const hbA = d.eh_casa ? 1.0 : 1.1;

        let lM = 1.5 * Math.sqrt(ratio)    * hb;
        let lA = 1.5 * Math.sqrt(ratioAdv) * hbA;

        if (mvTaticaAtual === 'ataque') {
            // Ataque: bônus ofensivo escala com sua força relativa
            // Quanto mais forte você é, mais eficaz o ataque
            lM *= 1.0 + 0.25 * Math.min(ratio, 1.5);   // +25% no equilíbrio, até +37.5% sendo 50% mais forte
            lA *= 1.20;                                  // sempre fica mais exposto
        } else if (mvTaticaAtual === 'defensivo') {
            // Defensivo: proteção escala com a força do adversário
            // Quanto mais forte o adversário, mais a tática reduz os gols sofridos
            lA *= 1.0 - 0.20 * Math.min(ratioAdv, 1.5); // -20% no equilíbrio, até -30% sendo 50% mais fraco
            lM *= 0.75;                                   // sempre ataca menos
        }

        return {
            meu: Math.min(5, Math.max(0.3, lM)) / 90,
            adv: Math.min(5, Math.max(0.3, lA)) / 90,
        };
    }
    function simMinuto(min) {
        const d = mvData(); if (!d) return [];
        const lam = calcLambdaPerMin(d);
        const evs = [];
        const meuSide = d.eh_casa ? 'casa' : 'fora';
        const advSide = d.eh_casa ? 'fora' : 'casa';
        const advId   = d.eh_casa ? d.time_fora_id : d.time_casa_id;
        if (Math.random() < lam.meu) {
            const sc = pickScorer(mvTitulares);
            if (sc) {
                const as = Math.random() < 0.45 ? pickAssister(mvTitulares, sc) : null;
                evs.push({tipo:'gol', minuto:min, time:meuSide, time_id:d.meu_time_id, jogador:sc.nome, assistido:as?.nome||null});
            }
        }
        if (Math.random() < lam.adv) {
            const sc = mvJogAdv.length ? mvJogAdv[Math.floor(Math.random()*mvJogAdv.length)] : {nome:'Adversário'};
            evs.push({tipo:'gol', minuto:min, time:advSide, time_id:advId, jogador:sc.nome, assistido:null});
        }
        if (Math.random() < 0.011) {
            const isMeu = Math.random() < 0.5;
            if (isMeu && mvTitulares.length) {
                const j = mvTitulares[Math.floor(Math.random()*mvTitulares.length)];
                evs.push({tipo:'amarelo', minuto:min, time:meuSide, time_id:d.meu_time_id, jogador:j.nome});
            } else if (!isMeu && mvJogAdv.length) {
                const j = mvJogAdv[Math.floor(Math.random()*mvJogAdv.length)];
                evs.push({tipo:'amarelo', minuto:min, time:advSide, time_id:advId, jogador:j.nome});
            }
        }
        // Lesão: ~0.12% por minuto = ~1 a cada 9 jogos para o time do usuário
        if (Math.random() < 0.0012 && mvTitulares.length > 1) {
            const j = mvTitulares[Math.floor(Math.random()*mvTitulares.length)];
            evs.push({tipo:'lesao', minuto:min, time:meuSide, time_id:d.meu_time_id, jogador:j.nome, jogador_id:j.id});
        }
        return evs;
    }
    function mvProcessEvento(ev) {
        const d = mvData(); if (!d) return;
        if (ev.tipo === 'gol') {
            if (ev.time === 'casa') mvScore.casa++; else mvScore.fora++;
            document.getElementById('mv-gols-casa').textContent = mvScore.casa;
            document.getElementById('mv-gols-fora').textContent = mvScore.fora;
        }
        if (['gol','amarelo','vermelho'].includes(ev.tipo)) mvEvtLog.push(ev);

        // Lesão: remove do campo e faz substituição automática se houver banco disponível
        if (ev.tipo === 'lesao' && ev.time_id === d.meu_time_id) {
            if (ev.jogador_id) mvLesionados.push(ev.jogador_id);
            const idx = mvTitulares.findIndex(j => j.nome === ev.jogador);
            if (idx >= 0 && mvBanco.length > 0 && mvSubCount < 5) {
                const sai = mvTitulares[idx];
                const entra = mvBanco.shift();
                entra.posicao = sai.posicao;
                mvTitulares.splice(idx, 1, entra);
                if (!mvNotas[entra.nome]) mvNotas[entra.nome] = {nota:6.0,gols:0,assists:0,amarelos:0,vermelhos:0,posicao:sai.posicao,titular:true,id:entra.id||0};
                else { mvNotas[entra.nome].titular = true; mvNotas[entra.nome].posicao = sai.posicao; }
                if (mvNotas[sai.nome]) mvNotas[sai.nome].titular = false;
                mvSubCount++;
                document.getElementById('mv-subs-label').textContent = `Subs ${mvSubCount}/5`;
                if (mvSubCount >= 5) document.getElementById('mv-btn-subs').disabled = true;
                if (d) mvAddEvento({tipo:'sub', minuto:ev.minuto, time:'', time_id:0, jogador:entra.nome, saiu:sai.nome}, d.nome_casa, d.nome_fora, d.meu_time_id);
            } else if (idx >= 0 && (mvBanco.length === 0 || mvSubCount >= 5)) {
                mvTitulares.splice(idx, 1);
            }
        }

        mvUpdateNotas(ev, d.meu_time_id);
        mvAddEvento(ev, d.nome_casa, d.nome_fora, d.meu_time_id);
        mvRenderNotas();
    }
    function mvTick() {
        const d = mvData();
        if (!d || mvPaused || mvMinuto >= 90) return;
        mvMinuto++;
        document.getElementById('mv-relogio').textContent = mvMinuto + "'";
        document.getElementById('mv-progress-fill').style.width = Math.min(100, (mvMinuto/90)*100) + '%';
        simMinuto(mvMinuto).forEach(ev => mvProcessEvento(ev));
        if (mvMinuto >= 90) mvEnd();
        else mvTimer = setTimeout(mvTick, mvDelay);
    }

    function mvEnd() {
        clearTimeout(mvTimer); mvTimer = null;
        const dot = document.getElementById('mv-status-dot');
        if (dot) { dot.style.background = '#64748b'; dot.style.animation = 'none'; }
        const rel = document.getElementById('mv-relogio'); if (rel) rel.textContent = 'FIM';
        const btn = document.getElementById('mv-btn-play'); if (btn) btn.disabled = true;
        const subBtn = document.getElementById('mv-btn-subs'); if (subBtn) subBtn.disabled = true;
        ['defensivo','normal','ataque'].forEach(t => {
            const b = document.getElementById('mv-tat-btn-'+t); if (b) b.disabled = true;
        });
        document.getElementById('mv-sub-panel').classList.add('hidden');
        const closeBtn = document.getElementById('mv-close-btn'); if (closeBtn) closeBtn.style.display = '';
        setTimeout(mvShowEndPopup, 800);
    }

    function mvShowEndPopup() {
        const d = mvData(); if (!d) return;
        document.getElementById('mv-end-rodada').textContent = 'Rodada ' + d.rodada;
        document.getElementById('mv-end-nome-casa').textContent = d.nome_casa;
        document.getElementById('mv-end-nome-fora').textContent = d.nome_fora;
        const nc = document.getElementById('mv-end-nome-casa-col'); if (nc) nc.textContent = d.nome_casa;
        const nf = document.getElementById('mv-end-nome-fora-col'); if (nf) nf.textContent = d.nome_fora;
        const scoreEl = document.getElementById('mv-end-score');
        scoreEl.textContent = mvScore.casa + ' – ' + mvScore.fora;
        const isCasa = d.meu_time_id === d.time_casa_id;
        const meuG = isCasa ? mvScore.casa : mvScore.fora;
        const advG = isCasa ? mvScore.fora : mvScore.casa;
        scoreEl.style.color = meuG > advG ? '#4ade80' : (meuG < advG ? '#f87171' : '#facc15');

        function renderEvt(ev) {
            const isMeu = ev.time_id === d.meu_time_id;
            if (ev.tipo === 'gol') {
                const cor = isMeu ? '#4ade80' : '#f87171';
                let txt = `<span style="color:${cor};font-weight:700">${ev.jogador}</span>`;
                if (ev.assistido) txt += ` <span style="color:#64748b;font-size:9px">(${ev.assistido})</span>`;
                return `<div style="display:flex;align-items:center;gap:4px;padding:2px 0;font-size:11px"><span style="color:#475569;font-size:9px;width:22px;flex-shrink:0">${ev.minuto}'</span>⚽${txt}</div>`;
            } else if (ev.tipo === 'amarelo') {
                return `<div style="display:flex;align-items:center;gap:4px;padding:2px 0;font-size:11px"><span style="color:#475569;font-size:9px;width:22px;flex-shrink:0">${ev.minuto}'</span><span style="display:inline-block;width:8px;height:11px;background:#facc15;border-radius:1px"></span><span style="color:#fde68a">${ev.jogador}</span></div>`;
            } else if (ev.tipo === 'vermelho') {
                return `<div style="display:flex;align-items:center;gap:4px;padding:2px 0;font-size:11px"><span style="color:#475569;font-size:9px;width:22px;flex-shrink:0">${ev.minuto}'</span><span style="display:inline-block;width:8px;height:11px;background:#ef4444;border-radius:1px"></span><span style="color:#fca5a5">${ev.jogador}</span></div>`;
            }
            return '';
        }
        const casaEvs = mvEvtLog.filter(e => e.time === 'casa');
        const foraEvs = mvEvtLog.filter(e => e.time === 'fora');
        const casaEl  = document.getElementById('mv-end-eventos-casa');
        const foraEl  = document.getElementById('mv-end-eventos-fora');
        if (casaEl) casaEl.innerHTML = casaEvs.length ? casaEvs.map(renderEvt).join('') : '<p style="font-size:10px;color:#475569;text-align:center;padding:4px">—</p>';
        if (foraEl) foraEl.innerHTML = foraEvs.length ? foraEvs.map(renderEvt).join('') : '<p style="font-size:10px;color:#475569;text-align:center;padding:4px">—</p>';

        // Notas
        const notasEl = document.getElementById('mv-end-notas');
        const entries = Object.entries(mvNotas);
        const tits = entries.filter(([,x]) => x.titular).sort((a,b) => (POS_ORDER[a[1].posicao]??99)-(POS_ORDER[b[1].posicao]??99));
        const bnco = entries.filter(([,x]) => !x.titular);
        function renderEndRow([nome, x]) {
            const nc2 = notaCor(x.nota);
            const pos2 = x.posicao ? `<span style="font-size:9px;color:#64748b;width:24px;flex-shrink:0;display:inline-block">${x.posicao}</span>` : '';
            const badges2 = (x.gols>0?`<span style="font-size:10px;margin-right:2px">⚽${x.gols}</span>`:'')+(x.assists>0?`<span style="font-size:10px;color:#60a5fa">🅰${x.assists}</span>`:'');
            return `<div style="display:flex;align-items:center;gap:4px;padding:3px 6px;border-radius:6px;background:rgba(30,41,59,0.5);margin-bottom:2px">${pos2}<span style="font-size:11px;color:#94a3b8;flex:1;overflow:hidden;white-space:nowrap;text-overflow:ellipsis">${nome}</span>${badges2}<span style="font-size:13px;font-weight:900;color:${nc2};flex-shrink:0;margin-left:4px">${x.nota.toFixed(1)}</span></div>`;
        }
        const hdr = l => `<div style="font-size:9px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.05em;padding:5px 6px 2px">${l}</div>`;
        notasEl.innerHTML = (tits.length ? hdr('Titulares')+tits.map(renderEndRow).join('') : '') + (bnco.length ? hdr('Banco')+bnco.map(renderEndRow).join('') : '');

        // Preenche form de resultado
        const statsArr = Object.entries(mvNotas).filter(([,x]) => x.id > 0)
            .map(([,x]) => ({id:x.id, gols:x.gols||0, assists:x.assists||0, amarelos:x.amarelos||0, vermelhos:x.vermelhos||0, nota:+x.nota.toFixed(2)}));
        document.getElementById('res-partida-id').value      = d.partida_id;
        document.getElementById('res-gols-casa').value       = mvScore.casa;
        document.getElementById('res-gols-fora').value       = mvScore.fora;
        document.getElementById('res-stats-json').value      = JSON.stringify(statsArr);
        document.getElementById('res-nova-formacao').value   = mvFormacaoAtual;
        document.getElementById('res-nova-tatica').value     = mvTaticaAtual;
        document.getElementById('res-lesionados-json').value = JSON.stringify(mvLesionados);

        document.getElementById('mv-end-popup').classList.remove('hidden');
    }

    function mvUpdateTaticaBtns() {
        const labels = {defensivo:'Def',normal:'Normal',ataque:'Atk'};
        ['defensivo','normal','ataque'].forEach(t => {
            const btn = document.getElementById('mv-tat-btn-'+t); if (!btn) return;
            const active = t === mvTaticaAtual;
            const col = t==='ataque'?'#dc2626':(t==='defensivo'?'#2563eb':'#16a34a');
            btn.style.background  = active ? col : '#1e293b';
            btn.style.color       = active ? '#fff' : '#94a3b8';
            btn.style.borderColor = active ? col : '#334155';
        });
        const lbl = document.getElementById('mv-tat-label');
        if (lbl) lbl.textContent = {defensivo:'Defensivo',normal:'Normal',ataque:'Ataque'}[mvTaticaAtual] || '';
    }

    window.mvSetTatica = function(t) {
        if (!['defensivo','normal','ataque'].includes(t)) return;
        mvTaticaAtual = t;
        mvUpdateTaticaBtns();
        const d = mvData(); if (!d) return;
        const labels = {defensivo:'Tática: Defensivo',normal:'Tática: Normal',ataque:'Tática: Ataque'};
        mvAddEvento({tipo:'sub',minuto:mvMinuto,time:'',time_id:0,jogador:labels[t],saiu:''}, d.nome_casa, d.nome_fora, d.meu_time_id);
    };

    window.abrirViewer = function() {
        const d = mvData(); if (!d) return;

        mvMinuto = 0; mvPaused = false; mvScore = {casa:0,fora:0}; mvSpeed = 1; mvDelay = SPEEDS[1];
        mvEvtLog = [];
        mvLesionados = [];
        clearTimeout(mvTimer);

        mvTaticaAtual = d.tatica_inicial || 'normal';
        mvFormacaoAtual = d.formacao_inicial || '4-3-3';
        mvSubCount = 0; mvTitulares = []; mvBanco = []; mvSubOut = null; mvSubIn = null;
        mvNotas = {}; mvJogAdv = [];

        (d.jogadores_meu || []).forEach(j => {
            mvNotas[j.nome] = {nota:6.0,gols:0,assists:0,amarelos:0,vermelhos:0,posicao:j.posicao||'',titular:!!j.titular,id:j.id||0};
            if (j.titular) mvTitulares.push({nome:j.nome,posicao:j.posicao||'',id:j.id||0});
            else           mvBanco.push({nome:j.nome,posicao:j.posicao||'',id:j.id||0});
        });
        mvJogAdv = (d.jogadores_adv || []).filter(j => j.titular).map(j => ({nome:j.nome,posicao:j.posicao}));

        document.getElementById('mv-nome-casa').textContent = d.nome_casa;
        document.getElementById('mv-nome-fora').textContent = d.nome_fora;
        document.getElementById('mv-gols-casa').textContent = '0';
        document.getElementById('mv-gols-fora').textContent = '0';
        document.getElementById('mv-relogio').textContent = "0'";
        document.getElementById('mv-status-dot').style.cssText = 'width:8px;height:8px;border-radius:50%;background:#4ade80;animation:pulse 1s infinite';
        document.getElementById('mv-progress-fill').style.width = '0%';
        document.getElementById('mv-eventos').innerHTML = '';
        document.getElementById('mv-btn-play').disabled = false;
        document.getElementById('mv-play-icon').className = 'fas fa-pause';
        document.getElementById('mv-play-label').textContent = 'Pausar';
        document.getElementById('mv-btn-subs').disabled = false;
        document.getElementById('mv-subs-label').textContent = 'Subs 0/5';
        document.getElementById('mv-sub-panel').classList.add('hidden');
        document.getElementById('mv-end-popup').classList.add('hidden');
        const closeBtn2 = document.getElementById('mv-close-btn'); if (closeBtn2) closeBtn2.style.display = 'none';

        ['defensivo','normal','ataque'].forEach(t => {
            const b = document.getElementById('mv-tat-btn-'+t); if (b) b.disabled = false;
        });
        mvUpdateTaticaBtns();

        document.querySelectorAll('.mv-speed-btn').forEach(b => {
            const a = b.dataset.speed == '1';
            b.classList.toggle('bg-slate-600', a); b.classList.toggle('text-white', a);
            b.classList.toggle('bg-slate-800', !a); b.classList.toggle('text-slate-400', !a);
        });

        mvRenderNotas();
        const v = document.getElementById('match-viewer');
        v.classList.remove('hidden'); v.classList.add('flex');
        mvTimer = setTimeout(mvTick, mvDelay);
    };

    function mvRenderSubPanel() {
        document.getElementById('mv-sub-count').textContent = mvSubCount;
        const outEl = document.getElementById('mv-sub-list-out');
        const inEl  = document.getElementById('mv-sub-list-in');

        // Ordena por posição preservando índice original para seleção
        const sortedOut = mvTitulares.map((j, i) => ({...j, _i: i}))
            .sort((a, b) => (POS_ORDER[a.posicao] ?? 99) - (POS_ORDER[b.posicao] ?? 99));
        const sortedIn = mvBanco.map((j, i) => ({...j, _i: i}))
            .sort((a, b) => (POS_ORDER[a.posicao] ?? 99) - (POS_ORDER[b.posicao] ?? 99));

        outEl.innerHTML = sortedOut.map(j => {
            const sel = j._i === mvSubOut;
            const bg  = sel ? 'rgba(239,68,68,0.15)' : 'rgba(15,23,42,0.8)';
            const brd = sel ? '#f87171' : '#1e293b';
            const cor = sel ? '#fca5a5' : '#cbd5e1';
            return `<div onclick="mvSelOut(${j._i})" style="cursor:pointer;padding:7px 10px;border-radius:8px;background:${bg};border:1px solid ${brd};display:flex;align-items:center;gap:6px;margin-bottom:3px">
                <span style="font-size:9px;color:#64748b;width:24px;flex-shrink:0">${j.posicao}</span>
                <span style="font-size:11px;color:${cor};overflow:hidden;white-space:nowrap;text-overflow:ellipsis">${j.nome}</span>
            </div>`;
        }).join('');

        inEl.innerHTML = sortedIn.map(j => {
            const sel = j._i === mvSubIn;
            const bg  = sel ? 'rgba(34,197,94,0.15)' : 'rgba(15,23,42,0.8)';
            const brd = sel ? '#4ade80' : '#1e293b';
            const cor = sel ? '#4ade80' : '#cbd5e1';
            return `<div onclick="mvSelIn(${j._i})" style="cursor:pointer;padding:7px 10px;border-radius:8px;background:${bg};border:1px solid ${brd};display:flex;align-items:center;gap:6px;margin-bottom:3px">
                <span style="font-size:9px;color:#64748b;width:24px;flex-shrink:0">${j.posicao}</span>
                <span style="font-size:11px;color:${cor};overflow:hidden;white-space:nowrap;text-overflow:ellipsis">${j.nome}</span>
            </div>`;
        }).join('');

        document.getElementById('mv-sub-confirm').disabled = (mvSubOut === null || mvSubIn === null);
    }

    window.mvSelOut = function(i) { mvSubOut = i; mvRenderSubPanel(); };
    window.mvSelIn  = function(i) { mvSubIn  = i; mvRenderSubPanel(); };

    window.mvToggleSubs = function() {
        const panel = document.getElementById('mv-sub-panel');
        if (!panel.classList.contains('hidden')) {
            panel.classList.add('hidden');
            return;
        }
        if (!mvPaused) {
            mvPaused = true;
            document.getElementById('mv-play-icon').className = 'fas fa-play';
            document.getElementById('mv-play-label').textContent = 'Continuar';
            clearTimeout(mvTimer);
        }
        mvSubOut = null; mvSubIn = null;
        mvRenderSubPanel();
        panel.classList.remove('hidden');
    };

    window.mvConfirmSub = function() {
        if (mvSubOut === null || mvSubIn === null || mvSubCount >= 5) return;
        const d = mvData();
        const saindo  = mvTitulares[mvSubOut];
        const entrando = mvBanco[mvSubIn];
        if (!saindo || !entrando) return;

        entrando.posicao = saindo.posicao;
        if (!mvNotas[entrando.nome]) mvNotas[entrando.nome] = {nota:6.0,gols:0,assists:0,amarelos:0,vermelhos:0,posicao:saindo.posicao,titular:true,id:entrando.id||0};
        else { mvNotas[entrando.nome].titular = true; mvNotas[entrando.nome].posicao = saindo.posicao; }
        if (mvNotas[saindo.nome]) mvNotas[saindo.nome].titular = false;

        // Atualiza listas
        mvTitulares.splice(mvSubOut, 1, entrando);
        mvBanco.splice(mvSubIn, 1);
        mvSubCount++;

        document.getElementById('mv-subs-label').textContent = `Subs ${mvSubCount}/5`;
        document.getElementById('mv-sub-count').textContent = mvSubCount;

        // Adiciona evento de substituição no log
        if (d) mvAddEvento({tipo:'sub', minuto:mvMinuto, time:'', time_id:0, jogador:entrando.nome, saiu:saindo.nome}, d.nome_casa, d.nome_fora, d.meu_time_id);
        mvRenderNotas();

        mvSubOut = null; mvSubIn = null;
        if (mvSubCount >= 5) {
            document.getElementById('mv-sub-panel').classList.add('hidden');
            document.getElementById('mv-btn-subs').disabled = true;
        } else {
            mvRenderSubPanel();
        }
    };

    window.fecharViewer = function() {
        clearTimeout(mvTimer);
        document.getElementById('mv-sub-panel').classList.add('hidden');
        document.getElementById('mv-end-popup').classList.add('hidden');
        const v = document.getElementById('match-viewer');
        v.classList.add('hidden');
        v.classList.remove('flex');
    };

    window.mvTogglePlay = function() {
        mvPaused = !mvPaused;
        document.getElementById('mv-play-icon').className = mvPaused ? 'fas fa-play' : 'fas fa-pause';
        document.getElementById('mv-play-label').textContent = mvPaused ? 'Continuar' : 'Pausar';
        if (!mvPaused && mvMinuto < 90) mvTimer = setTimeout(mvTick, mvDelay);
    };

    window.mvSetSpeed = function(spd) {
        mvSpeed = spd; mvDelay = SPEEDS[spd] || 900;
        document.querySelectorAll('.mv-speed-btn').forEach(b => {
            const active = b.dataset.speed == spd;
            b.classList.toggle('bg-slate-600', active);
            b.classList.toggle('text-white', active);
            b.classList.toggle('bg-slate-800', !active);
            b.classList.toggle('text-slate-400', !active);
        });
    };

    window.mvInstant = function() {
        clearTimeout(mvTimer);
        for (let m = mvMinuto + 1; m <= 90; m++) {
            simMinuto(m).forEach(ev => mvProcessEvento(ev));
        }
        mvMinuto = 90;
        document.getElementById('mv-relogio').textContent = "90'";
        document.getElementById('mv-progress-fill').style.width = '100%';
        mvRenderNotas();
        mvEnd();
    };

    window.mvSubmitResult = function() {
        document.getElementById('form-resultado-partida').submit();
    };

    document.addEventListener('keydown', e => { if (e.key === 'Escape' && !document.getElementById('match-viewer').classList.contains('hidden')) fecharViewer(); });
})();
</script>
