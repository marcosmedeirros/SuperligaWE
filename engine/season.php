<?php
/**
 * Motor de temporada — EcoFut
 * Suporta Série A (div=1) + Série B (div=2) + Copa do Brasil (5 fases).
 */

require_once __DIR__ . '/../engine/match.php';

// ── Inicialização de temporada ────────────────────────────────────────────────

function inicializarTemporada(PDO $pdo, int $saveId, int $timeUsuarioId): void
{
    // 1. Elenco do usuário
    $jogBase = $pdo->prepare("SELECT * FROM ecofut_jogadores_base WHERE time_id = ?");
    $jogBase->execute([$timeUsuarioId]);
    $jogadores = $jogBase->fetchAll(PDO::FETCH_ASSOC);

    $insJog = $pdo->prepare(
        "INSERT INTO ecofut_elenco
         (save_id, time_id, jogador_base_id, nome, posicao, forca, potencial, idade, energia, moral,
          contundido, suspenso, salario, meses_contrato, titular,
          sk_goleiro, sk_agilidade, sk_passe, sk_armacao, sk_desarme, sk_finalizacao, sk_tecnica)
         VALUES (?,?,?,?,?,?,?,?,100,75,0,0,?,12,?,?,?,?,?,?,?,?)"
    );

    $posIdx         = array_fill_keys(['GOL','ZAG','LD','LE','VOL','MC','MEI','PE','PD','ATA'], 0);
    $titularLimites = ['GOL'=>1,'ZAG'=>2,'LD'=>1,'LE'=>1,'VOL'=>2,'MC'=>2,'MEI'=>1,'PE'=>1,'PD'=>1,'ATA'=>2];

    foreach ($jogadores as $j) {
        $pos     = $j['posicao'];
        $limite  = $titularLimites[$pos] ?? 1;
        $titular = ($posIdx[$pos] ?? 0) < $limite ? 1 : 0;
        if (isset($posIdx[$pos])) $posIdx[$pos]++;

        $idade     = (int)$j['idade'];
        $forca     = (int)$j['forca'];
        $potencial = $idade <= 20 ? min(99, $forca + rand(8, 20))
                   : ($idade <= 23 ? min(99, $forca + rand(3, 10))
                   : $forca);

        $insJog->execute([
            $saveId, $timeUsuarioId, $j['id'], $j['nome'], $j['posicao'], $forca, $potencial,
            $j['idade'], $j['salario'], $titular,
            $j['sk_goleiro'], $j['sk_agilidade'], $j['sk_passe'],
            $j['sk_armacao'], $j['sk_desarme'], $j['sk_finalizacao'], $j['sk_tecnica'],
        ]);
    }

    // 2. Classificação por divisão
    $timesA = $pdo->query("SELECT id FROM ecofut_times WHERE divisao = 1 ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
    $timesB = $pdo->query("SELECT id FROM ecofut_times WHERE divisao = 2 ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);

    $insClass = $pdo->prepare("INSERT IGNORE INTO ecofut_classificacao (save_id, time_id, divisao) VALUES (?,?,?)");
    foreach ($timesA as $tid) $insClass->execute([$saveId, $tid, 1]);
    foreach ($timesB as $tid) $insClass->execute([$saveId, $tid, 2]);

    // 3. Fixtures Série A + B
    gerarFixtures($pdo, $saveId, $timesA, 1);
    gerarFixtures($pdo, $saveId, $timesB, 2);

    // 4. Copa do Brasil (32 times, 5 fases)
    inicializarCopaDoBrasil($pdo, $saveId, $timeUsuarioId);

    // 5. Patrocínio inicial
    $pdo->prepare(
        "INSERT INTO ecofut_financas (save_id, categoria, descricao, valor)
         VALUES (?, 'patrocinio', 'Patrocínio inicial da temporada', 500000)"
    )->execute([$saveId]);
}

// ── Geração de fixtures round-robin ──────────────────────────────────────────

function gerarFixtures(PDO $pdo, int $saveId, array $times, int $divisao): void
{
    $n = count($times);
    if ($n < 2) return;

    $ids = $times;
    if ($n % 2 !== 0) $ids[] = null;
    $m    = count($ids);
    $fixo = $ids[0];
    $rot  = array_slice($ids, 1);

    $jogos   = [];
    $nRodadas = $m - 1;

    for ($r = 0; $r < $nRodadas; $r++) {
        $atual = array_merge([$fixo], $rot);
        $meio  = $m / 2;
        for ($i = 0; $i < $meio; $i++) {
            $a = $atual[$i];
            $b = $atual[$m - 1 - $i];
            if ($a !== null && $b !== null) $jogos[] = [$r + 1, $a, $b];
        }
        $ultimo = array_pop($rot);
        array_unshift($rot, $ultimo);
    }

    $volta = [];
    foreach ($jogos as [$rod, $casa, $fora]) {
        $volta[] = [$rod + $nRodadas, $fora, $casa];
    }
    $jogos = array_merge($jogos, $volta);

    $ins = $pdo->prepare(
        "INSERT INTO ecofut_partidas (save_id, rodada, divisao, time_casa_id, time_fora_id, status)
         VALUES (?,?,?,?,?,'agendada')"
    );
    foreach ($jogos as [$rod, $casa, $fora]) {
        $ins->execute([$saveId, $rod, $divisao, $casa, $fora]);
    }
}

// ── Copa do Brasil ────────────────────────────────────────────────────────────

function inicializarCopaDoBrasil(PDO $pdo, int $saveId, int $timeId): void
{
    $pdo->prepare("DELETE FROM ecofut_copa WHERE save_id = ?")->execute([$saveId]);

    // 32 times: todos da Série B + 12 da Série A (os mais fracos), garantindo o time do usuário
    $timesB = $pdo->query("SELECT id FROM ecofut_times WHERE divisao = 2")->fetchAll(PDO::FETCH_COLUMN);
    $timesA = $pdo->query("SELECT id FROM ecofut_times WHERE divisao = 1 ORDER BY forca_base ASC LIMIT 12")->fetchAll(PDO::FETCH_COLUMN);

    $pool = array_unique(array_merge($timesB, $timesA));

    // Garante time do usuário
    if (!in_array($timeId, $pool)) {
        array_pop($pool);
        $pool[] = $timeId;
    }

    shuffle($pool);
    $participantes = array_slice($pool, 0, 32);

    // Garante time do usuário nos 32
    if (!in_array($timeId, $participantes)) {
        $participantes[0] = $timeId;
        shuffle($participantes);
    }

    // Cria 16 jogos da primeira fase
    $ins = $pdo->prepare(
        "INSERT INTO ecofut_copa (save_id, fase, time_casa_id, time_fora_id) VALUES (?, 'primeira_fase', ?, ?)"
    );
    for ($i = 0; $i + 1 < count($participantes); $i += 2) {
        $ins->execute([$saveId, $participantes[$i], $participantes[$i + 1]]);
    }
}

/**
 * Simula uma fase da Copa quando a rodada trigger é completada.
 * Triggers: rodada 6→primeira_fase, 12→oitavas, 20→quartas, 28→semifinal, 36→final
 */
function processarCopa(PDO $pdo, int $saveId, int $timeId, int $rodadaCompleta): void
{
    $triggers = [
        6  => 'primeira_fase',
        12 => 'oitavas',
        20 => 'quartas',
        28 => 'semifinal',
        36 => 'final',
    ];
    if (!isset($triggers[$rodadaCompleta])) return;
    $fase = $triggers[$rodadaCompleta];

    $pendentes = $pdo->prepare(
        "SELECT COUNT(*) FROM ecofut_copa WHERE save_id = ? AND fase = ? AND status = 'agendada'"
    );
    $pendentes->execute([$saveId, $fase]);
    if ((int)$pendentes->fetchColumn() === 0) return;

    $copaStmt = $pdo->prepare(
        "SELECT * FROM ecofut_copa WHERE save_id = ? AND fase = ? AND status = 'agendada'"
    );
    $copaStmt->execute([$saveId, $fase]);
    $partidas = $copaStmt->fetchAll(PDO::FETCH_ASSOC);

    $userJogadores = $pdo->prepare("SELECT * FROM ecofut_elenco WHERE save_id = ?");
    $userJogadores->execute([$saveId]);
    $userForca = (int)calcularForcaElenco($userJogadores->fetchAll(PDO::FETCH_ASSOC), 65);

    $vencedores = [];
    foreach ($partidas as $p) {
        $fCasa = obterForcaTimeBase($pdo, (int)$p['time_casa_id']);
        $fFora = obterForcaTimeBase($pdo, (int)$p['time_fora_id']);
        if ((int)$p['time_casa_id'] === $timeId) $fCasa = $userForca;
        if ((int)$p['time_fora_id'] === $timeId) $fFora = $userForca;

        $dadosCasa = ['nome'=>'Casa','forca'=>$fCasa,'tatica'=>TAT_EQUILIBRADO,'jogadores'=>[],'id'=>(int)$p['time_casa_id']];
        $dadosFora = ['nome'=>'Fora','forca'=>$fFora,'tatica'=>TAT_EQUILIBRADO,'jogadores'=>[],'id'=>(int)$p['time_fora_id']];
        $res = simularPartida($dadosCasa, $dadosFora);
        $gC  = $res['gols_casa'];
        $gF  = $res['gols_fora'];

        $vencedorId = $gC !== $gF
            ? ($gC > $gF ? (int)$p['time_casa_id'] : (int)$p['time_fora_id'])
            : (rand(0,1) === 0 ? (int)$p['time_casa_id'] : (int)$p['time_fora_id']); // pênaltis

        $vencedores[] = $vencedorId;
        $pdo->prepare("UPDATE ecofut_copa SET gols_casa=?,gols_fora=?,status='jogada' WHERE id=?")
            ->execute([$gC, $gF, $p['id']]);
    }

    // Avança para próxima fase
    $proximaFase = [
        'primeira_fase' => 'oitavas',
        'oitavas'       => 'quartas',
        'quartas'       => 'semifinal',
        'semifinal'     => 'final',
    ][$fase] ?? null;

    if ($proximaFase && count($vencedores) >= 2) {
        $ins = $pdo->prepare(
            "INSERT INTO ecofut_copa (save_id, fase, time_casa_id, time_fora_id) VALUES (?, ?, ?, ?)"
        );
        for ($i = 0; $i + 1 < count($vencedores); $i += 2) {
            $ins->execute([$saveId, $proximaFase, $vencedores[$i], $vencedores[$i + 1]]);
        }
    }

    // Prêmios Copa do Brasil
    $premios = [
        'semifinal' => ['valor' => 750000,  'desc' => 'Copa do Brasil — Semifinal'],
        'final'     => ['vencedor' => 4000000, 'vice' => 1500000],
    ];

    if ($fase === 'semifinal') {
        // Premiação para perdedores da semi
        $semis = $pdo->prepare("SELECT * FROM ecofut_copa WHERE save_id = ? AND fase = 'semifinal'");
        $semis->execute([$saveId]);
        foreach ($semis->fetchAll(PDO::FETCH_ASSOC) as $m) {
            $perdedor = in_array((int)$m['time_casa_id'], $vencedores)
                ? (int)$m['time_fora_id'] : (int)$m['time_casa_id'];
            if ($perdedor === $timeId) {
                $pdo->prepare("UPDATE ecofut_saves SET saldo = saldo + 750000 WHERE id = ?")->execute([$saveId]);
                $pdo->prepare("INSERT INTO ecofut_financas (save_id,categoria,descricao,valor) VALUES (?,'copa','Copa do Brasil — Semifinal',750000)")->execute([$saveId]);
            }
        }
    }

    if ($fase === 'final' && !empty($vencedores)) {
        $fin = $pdo->prepare("SELECT * FROM ecofut_copa WHERE save_id = ? AND fase = 'final' ORDER BY id DESC LIMIT 1");
        $fin->execute([$saveId]);
        $finalJogo = $fin->fetch(PDO::FETCH_ASSOC);
        if ($finalJogo) {
            $emFinal = ((int)$finalJogo['time_casa_id'] === $timeId || (int)$finalJogo['time_fora_id'] === $timeId);
            if ($vencedores[0] === $timeId) {
                $pdo->prepare("UPDATE ecofut_saves SET saldo = saldo + 4000000 WHERE id = ?")->execute([$saveId]);
                $pdo->prepare("INSERT INTO ecofut_financas (save_id,categoria,descricao,valor) VALUES (?,'copa','Copa do Brasil — CAMPEÃO!',4000000)")->execute([$saveId]);
            } elseif ($emFinal) {
                $pdo->prepare("UPDATE ecofut_saves SET saldo = saldo + 1500000 WHERE id = ?")->execute([$saveId]);
                $pdo->prepare("INSERT INTO ecofut_financas (save_id,categoria,descricao,valor) VALUES (?,'copa','Copa do Brasil — Vice-campeão',1500000)")->execute([$saveId]);
            }
        }
    }
}

function obterForcaTimeBase(PDO $pdo, int $timeId): int
{
    $q = $pdo->prepare("SELECT forca_base FROM ecofut_times WHERE id = ?");
    $q->execute([$timeId]);
    return (int)($q->fetchColumn() ?: 65);
}

// ── Avançar rodada ────────────────────────────────────────────────────────────

function avancarRodada(PDO $pdo, int $saveId): array
{
    $saveQ = $pdo->prepare("SELECT * FROM ecofut_saves WHERE id = ?");
    $saveQ->execute([$saveId]);
    $saveData = $saveQ->fetch(PDO::FETCH_ASSOC);
    if (!$saveData) return [];

    $rodada        = (int)$saveData['rodada_atual'];
    $timeUsuarioId = (int)$saveData['time_id'];
    $dadosJson     = json_decode($saveData['dados_json'] ?? '{}', true) ?: [];
    $taticaUser    = $dadosJson['tatica'] ?? 'normal';

    // Busca partidas de ambas as divisões na rodada atual
    $stmt = $pdo->prepare(
        "SELECT * FROM ecofut_partidas WHERE save_id = ? AND rodada = ? AND status = 'agendada'"
    );
    $stmt->execute([$saveId, $rodada]);
    $partidas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($partidas)) {
        return ['rodada'=>$rodada,'resultados'=>[],'match_setup'=>null,'time_usuario_id'=>$timeUsuarioId];
    }

    $resultados = [];
    $matchSetup = null;

    foreach ($partidas as $p) {
        $ehUsuario = ((int)$p['time_casa_id'] === $timeUsuarioId || (int)$p['time_fora_id'] === $timeUsuarioId);
        $dadosCasa = obterDadosTimeParaSimulacao($pdo, $saveId, (int)$p['time_casa_id'], $timeUsuarioId);
        $dadosFora = obterDadosTimeParaSimulacao($pdo, $saveId, (int)$p['time_fora_id'], $timeUsuarioId);

        if ($ehUsuario) {
            $ehCasa  = (int)$p['time_casa_id'] === $timeUsuarioId;
            $jogMeu  = $ehCasa ? $dadosCasa['jogadores'] : $dadosFora['jogadores'];
            $jogAdv  = $ehCasa ? $dadosFora['jogadores'] : $dadosCasa['jogadores'];
            $mapJog  = fn($arr) => array_values(array_map(fn($j) => [
                'id'            => (int)($j['id'] ?? 0),
                'nome'          => $j['nome'],
                'posicao'       => $j['posicao'],
                'titular'       => (int)($j['titular'] ?? 0),
                'sk_finalizacao'=> (int)($j['sk_finalizacao'] ?? 50),
                'sk_armacao'    => (int)($j['sk_armacao'] ?? 50),
                'contundido'    => (int)($j['contundido'] ?? 0),
                'suspenso'      => (int)($j['suspenso'] ?? 0),
            ], $arr));
            $matchSetup = [
                'partida_id'       => (int)$p['id'],
                'divisao'          => (int)$p['divisao'],
                'rodada'           => $rodada,
                'nome_casa'        => $dadosCasa['nome'],
                'nome_fora'        => $dadosFora['nome'],
                'time_casa_id'     => (int)$p['time_casa_id'],
                'time_fora_id'     => (int)$p['time_fora_id'],
                'meu_time_id'      => $timeUsuarioId,
                'eh_casa'          => $ehCasa,
                'forca_meu'        => round(calcularForcaElenco($jogMeu, 65), 2),
                'forca_adv'        => round(calcularForcaElenco($jogAdv, 65), 2),
                'tatica_inicial'   => $taticaUser,
                'formacao_inicial' => $dadosJson['formacao'] ?? '4-3-3',
                'jogadores_meu'    => $mapJog($jogMeu),
                'jogadores_adv'    => $mapJog($jogAdv),
            ];
            continue;
        }

        $res = simularPartida($dadosCasa, $dadosFora);
        $pdo->prepare("UPDATE ecofut_partidas SET gols_casa=?,gols_fora=?,status='jogada',log_json=? WHERE id=?")
            ->execute([$res['gols_casa'], $res['gols_fora'], json_encode($res['log']), $p['id']]);
        atualizarClassificacao($pdo, $saveId, (int)$p['time_casa_id'], (int)$p['time_fora_id'],
            $res['gols_casa'], $res['gols_fora'], (int)$p['divisao']);
        $resultados[] = [
            'partida_id'   => (int)$p['id'],
            'divisao'      => (int)$p['divisao'],
            'time_casa_id' => (int)$p['time_casa_id'],
            'time_fora_id' => (int)$p['time_fora_id'],
            'gols_casa'    => $res['gols_casa'],
            'gols_fora'    => $res['gols_fora'],
        ];
    }

    if ($matchSetup === null) {
        recuperarEnergiaBancoLesionados($pdo, $saveId);
        if ($rodada % 4 === 0) processarSalarios($pdo, $saveId);

        foreach ($resultados as $r) {
            if ((int)$r['time_casa_id'] === $timeUsuarioId) {
                $b = rand(80000, 350000);
                $pdo->prepare("INSERT INTO ecofut_financas(save_id,categoria,descricao,valor) VALUES(?,'bilheteria','Bilheteria rodada $rodada',?)")->execute([$saveId, $b]);
                $pdo->prepare("UPDATE ecofut_saves SET saldo=saldo+? WHERE id=?")->execute([$b, $saveId]);
            }
        }

        $pdo->prepare("UPDATE ecofut_saves SET rodada_atual=? WHERE id=?")->execute([$rodada + 1, $saveId]);
        processarCopa($pdo, $saveId, $timeUsuarioId, $rodada);
        if ($rodada >= 38) processarFimTemporada($pdo, $saveId);
    }

    return [
        'rodada'          => $rodada,
        'resultados'      => $resultados,
        'match_setup'     => $matchSetup,
        'time_usuario_id' => $timeUsuarioId,
    ];
}

// ── Finalizar partida do usuário ──────────────────────────────────────────────

function finalizarPartidaUsuario(PDO $pdo, int $saveId, int $partidaId, int $golsCasa, int $golsFora): array
{
    $saveQ = $pdo->prepare("SELECT * FROM ecofut_saves WHERE id=?");
    $saveQ->execute([$saveId]);
    $saveData = $saveQ->fetch(PDO::FETCH_ASSOC);
    if (!$saveData) return ['ok' => false];

    $rodada        = (int)$saveData['rodada_atual'];
    $timeUsuarioId = (int)$saveData['time_id'];

    $pQ = $pdo->prepare("SELECT * FROM ecofut_partidas WHERE id=? AND save_id=?");
    $pQ->execute([$partidaId, $saveId]);
    $partida = $pQ->fetch(PDO::FETCH_ASSOC);
    if (!$partida || $partida['status'] === 'jogada') return ['ok' => false];

    $pdo->prepare("UPDATE ecofut_partidas SET gols_casa=?,gols_fora=?,status='jogada' WHERE id=? AND save_id=?")
        ->execute([$golsCasa, $golsFora, $partidaId, $saveId]);
    atualizarClassificacao($pdo, $saveId, (int)$partida['time_casa_id'], (int)$partida['time_fora_id'],
        $golsCasa, $golsFora, (int)$partida['divisao']);
    aplicarDesgastePosJogo($pdo, $saveId, [
        'gols_casa' => $golsCasa, 'gols_fora' => $golsFora,
        'time_casa_id' => (int)$partida['time_casa_id'], 'time_fora_id' => (int)$partida['time_fora_id'],
    ], $timeUsuarioId);
    recuperarEnergiaBancoLesionados($pdo, $saveId);
    if ($rodada % 4 === 0) processarSalarios($pdo, $saveId);

    if ((int)$partida['time_casa_id'] === $timeUsuarioId) {
        $b = rand(80000, 350000);
        $pdo->prepare("INSERT INTO ecofut_financas(save_id,categoria,descricao,valor) VALUES(?,'bilheteria','Bilheteria rodada $rodada',?)")->execute([$saveId, $b]);
        $pdo->prepare("UPDATE ecofut_saves SET saldo=saldo+? WHERE id=?")->execute([$b, $saveId]);
    }

    $pdo->prepare("UPDATE ecofut_saves SET rodada_atual=? WHERE id=?")->execute([$rodada + 1, $saveId]);
    processarCopa($pdo, $saveId, $timeUsuarioId, $rodada);
    if ($rodada >= 38) processarFimTemporada($pdo, $saveId);

    return ['ok' => true, 'rodada' => $rodada, 'time_usuario_id' => $timeUsuarioId];
}

// ── Helpers internos ──────────────────────────────────────────────────────────

function obterDadosTimeParaSimulacao(PDO $pdo, int $saveId, int $timeId, int $timeUsuarioId): array
{
    $stmt = $pdo->prepare("SELECT nome, forca_base FROM ecofut_times WHERE id = ?");
    $stmt->execute([$timeId]);
    $time = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($timeId === $timeUsuarioId) {
        $jStmt = $pdo->prepare("SELECT * FROM ecofut_elenco WHERE save_id = ? AND time_id = ?");
        $jStmt->execute([$saveId, $timeId]);
        $jogadores = $jStmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $jStmt = $pdo->prepare(
            "SELECT *, 1 as titular, 100 as energia, 75 as moral, 0 as contundido, 0 as suspenso
             FROM ecofut_jogadores_base WHERE time_id = ? LIMIT 25"
        );
        $jStmt->execute([$timeId]);
        $jogadores = $jStmt->fetchAll(PDO::FETCH_ASSOC);
        for ($i = 0; $i < count($jogadores); $i++) {
            $jogadores[$i]['titular'] = $i < 11 ? 1 : 0;
        }
    }

    return [
        'id'        => $timeId,
        'nome'      => $time['nome']       ?? 'Time',
        'forca'     => $time['forca_base'] ?? 65,
        'tatica'    => TAT_EQUILIBRADO,
        'jogadores' => $jogadores,
    ];
}

function atualizarClassificacao(
    PDO $pdo, int $saveId,
    int $casaId, int $foraId,
    int $gCasa, int $gFora,
    int $divisao = 1
): void {
    $upd = $pdo->prepare(
        "UPDATE ecofut_classificacao
         SET jogos       = jogos + 1,
             vitorias    = vitorias + ?,
             empates     = empates  + ?,
             derrotas    = derrotas + ?,
             gols_pro    = gols_pro + ?,
             gols_contra = gols_contra + ?,
             pontos      = pontos + ?
         WHERE save_id = ? AND time_id = ? AND divisao = ?"
    );

    $vC = $gCasa > $gFora ? 1 : 0; $eC = $gCasa === $gFora ? 1 : 0; $dC = $gCasa < $gFora ? 1 : 0;
    $pC = $gCasa > $gFora ? 3 : ($gCasa === $gFora ? 1 : 0);
    $upd->execute([$vC, $eC, $dC, $gCasa, $gFora, $pC, $saveId, $casaId, $divisao]);

    $vF = $gFora > $gCasa ? 1 : 0; $eF = $gCasa === $gFora ? 1 : 0; $dF = $gFora < $gCasa ? 1 : 0;
    $pF = $gFora > $gCasa ? 3 : ($gCasa === $gFora ? 1 : 0);
    $upd->execute([$vF, $eF, $dF, $gFora, $gCasa, $pF, $saveId, $foraId, $divisao]);
}

function processarSalarios(PDO $pdo, int $saveId): void
{
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(salario),0) FROM ecofut_elenco WHERE save_id = ?");
    $stmt->execute([$saveId]);
    $total = (int)$stmt->fetchColumn();
    if ($total > 0) {
        $pdo->prepare("UPDATE ecofut_saves SET saldo = saldo - ? WHERE id = ?")->execute([$total, $saveId]);
        $pdo->prepare("INSERT INTO ecofut_financas (save_id,categoria,descricao,valor) VALUES (?,'salarios','Pagamento de salários',?)")
            ->execute([$saveId, -$total]);
    }
}

