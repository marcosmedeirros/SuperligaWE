<?php
/**
 * Motor de temporada — EcoFut
 * Gera fixtures round-robin (38 rodadas, 380 partidas) e inicializa elenco do usuário.
 */

require_once __DIR__ . '/../engine/match.php';

// ── Inicialização de temporada ────────────────────────────────────────────────

function inicializarTemporada(PDO $pdo, int $saveId, int $timeUsuarioId): void {
    // 1. Elenco do usuário (copia da base)
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

    $posIdx = array_fill_keys(['GOL','ZAG','LD','LE','VOL','MC','MEI','PE','PD','ATA'], 0);
    $titularLimites = ['GOL'=>1,'ZAG'=>2,'LD'=>1,'LE'=>1,'VOL'=>2,'MC'=>2,'MEI'=>1,'PE'=>1,'PD'=>1,'ATA'=>2];

    foreach ($jogadores as $j) {
        $pos    = $j['posicao'];
        $limite = $titularLimites[$pos] ?? 1;
        $titular = ($posIdx[$pos] ?? 0) < $limite ? 1 : 0;
        if (isset($posIdx[$pos])) $posIdx[$pos]++;

        $idade    = (int)$j['idade'];
        $forca    = (int)$j['forca'];
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

    // 2. Classificação de todos os times
    $times = $pdo->query("SELECT id FROM ecofut_times ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
    $insClass = $pdo->prepare("INSERT IGNORE INTO ecofut_classificacao (save_id, time_id, divisao) VALUES (?,?,1)");
    foreach ($times as $tid) {
        $insClass->execute([$saveId, $tid]);
    }

    // 3. Gerar fixtures round-robin
    gerarFixtures($pdo, $saveId, $times, 1);

    // 4. Inicializar Copa
    inicializarCopa($pdo, $saveId, $timeUsuarioId);

    // 5. Receita inicial de patrocinador
    $pdo->prepare(
        "INSERT INTO ecofut_financas (save_id, categoria, descricao, valor)
         VALUES (?, 'patrocinio', 'Patrocínio inicial da temporada', 500000)"
    )->execute([$saveId]);
}

// ── Geração de fixtures (round-robin) ────────────────────────────────────────

function gerarFixtures(PDO $pdo, int $saveId, array $times, int $divisao): void {
    $n = count($times);
    if ($n < 2) return;

    $ids = $times;
    if ($n % 2 !== 0) $ids[] = null;

    $m    = count($ids);
    $fixo = $ids[0];
    $rot  = array_slice($ids, 1);

    $jogos = [];

    $nRodadas = $m - 1;
    for ($r = 0; $r < $nRodadas; $r++) {
        $atual = array_merge([$fixo], $rot);
        $meio  = $m / 2;
        for ($i = 0; $i < $meio; $i++) {
            $a = $atual[$i];
            $b = $atual[$m - 1 - $i];
            if ($a !== null && $b !== null) {
                $jogos[] = [$r + 1, $a, $b];
            }
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

// ── Copa ──────────────────────────────────────────────────────────────────────

function inicializarCopa(PDO $pdo, int $saveId, int $timeId): void {
    $pdo->prepare("DELETE FROM ecofut_copa WHERE save_id = ?")->execute([$saveId]);

    // Pega todos os times, shuffleados, garante time do usuário incluso
    $todos = $pdo->query("SELECT id FROM ecofut_times")->fetchAll(PDO::FETCH_COLUMN);
    shuffle($todos);
    $oito = [$timeId];
    foreach ($todos as $t) {
        if ((int)$t !== $timeId && count($oito) < 8) {
            $oito[] = (int)$t;
        }
    }
    shuffle($oito);

    // 4 jogos de quartas de final
    $ins = $pdo->prepare("INSERT INTO ecofut_copa (save_id, fase, time_casa_id, time_fora_id) VALUES (?, 'quartas', ?, ?)");
    for ($i = 0; $i + 1 < count($oito); $i += 2) {
        $ins->execute([$saveId, $oito[$i], $oito[$i + 1]]);
    }
}

/**
 * Simula uma fase da Copa quando a rodada trigger completa.
 * Triggers: rodada 10 → quartas, 20 → semifinal, 30 → final.
 */
function processarCopa(PDO $pdo, int $saveId, int $timeId, int $rodadaCompleta): void {
    $triggers = [10 => 'quartas', 20 => 'semifinal', 30 => 'final'];
    if (!isset($triggers[$rodadaCompleta])) return;
    $fase = $triggers[$rodadaCompleta];

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ecofut_copa WHERE save_id = ? AND fase = ? AND status = 'agendada'");
    $stmt->execute([$saveId, $fase]);
    if ((int)$stmt->fetchColumn() === 0) return;

    $copaStmt = $pdo->prepare("SELECT * FROM ecofut_copa WHERE save_id = ? AND fase = ? AND status = 'agendada'");
    $copaStmt->execute([$saveId, $fase]);
    $partidas = $copaStmt->fetchAll(PDO::FETCH_ASSOC);

    // Carrega força do elenco do usuário uma vez
    $userJog = $pdo->prepare("SELECT * FROM ecofut_elenco WHERE save_id = ?");
    $userJog->execute([$saveId]);
    $userJogadores = $userJog->fetchAll(PDO::FETCH_ASSOC);
    $userForca = (int)calcularForcaElenco($userJogadores, 65);

    $vencedores = [];
    foreach ($partidas as $p) {
        $fCasa = obterForcaTimeBase($pdo, (int)$p['time_casa_id']);
        $fFora = obterForcaTimeBase($pdo, (int)$p['time_fora_id']);
        if ((int)$p['time_casa_id'] === $timeId) $fCasa = $userForca;
        if ((int)$p['time_fora_id'] === $timeId) $fFora = $userForca;

        $dadosCasa = ['nome' => 'Casa', 'forca' => $fCasa, 'tatica' => TAT_EQUILIBRADO, 'jogadores' => [], 'id' => (int)$p['time_casa_id']];
        $dadosFora = ['nome' => 'Fora', 'forca' => $fFora, 'tatica' => TAT_EQUILIBRADO, 'jogadores' => [], 'id' => (int)$p['time_fora_id']];
        $res   = simularPartida($dadosCasa, $dadosFora);
        $gC    = $res['gols_casa'];
        $gF    = $res['gols_fora'];

        // Pênaltis em empate
        if ($gC === $gF) {
            $vencedorId = rand(0, 1) === 0 ? (int)$p['time_casa_id'] : (int)$p['time_fora_id'];
        } else {
            $vencedorId = $gC > $gF ? (int)$p['time_casa_id'] : (int)$p['time_fora_id'];
        }
        $vencedores[] = $vencedorId;

        $pdo->prepare("UPDATE ecofut_copa SET gols_casa=?,gols_fora=?,status='jogada' WHERE id=?")
            ->execute([$gC, $gF, $p['id']]);
    }

    // Avança para próxima fase
    if ($fase === 'quartas' && count($vencedores) >= 2) {
        $ins = $pdo->prepare("INSERT INTO ecofut_copa (save_id, fase, time_casa_id, time_fora_id) VALUES (?, 'semifinal', ?, ?)");
        for ($i = 0; $i + 1 < count($vencedores); $i += 2) {
            $ins->execute([$saveId, $vencedores[$i], $vencedores[$i + 1]]);
        }
    } elseif ($fase === 'semifinal' && count($vencedores) >= 2) {
        $pdo->prepare("INSERT INTO ecofut_copa (save_id, fase, time_casa_id, time_fora_id) VALUES (?, 'final', ?, ?)")
            ->execute([$saveId, $vencedores[0], $vencedores[1]]);

        // Prêmio consolação para perdedores da semi
        $semisAll = $pdo->prepare("SELECT * FROM ecofut_copa WHERE save_id = ? AND fase = 'semifinal'");
        $semisAll->execute([$saveId]);
        foreach ($semisAll->fetchAll(PDO::FETCH_ASSOC) as $m) {
            $perdedorId = in_array((int)$m['time_casa_id'], $vencedores) ? (int)$m['time_fora_id'] : (int)$m['time_casa_id'];
            if ($perdedorId === $timeId) {
                $pdo->prepare("UPDATE ecofut_saves SET saldo = saldo + 500000 WHERE id = ?")->execute([$saveId]);
                $pdo->prepare("INSERT INTO ecofut_financas (save_id, categoria, descricao, valor) VALUES (?, 'copa', 'Copa - Semifinal', 500000)")->execute([$saveId]);
            }
        }
    } elseif ($fase === 'final') {
        $vencedorFinal = $vencedores[0] ?? null;
        $finalQ = $pdo->prepare("SELECT * FROM ecofut_copa WHERE save_id = ? AND fase = 'final' ORDER BY id DESC LIMIT 1");
        $finalQ->execute([$saveId]);
        $fin = $finalQ->fetch(PDO::FETCH_ASSOC);
        if ($fin && $vencedorFinal !== null) {
            $isUserInFinal = ((int)$fin['time_casa_id'] === $timeId || (int)$fin['time_fora_id'] === $timeId);
            if ($vencedorFinal === $timeId) {
                $pdo->prepare("UPDATE ecofut_saves SET saldo = saldo + 3000000 WHERE id = ?")->execute([$saveId]);
                $pdo->prepare("INSERT INTO ecofut_financas (save_id, categoria, descricao, valor) VALUES (?, 'copa', 'Copa - CAMPEÃO!', 3000000)")->execute([$saveId]);
            } elseif ($isUserInFinal) {
                $pdo->prepare("UPDATE ecofut_saves SET saldo = saldo + 1000000 WHERE id = ?")->execute([$saveId]);
                $pdo->prepare("INSERT INTO ecofut_financas (save_id, categoria, descricao, valor) VALUES (?, 'copa', 'Copa - Vice-campeão', 1000000)")->execute([$saveId]);
            }
        }
    }
}

function obterForcaTimeBase(PDO $pdo, int $timeId): int {
    $q = $pdo->prepare("SELECT forca_base FROM ecofut_times WHERE id = ?");
    $q->execute([$timeId]);
    return (int)($q->fetchColumn() ?: 65);
}

// ── Avançar rodada ────────────────────────────────────────────────────────────

function avancarRodada(PDO $pdo, int $saveId): array {
    $saveQ = $pdo->prepare("SELECT * FROM ecofut_saves WHERE id = ?");
    $saveQ->execute([$saveId]);
    $saveData = $saveQ->fetch(PDO::FETCH_ASSOC);
    if (!$saveData) return [];

    $rodada        = (int)$saveData['rodada_atual'];
    $timeUsuarioId = (int)$saveData['time_id'];
    $dadosJson     = json_decode($saveData['dados_json'] ?? '{}', true) ?: [];
    $taticaUser    = $dadosJson['tatica'] ?? 'normal';

    $stmt = $pdo->prepare("SELECT * FROM ecofut_partidas WHERE save_id = ? AND rodada = ? AND status = 'agendada'");
    $stmt->execute([$saveId, $rodada]);
    $partidas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($partidas)) {
        return ['rodada' => $rodada, 'resultados' => [], 'match_setup' => null, 'time_usuario_id' => $timeUsuarioId];
    }

    $resultados = [];
    $matchSetup = null;

    foreach ($partidas as $p) {
        $ehUsuario = ((int)$p['time_casa_id'] === $timeUsuarioId || (int)$p['time_fora_id'] === $timeUsuarioId);
        $dadosCasa = obterDadosTimeParaSimulacao($pdo, $saveId, (int)$p['time_casa_id'], $timeUsuarioId);
        $dadosFora = obterDadosTimeParaSimulacao($pdo, $saveId, (int)$p['time_fora_id'], $timeUsuarioId);

        if ($ehUsuario) {
            $ehCasa = (int)$p['time_casa_id'] === $timeUsuarioId;
            $jogMeu = $ehCasa ? $dadosCasa['jogadores'] : $dadosFora['jogadores'];
            $jogAdv = $ehCasa ? $dadosFora['jogadores'] : $dadosCasa['jogadores'];
            $mapJog = fn($arr) => array_values(array_map(fn($j) => [
                'id'            => isset($j['id']) ? (int)$j['id'] : 0,
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
        atualizarClassificacao($pdo, $saveId, (int)$p['time_casa_id'], (int)$p['time_fora_id'], $res['gols_casa'], $res['gols_fora']);
        $resultados[] = [
            'partida_id'   => (int)$p['id'],
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

function finalizarPartidaUsuario(PDO $pdo, int $saveId, int $partidaId, int $golsCasa, int $golsFora): array {
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
    atualizarClassificacao($pdo, $saveId, (int)$partida['time_casa_id'], (int)$partida['time_fora_id'], $golsCasa, $golsFora);
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

function obterDadosTimeParaSimulacao(PDO $pdo, int $saveId, int $timeId, int $timeUsuarioId): array {
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

function atualizarClassificacao(PDO $pdo, int $saveId, int $casaId, int $foraId, int $gCasa, int $gFora): void {
    $pontosCasa = $gCasa > $gFora ? 3 : ($gCasa === $gFora ? 1 : 0);
    $pontosFora = $gFora > $gCasa ? 3 : ($gCasa === $gFora ? 1 : 0);

    $upd = $pdo->prepare(
        "UPDATE ecofut_classificacao
         SET jogos       = jogos + 1,
             vitorias    = vitorias + ?,
             empates     = empates  + ?,
             derrotas    = derrotas + ?,
             gols_pro    = gols_pro + ?,
             gols_contra = gols_contra + ?,
             pontos      = pontos + ?
         WHERE save_id = ? AND time_id = ? AND divisao = 1"
    );

    $vC = $gCasa > $gFora ? 1 : 0; $eC = $gCasa === $gFora ? 1 : 0; $dC = $gCasa < $gFora ? 1 : 0;
    $upd->execute([$vC, $eC, $dC, $gCasa, $gFora, $pontosCasa, $saveId, $casaId]);

    $vF = $gFora > $gCasa ? 1 : 0; $eF = $gCasa === $gFora ? 1 : 0; $dF = $gFora < $gCasa ? 1 : 0;
    $upd->execute([$vF, $eF, $dF, $gFora, $gCasa, $pontosFora, $saveId, $foraId]);
}

function processarSalarios(PDO $pdo, int $saveId): void {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(salario),0) FROM ecofut_elenco WHERE save_id = ?");
    $stmt->execute([$saveId]);
    $totalSalarios = (int)$stmt->fetchColumn();

    if ($totalSalarios > 0) {
        $pdo->prepare("UPDATE ecofut_saves SET saldo = saldo - ? WHERE id = ?")->execute([$totalSalarios, $saveId]);
        $pdo->prepare(
            "INSERT INTO ecofut_financas (save_id, categoria, descricao, valor)
             VALUES (?, 'salarios', 'Pagamento de salários', ?)"
        )->execute([$saveId, -$totalSalarios]);
    }
}

function processarFimTemporada(PDO $pdo, int $saveId): void {
    $saveQ = $pdo->prepare("SELECT * FROM ecofut_saves WHERE id = ?");
    $saveQ->execute([$saveId]);
    $saveData = $saveQ->fetch(PDO::FETCH_ASSOC);
    if (!$saveData) return;

    $timeId    = (int)$saveData['time_id'];
    $temporada = (int)$saveData['temporada'];
    $dadosJson = json_decode($saveData['dados_json'] ?? '{}', true) ?: [];

    // 1. Prêmio por classificação final
    $classQ = $pdo->prepare(
        "SELECT time_id FROM ecofut_classificacao
         WHERE save_id = ? AND divisao = 1
         ORDER BY pontos DESC, (gols_pro - gols_contra) DESC, gols_pro DESC"
    );
    $classQ->execute([$saveId]);
    $ordem = $classQ->fetchAll(PDO::FETCH_COLUMN);

    $pos = array_search($timeId, $ordem);
    if ($pos !== false) {
        $premioPos = max(0, (20 - $pos) * 250000);
        if ($premioPos > 0) {
            $posNum = $pos + 1;
            $pdo->prepare("UPDATE ecofut_saves SET saldo = saldo + ? WHERE id = ?")->execute([$premioPos, $saveId]);
            $pdo->prepare("INSERT INTO ecofut_financas (save_id, categoria, descricao, valor) VALUES (?, 'premio', ?, ?)")
                ->execute([$saveId, "Prêmio por classificação final ({$posNum}º lugar)", $premioPos]);
        }
    }

    // 2. Envelhecimento
    $jogQ = $pdo->prepare("SELECT id, forca, idade, partidas FROM ecofut_elenco WHERE save_id = ?");
    $jogQ->execute([$saveId]);
    $jogadores = $jogQ->fetchAll(PDO::FETCH_ASSOC);

    $updJog = $pdo->prepare("UPDATE ecofut_elenco SET idade = ?, forca = ? WHERE id = ?");
    foreach ($jogadores as $j) {
        $novaIdade = (int)$j['idade'] + 1;
        $novaForca = (int)$j['forca'];
        if ($novaIdade >= 35) {
            $novaForca = max(50, $novaForca - rand(1, 2));
        } elseif ($novaIdade < 22 && (int)($j['partidas'] ?? 0) > 5) {
            $novaForca = min(99, $novaForca + 1);
        }
        $updJog->execute([$novaIdade, $novaForca, $j['id']]);
    }

    // 3. Contrato expirando: desconta 12 meses e remove expirados
    $pdo->prepare("UPDATE ecofut_elenco SET meses_contrato = meses_contrato - 12 WHERE save_id = ?")->execute([$saveId]);
    $expiradosQ = $pdo->prepare("SELECT nome FROM ecofut_elenco WHERE save_id = ? AND meses_contrato <= 0");
    $expiradosQ->execute([$saveId]);
    $jogadoresExpirados = $expiradosQ->fetchAll(PDO::FETCH_COLUMN);
    $pdo->prepare("DELETE FROM ecofut_elenco WHERE save_id = ? AND meses_contrato <= 0")->execute([$saveId]);

    // 4. Rebaixamento/promoção
    $totalTimes   = count($ordem);
    $posUsuario   = $pos !== false ? (int)$pos + 1 : $totalTimes;
    $rebaixado    = $posUsuario > ($totalTimes - 4);
    $campeao      = $posUsuario === 1;
    $continental  = $posUsuario <= 6;

    if ($rebaixado) {
        $multa = 1000000;
        $pdo->prepare("UPDATE ecofut_saves SET saldo = GREATEST(0, saldo - ?) WHERE id = ?")->execute([$multa, $saveId]);
        $pdo->prepare("INSERT INTO ecofut_financas (save_id, categoria, descricao, valor) VALUES (?, 'rebaixamento', 'Penalidade por rebaixamento', ?)")->execute([$saveId, -$multa]);
    }

    // 5. Notificação da temporada
    $dadosJson['notificacao_temporada'] = [
        'temporada'        => $temporada,
        'posicao'          => $posUsuario,
        'rebaixado'        => $rebaixado,
        'campeao'          => $campeao,
        'continental'      => $continental,
        'jogadores_saindo' => $jogadoresExpirados,
    ];

    // 6. Jovem revelado para nova temporada
    $nomesBase      = ['Carlos','Rafael','Lucas','Pedro','Matheus','Felipe','Gabriel','Thiago','Bruno','Eduardo'];
    $sobrenomesBase = ['Silva','Santos','Oliveira','Costa','Ferreira','Rodrigues','Lima','Alves','Sousa','Nascimento'];
    $posicoesPoss   = ['ATA','ATA','PE','PD','MEI','VOL','ZAG'];
    $jForca = rand(60, 70);
    $dadosJson['jovem_revelado'] = [
        'nome'     => $nomesBase[array_rand($nomesBase)] . ' ' . $sobrenomesBase[array_rand($sobrenomesBase)],
        'posicao'  => $posicoesPoss[array_rand($posicoesPoss)],
        'forca'    => $jForca,
        'potencial'=> min(99, $jForca + rand(8, 20)),
        'idade'    => rand(17, 20),
        'salario'  => rand(25000, 50000),
    ];

    // 7. Nova temporada
    $novaTemporada = $temporada + 1;

    $pdo->prepare("INSERT INTO ecofut_financas (save_id, categoria, descricao, valor) VALUES (?, 'patrocinio', ?, 500000)")
        ->execute([$saveId, "Patrocínio temporada $novaTemporada"]);
    $pdo->prepare("UPDATE ecofut_saves SET saldo = saldo + 500000 WHERE id = ?")->execute([$saveId]);

    // Reset classificação, delete fixtures antigas, gera novas
    $pdo->prepare("UPDATE ecofut_classificacao SET pontos=0,jogos=0,vitorias=0,empates=0,derrotas=0,gols_pro=0,gols_contra=0 WHERE save_id = ?")->execute([$saveId]);
    $pdo->prepare("DELETE FROM ecofut_partidas WHERE save_id = ?")->execute([$saveId]);
    $times = $pdo->query("SELECT id FROM ecofut_times ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
    gerarFixtures($pdo, $saveId, $times, 1);
    inicializarCopa($pdo, $saveId, $timeId);

    // Reseta partidas e gols do elenco para nova temporada
    $pdo->prepare("UPDATE ecofut_elenco SET partidas=0,gols=0,assists=0,amarelos=0,vermelhos=0,nota_total=0 WHERE save_id = ?")->execute([$saveId]);

    $pdo->prepare("UPDATE ecofut_saves SET temporada=?, rodada_atual=1, dados_json=? WHERE id=?")
        ->execute([$novaTemporada, json_encode($dadosJson), $saveId]);
}
