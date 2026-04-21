<?php
$save        = $page_data['save']          ?? [];
$elenco      = $page_data['elenco']        ?? [];
$classificacao = $page_data['classificacao'] ?? [];
$partidas    = $page_data['partidas']      ?? [];
$financas    = $page_data['financas']      ?? [];
$mercado     = $page_data['mercado']       ?? [];
$time        = $page_data['time']          ?? [];
$proxima     = $page_data['proxima_partida'] ?? null;
$ultima      = $page_data['ultima_partida']  ?? null;

$nome_time  = $save['nome_time']      ?? 'Meu Time';
$treinador  = $save['nome_treinador'] ?? 'Treinador';
$temporada  = $save['temporada']      ?? 1;
$saldo      = $save['saldo']          ?? 0;
$rodada     = $save['rodada_atual']   ?? 1;
$slot       = $_SESSION['ecofut_save_slot'] ?? 1;
$timeId     = $save['time_id']        ?? 0;

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
            ['id'=>'dashboard',  'icon'=>'fa-house',         'label'=>'Início'],
            ['id'=>'elenco',     'icon'=>'fa-users',         'label'=>'Elenco'],
            ['id'=>'campeonato', 'icon'=>'fa-trophy',        'label'=>'Campeonato'],
            ['id'=>'jogar',      'icon'=>'fa-play',          'label'=>'Jogar'],
            ['id'=>'financas',   'icon'=>'fa-coins',         'label'=>'Finanças'],
            ['id'=>'mercado',    'icon'=>'fa-arrows-rotate', 'label'=>'Mercado'],
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

    <!-- Próxima partida + Últimas partidas -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Próxima partida -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <h3 class="text-sm font-bold text-white mb-4"><i class="fas fa-calendar-alt text-green-400 mr-2"></i>Próxima Partida</h3>
            <?php if ($proxima): ?>
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
<!-- ABA: ELENCO                                                               -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<section id="tab-elenco" class="tab-content">
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">

        <!-- Campo tático -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4">
            <h3 class="text-sm font-bold text-white mb-3"><i class="fas fa-table-cells text-green-400 mr-2"></i>Escalação Titular</h3>
            <div class="pitch-pattern rounded-xl relative" style="height:380px">
                <!-- Linhas do campo -->
                <div class="absolute inset-x-0 top-1/2 h-px bg-white/10"></div>
                <div class="absolute left-1/2 top-1/4 w-1/2 h-1/3 border border-white/10 -translate-x-1/2" style="left:50%;width:50%;top:35%;height:28%"></div>
                <div class="absolute" style="left:35%;top:62%;width:30%;height:22%;border:1px solid rgba(255,255,255,.1)"></div>
                <div class="absolute w-16 h-16 rounded-full border border-white/10" style="top:calc(50% - 2rem);left:calc(50% - 2rem)"></div>

                <?php
                $byPos = [];
                foreach ($titulares as $j) {
                    $byPos[$j['posicao']][] = $j;
                }
                // Posicionamento multi-jogador
                $posLayouts = [
                    'GOL' => [['bottom'=>'5%','left'=>'50%']],
                    'ZAG' => [['bottom'=>'20%','left'=>'35%'],['bottom'=>'20%','left'=>'65%'],['bottom'=>'20%','left'=>'50%']],
                    'LD'  => [['bottom'=>'20%','left'=>'82%']],
                    'LE'  => [['bottom'=>'20%','left'=>'18%']],
                    'VOL' => [['bottom'=>'38%','left'=>'50%'],['bottom'=>'38%','left'=>'30%'],['bottom'=>'38%','left'=>'70%']],
                    'MC'  => [['bottom'=>'50%','left'=>'35%'],['bottom'=>'50%','left'=>'65%']],
                    'MEI' => [['bottom'=>'58%','left'=>'50%']],
                    'PE'  => [['bottom'=>'70%','left'=>'18%'],['bottom'=>'70%','left'=>'30%']],
                    'PD'  => [['bottom'=>'70%','left'=>'82%'],['bottom'=>'70%','left'=>'70%']],
                    'ATA' => [['bottom'=>'76%','left'=>'35%'],['bottom'=>'76%','left'=>'65%'],['bottom'=>'82%','left'=>'50%'],['bottom'=>'70%','left'=>'50%']],
                ];
                foreach ($byPos as $pos => $jogs):
                    $layouts = $posLayouts[$pos] ?? [['bottom'=>'50%','left'=>'50%']];
                    foreach ($jogs as $idx => $j):
                        $layout = $layouts[min($idx, count($layouts)-1)];
                        $pCor   = posColor($pos);
                        $status = ($j['contundido'] ?? 0) ? '🤕' : (($j['suspenso'] ?? 0) ? '🟥' : '');
                        $energia = (int)($j['energia'] ?? 100);
                        $enCor = $energia >= 70 ? '#22c55e' : ($energia >= 40 ? '#eab308' : '#ef4444');
                ?>
                <div class="absolute transform -translate-x-1/2 -translate-y-1/2 text-center"
                     style="bottom:<?= $layout['bottom'] ?>;left:<?= $layout['left'] ?>;transform:translateX(-50%) translateY(50%)">
                    <div class="w-10 h-10 rounded-full border-2 flex items-center justify-center text-xs font-bold mx-auto mb-0.5"
                         style="border-color:<?= $pCor ?>;background:<?= $pCor ?>22;color:<?= $pCor ?>">
                        <?= posLabel($pos) ?>
                    </div>
                    <p class="text-[9px] text-white font-semibold leading-tight max-w-[52px] truncate"><?= explode(' ', $j['nome'])[1] ?? $j['nome'] ?></p>
                    <div class="w-8 mx-auto bg-slate-800 rounded-full h-1 mt-0.5">
                        <div class="h-1 rounded-full" style="width:<?= $energia ?>%;background:<?= $enCor ?>"></div>
                    </div>
                    <?php if ($status): ?><span class="text-[10px]"><?= $status ?></span><?php endif; ?>
                </div>
                <?php endforeach; endforeach; ?>
            </div>
        </div>

        <!-- Lista completa do elenco -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
            <div class="p-4 border-b border-slate-800 flex items-center justify-between">
                <h3 class="text-sm font-bold text-white"><i class="fas fa-users text-blue-400 mr-2"></i>Elenco Completo</h3>
                <span class="text-xs text-slate-500"><?= count($elenco) ?> jogadores</span>
            </div>
            <div class="overflow-y-auto" style="max-height:360px">
                <table class="w-full text-xs">
                    <thead class="sticky top-0 bg-slate-800">
                        <tr class="text-slate-400">
                            <th class="py-2 px-3 text-left">Jogador</th>
                            <th class="py-2 px-2 text-center">Pos</th>
                            <th class="py-2 px-2 text-center">OVR</th>
                            <th class="py-2 px-2 text-center">Idade</th>
                            <th class="py-2 px-2 text-center">Ene</th>
                            <th class="py-2 px-2 text-center">Mor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        <?php foreach ($elenco as $j):
                            $pCor    = posColor($j['posicao']);
                            $titular = (int)($j['titular'] ?? 0);
                            $status  = ($j['contundido'] ?? 0) ? '<i class="fas fa-bandage text-red-400" title="Contundido"></i>' : (($j['suspenso'] ?? 0) ? '<i class="fas fa-card-club text-yellow-400" title="Suspenso"></i>' : '');
                        ?>
                        <tr class="hover:bg-slate-800/50 <?= $titular ? '' : 'opacity-70' ?>">
                            <td class="py-2 px-3 font-medium text-white truncate max-w-[120px]">
                                <?= $status ?> <?= htmlspecialchars($j['nome']) ?>
                                <?php if ($titular): ?><span class="text-[9px] text-green-400 font-bold ml-0.5">T</span><?php endif; ?>
                            </td>
                            <td class="py-2 px-2 text-center">
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded" style="color:<?= $pCor ?>;background:<?= $pCor ?>22"><?= posLabel($j['posicao']) ?></span>
                            </td>
                            <td class="py-2 px-2 text-center font-bold text-white"><?= $j['forca'] ?></td>
                            <td class="py-2 px-2 text-center text-slate-400"><?= $j['idade'] ?></td>
                            <td class="py-2 px-2 text-center">
                                <?php $e = (int)($j['energia']??100); $ec = $e>=70?'text-green-400':($e>=40?'text-yellow-400':'text-red-400'); ?>
                                <span class="<?= $ec ?>"><?= $e ?></span>
                            </td>
                            <td class="py-2 px-2 text-center">
                                <?php $m = (int)($j['moral']??75); $mc = $m>=70?'text-green-400':($m>=50?'text-yellow-400':'text-red-400'); ?>
                                <span class="<?= $mc ?>"><?= $m ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