// ── Fim de temporada: promoção, rebaixamento, nova temporada ──────────────────

function processarFimTemporada(PDO $pdo, int $saveId): void
{
    $saveQ = $pdo->prepare("SELECT * FROM ecofut_saves WHERE id = ?");
    $saveQ->execute([$saveId]);
    $saveData = $saveQ->fetch(PDO::FETCH_ASSOC);
    if (!$saveData) return;

    $timeId    = (int)$saveData['time_id'];
    $temporada = (int)$saveData['temporada'];
    $dadosJson = json_decode($saveData['dados_json'] ?? '{}', true) ?: [];

    // ── Classificação final Série A ───────────────────────────────────────────
    $classA = $pdo->prepare(
        "SELECT time_id FROM ecofut_classificacao
         WHERE save_id = ? AND divisao = 1
         ORDER BY pontos DESC, (gols_pro - gols_contra) DESC, gols_pro DESC"
    );
    $classA->execute([$saveId]);
    $ordemA = $classA->fetchAll(PDO::FETCH_COLUMN);

    // ── Classificação final Série B ───────────────────────────────────────────
    $classB = $pdo->prepare(
        "SELECT time_id FROM ecofut_classificacao
         WHERE save_id = ? AND divisao = 2
         ORDER BY pontos DESC, (gols_pro - gols_contra) DESC, gols_pro DESC"
    );
    $classB->execute([$saveId]);
    $ordemB = $classB->fetchAll(PDO::FETCH_COLUMN);

    // ── Promoção e rebaixamento ───────────────────────────────────────────────
    $rebaixadosA  = array_slice($ordemA, -4); // últimos 4 da A → vão para B
    $promovidos   = array_slice($ordemB, 0, 4); // top 4 da B → vão para A

    $updDiv = $pdo->prepare("UPDATE ecofut_times SET divisao = ? WHERE id = ?");
    foreach ($rebaixadosA as $tid) {
        $updDiv->execute([2, $tid]);
        $pdo->prepare("UPDATE IGNORE ecofut_classificacao SET divisao = 2 WHERE save_id=? AND time_id=? AND divisao=1")->execute([$saveId, $tid]);
    }
    foreach ($promovidos as $tid) {
        $updDiv->execute([1, $tid]);
        $pdo->prepare("UPDATE IGNORE ecofut_classificacao SET divisao = 1 WHERE save_id=? AND time_id=? AND divisao=2")->execute([$saveId, $tid]);
    }

    // ── Posição do usuário ────────────────────────────────────────────────────
    $userDiv = (int)($pdo->prepare("SELECT divisao FROM ecofut_times WHERE id=?")->execute([$timeId]) ? 1 : 1);
    $userDivQ = $pdo->prepare("SELECT divisao FROM ecofut_times WHERE id=?");
    $userDivQ->execute([$timeId]);
    $userDiv = (int)$userDivQ->fetchColumn();

    // Busca posição antes da promoção/rebaixamento
    $posA = array_search($timeId, $ordemA);
    $posB = array_search($timeId, $ordemB);
    $posUsuario  = $posA !== false ? (int)$posA + 1 : ($posB !== false ? (int)$posB + 1 : 20);
    $divisaoUser = $posA !== false ? 1 : 2;

    $rebaixado   = in_array($timeId, $rebaixadosA);
    $promovido   = in_array($timeId, $promovidos);
    $campeao     = ($posA === 0 || $posB === 0);
    $continental = ($posA !== false && (int)$posA < 6); // top 6 da Série A = Libertadores/Sul-Americana

    // ── Prêmio por classificação ──────────────────────────────────────────────
    $fator  = $divisaoUser === 1 ? 250000 : 100000;
    $total  = $divisaoUser === 1 ? 20 : 20;
    $premioPos = max(0, ($total - ($posUsuario - 1)) * $fator);
    if ($premioPos > 0) {
        $pdo->prepare("UPDATE ecofut_saves SET saldo = saldo + ? WHERE id = ?")->execute([$premioPos, $saveId]);
        $pdo->prepare("INSERT INTO ecofut_financas (save_id,categoria,descricao,valor) VALUES (?,'premio',?,?)")
            ->execute([$saveId, "Prêmio classificação final ({$posUsuario}º lugar)", $premioPos]);
    }

    // ── Penalidade rebaixamento ───────────────────────────────────────────────
    if ($rebaixado) {
        $pdo->prepare("UPDATE ecofut_saves SET saldo = GREATEST(0, saldo - 1500000) WHERE id = ?")->execute([$saveId]);
        $pdo->prepare("INSERT INTO ecofut_financas (save_id,categoria,descricao,valor) VALUES (?,'rebaixamento','Penalidade por rebaixamento',-1500000)")->execute([$saveId]);
    }

    // ── Envelhecimento ────────────────────────────────────────────────────────
    $jogQ = $pdo->prepare("SELECT id, forca, idade, partidas FROM ecofut_elenco WHERE save_id = ?");
    $jogQ->execute([$saveId]);
    $updJog = $pdo->prepare("UPDATE ecofut_elenco SET idade = ?, forca = ? WHERE id = ?");
    foreach ($jogQ->fetchAll(PDO::FETCH_ASSOC) as $j) {
        $novaIdade = (int)$j['idade'] + 1;
        $novaForca = (int)$j['forca'];
        if ($novaIdade >= 35)                                        $novaForca = max(50, $novaForca - rand(1, 2));
        elseif ($novaIdade < 22 && (int)($j['partidas'] ?? 0) > 5)  $novaForca = min(99, $novaForca + 1);
        $updJog->execute([$novaIdade, $novaForca, $j['id']]);
    }

    // ── Contratos expirados ───────────────────────────────────────────────────
    $pdo->prepare("UPDATE ecofut_elenco SET meses_contrato = meses_contrato - 12 WHERE save_id = ?")->execute([$saveId]);
    $expQ = $pdo->prepare("SELECT nome FROM ecofut_elenco WHERE save_id = ? AND meses_contrato <= 0");
    $expQ->execute([$saveId]);
    $saindo = $expQ->fetchAll(PDO::FETCH_COLUMN);
    $pdo->prepare("DELETE FROM ecofut_elenco WHERE save_id = ? AND meses_contrato <= 0")->execute([$saveId]);

    // ── Notificação ───────────────────────────────────────────────────────────
    $dadosJson['notificacao_temporada'] = [
        'temporada'        => $temporada,
        'posicao'          => $posUsuario,
        'divisao'          => $divisaoUser,
        'rebaixado'        => $rebaixado,
        'promovido'        => $promovido,
        'campeao'          => $campeao,
        'continental'      => $continental,
        'jogadores_saindo' => $saindo,
    ];

    // ── Jovem revelado ────────────────────────────────────────────────────────
    $fn  = ['Carlos','Rafael','Lucas','Pedro','Matheus','Felipe','Gabriel','Thiago','Bruno','Enzo'];
    $ln  = ['Silva','Santos','Oliveira','Costa','Ferreira','Rodrigues','Lima','Alves','Souza','Nascimento'];
    $pos = ['ATA','ATA','PE','PD','MEI','VOL','ZAG'];
    $jF  = rand(60, 70);
    $dadosJson['jovem_revelado'] = [
        'nome'      => $fn[array_rand($fn)] . ' ' . $ln[array_rand($ln)],
        'posicao'   => $pos[array_rand($pos)],
        'forca'     => $jF,
        'potencial' => min(99, $jF + rand(8, 20)),
        'idade'     => rand(17, 20),
        'salario'   => rand(25000, 50000),
    ];

    // ── Nova temporada ────────────────────────────────────────────────────────
    $novaTemp = $temporada + 1;
    $pdo->prepare("INSERT INTO ecofut_financas (save_id,categoria,descricao,valor) VALUES (?,'patrocinio',?,500000)")
        ->execute([$saveId, "Patrocínio temporada $novaTemp"]);
    $pdo->prepare("UPDATE ecofut_saves SET saldo = saldo + 500000 WHERE id = ?")->execute([$saveId]);

    // Reseta classificação de todas as divisões
    $pdo->prepare("UPDATE ecofut_classificacao SET pontos=0,jogos=0,vitorias=0,empates=0,derrotas=0,gols_pro=0,gols_contra=0 WHERE save_id=?")->execute([$saveId]);
    $pdo->prepare("DELETE FROM ecofut_partidas WHERE save_id = ?")->execute([$saveId]);

    // Gera novos fixtures com divisões atualizadas
    $timesA = $pdo->query("SELECT id FROM ecofut_times WHERE divisao = 1 ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
    $timesB = $pdo->query("SELECT id FROM ecofut_times WHERE divisao = 2 ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);

    // Garante entradas de classificação para times que mudaram de divisão
    $insClass = $pdo->prepare("INSERT IGNORE INTO ecofut_classificacao (save_id, time_id, divisao) VALUES (?,?,?)");
    foreach ($timesA as $tid) $insClass->execute([$saveId, $tid, 1]);
    foreach ($timesB as $tid) $insClass->execute([$saveId, $tid, 2]);

    gerarFixtures($pdo, $saveId, $timesA, 1);
    gerarFixtures($pdo, $saveId, $timesB, 2);
    inicializarCopaDoBrasil($pdo, $saveId, $timeId);

    $pdo->prepare("UPDATE ecofut_elenco SET partidas=0,gols=0,assists=0,amarelos=0,vermelhos=0,nota_total=0 WHERE save_id=?")->execute([$saveId]);
    $pdo->prepare("UPDATE ecofut_saves SET temporada=?,rodada_atual=1,dados_json=? WHERE id=?")
        ->execute([$novaTemp, json_encode($dadosJson), $saveId]);
}