</section>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- ABA: JOGAR                                                                -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<section id="tab-jogar" class="tab-content">
    <div class="max-w-2xl mx-auto">

        <?php if (isset($page_data['log_partida'])): $logP = $page_data['log_partida']; ?>
        <!-- Resultado última partida simulada -->
        <div class="bg-slate-900 border border-slate-700 rounded-2xl p-6 mb-5">
            <p class="text-xs text-slate-500 text-center mb-4">Resultado — Rodada <?= $logP['rodada'] ?></p>
            <div class="flex items-center justify-center gap-6 mb-6">
                <div class="text-center">
                    <p class="text-sm font-bold text-slate-300 mb-1"><?= htmlspecialchars($logP['nome_casa']) ?></p>
                    <p class="text-5xl font-black text-white"><?= $logP['gols_casa'] ?></p>
                </div>
                <div class="text-2xl text-slate-600 font-bold">–</div>
                <div class="text-center">
                    <p class="text-sm font-bold text-slate-300 mb-1"><?= htmlspecialchars($logP['nome_fora']) ?></p>
                    <p class="text-5xl font-black text-white"><?= $logP['gols_fora'] ?></p>
                </div>
            </div>

            <!-- Log de eventos -->
            <?php if (!empty($logP['eventos'])): ?>
            <div class="border-t border-slate-800 pt-4 space-y-1.5 max-h-60 overflow-y-auto">
                <?php foreach ($logP['eventos'] as $ev):
                    $meuEv = (int)$ev['time_id'] === $timeId;
                    if ($ev['tipo'] === 'gol'): ?>
                    <div class="flex items-center gap-2 text-xs">
                        <span class="text-slate-500 w-6 text-right flex-shrink-0"><?= $ev['minuto'] ?>'</span>
                        <i class="fas fa-futbol text-<?= $meuEv ? 'green' : 'red' ?>-400 flex-shrink-0"></i>
                        <span class="<?= $meuEv ? 'text-green-400 font-semibold' : 'text-slate-400' ?>"><?= htmlspecialchars($ev['jogador']) ?></span>
                        <span class="text-slate-600 text-[10px]">(<?= $ev['time'] === 'casa' ? htmlspecialchars($logP['nome_casa']) : htmlspecialchars($logP['nome_fora']) ?>)</span>
                    </div>
                    <?php elseif ($ev['tipo'] === 'amarelo'): ?>
                    <div class="flex items-center gap-2 text-xs">
                        <span class="text-slate-500 w-6 text-right flex-shrink-0"><?= $ev['minuto'] ?>'</span>
                        <span class="w-3 h-4 bg-yellow-400 rounded-sm flex-shrink-0 inline-block"></span>
                        <span class="text-slate-400"><?= htmlspecialchars($ev['jogador']) ?></span>
                    </div>
                    <?php elseif ($ev['tipo'] === 'vermelho'): ?>
                    <div class="flex items-center gap-2 text-xs">
                        <span class="text-slate-500 w-6 text-right flex-shrink-0"><?= $ev['minuto'] ?>'</span>
                        <span class="w-3 h-4 bg-red-500 rounded-sm flex-shrink-0 inline-block"></span>
                        <span class="text-slate-400"><?= htmlspecialchars($ev['jogador']) ?></span>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Painel de ação -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
            <h3 class="text-lg font-bold text-white mb-2">Próxima Rodada</h3>
            <?php if ($proxima): ?>
            <div class="bg-slate-800 rounded-xl p-4 mb-5 text-center">
                <p class="text-xs text-slate-500 mb-2">Rodada <?= $proxima['rodada'] ?></p>
                <div class="flex items-center justify-center gap-4">
                    <span class="font-bold text-white"><?= htmlspecialchars($proxima['nome_casa']) ?></span>
                    <span class="text-slate-600 font-bold">vs</span>
                    <span class="font-bold text-white"><?= htmlspecialchars($proxima['nome_fora']) ?></span>
                </div>
                <?php if ((int)$proxima['time_casa_id'] === $timeId): ?>
                <p class="text-xs text-green-400 mt-1">Você joga em casa</p>
                <?php elseif ((int)$proxima['time_fora_id'] === $timeId): ?>
                <p class="text-xs text-yellow-400 mt-1">Você joga fora</p>
                <?php endif; ?>
            </div>
            <form method="POST" action="?page=app">
                <input type="hidden" name="action" value="avancar_rodada">
                <button class="w-full bg-green-600 hover:bg-green-500 text-white font-bold py-3 rounded-xl transition shadow-lg shadow-green-500/20 text-base">
                    <i class="fas fa-play mr-2"></i> Simular Rodada <?= $rodada ?>
                </button>
            </form>
            <p class="text-xs text-slate-500 text-center mt-3">Todos os jogos da rodada serão simulados automaticamente</p>
            <?php elseif ($rodada > 38): ?>
            <div class="text-center py-8">
                <i class="fas fa-trophy text-yellow-400 text-4xl mb-3"></i>
                <p class="text-white font-bold text-lg">Temporada Encerrada!</p>
                <p class="text-slate-400 text-sm mt-1">Veja sua classificação final na aba Campeonato.</p>
            </div>
            <?php else: ?>
            <p class="text-slate-500 text-center py-4">Nenhuma partida agendada para esta rodada.</p>
            <?php endif; ?>
        </div>
    </div>
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
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        <!-- Jogadores disponíveis para compra -->
        <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
            <div class="p-4 border-b border-slate-800 flex items-center justify-between">
                <h3 class="text-sm font-bold text-white"><i class="fas fa-store text-yellow-400 mr-2"></i>Jogadores Disponíveis</h3>
                <span class="text-xs text-slate-500"><?= count($mercado) ?> no mercado</span>
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
                            $preco  = (int)(($j['forca'] ** 2 / 100) * 1200 + 20000);
                            $pCor   = posColor($j['posicao']);
                            $podeCom = $saldo >= $preco;
                        ?>
                        <tr class="hover:bg-slate-800/50">
                            <td class="py-2.5 px-3 font-medium text-white"><?= htmlspecialchars($j['nome']) ?></td>
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
                                    <input type="hidden" name="preco" value="<?= $preco ?>">
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
                    $venda = (int)(($j['forca'] ** 2 / 100) * 900 + 10000);
                ?>
                <div class="flex items-center gap-2 px-3 py-2.5 border-b border-slate-800 hover:bg-slate-800/40">
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-white truncate"><?= htmlspecialchars($j['nome']) ?></p>
                        <p class="text-[10px] text-slate-500"><?= posLabel($j['posicao']) ?> · OVR <?= $j['forca'] ?></p>
                    </div>
                    <span class="text-xs text-green-400 font-semibold flex-shrink-0"><?= appSaldo($venda) ?></span>
                    <form method="POST" action="?page=app">
                        <input type="hidden" name="action" value="vender_jogador">
                        <input type="hidden" name="elenco_id" value="<?= $j['id'] ?>">
                        <input type="hidden" name="preco" value="<?= $venda ?>">
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

<?php if (isset($page_data['log_partida'])): ?>
// Abre aba Jogar se há resultado fresco
trocarAba('jogar');
<?php endif; ?>
</script>
