<?php
/**
 * Motor de temporada — EcoFut
 * Gera fixtures round-robin (38 rodadas, 380 partidas) e inicializa elenco do usuário.
 */

require_once __DIR__ . '/../engine/match.php';

// ── Inicialização de temporada ────────────────────────────────────────────────

/**
 * Chamado ao criar um novo save. Recebe o time_id do usuário.
 * - Copia os 25 jogadores de ecofut_jogadores_base para ecofut_elenco
 * - Inicializa ecofut_classificacao para os 20 times
 * - Gera as 380 partidas em ecofut_partidas
 * - Insere receita inicial de patrocinador
 */
function inicializarTemporada(PDO $pdo, int $saveId, int $timeUsuarioId): void {
    // 1. Elenco do usuário (copia da base)
    $jogBase = $pdo->prepare(
        "SELECT * FROM ecofut_jogadores_base WHERE time_id = ?"
    );
    $jogBase->execute([$timeUsuarioId]);
    $jogadores = $jogBase->fetchAll(PDO::FETCH_ASSOC);

    $insJog = $pdo->prepare(
        "INSERT INTO ecofut_elenco
         (save_id, time_id, jogador_base_id, nome, posicao, forca, idade, energia, moral,
          contundido, suspenso, salario, meses_contrato, titular,
          sk_goleiro, sk_agilidade, sk_passe, sk_armacao, sk_desarme, sk_finalizacao, sk_tecnica)
         VALUES (?,?,?,?,?,?,?,100,75,0,0,?,12,?,?,?,?,?,?,?,?)"
    );

    $posIdx = array_fill_keys(['GOL','ZAG','LD','LE','VOL','MC','MEI','PE','PD','ATA'], 0);
    $titularLimites = ['GOL'=>1,'ZAG'=>2,'LD'=>1,'LE'=>1,'VOL'=>2,'MC'=>2,'MEI'=>1,'PE'=>1,'PD'=>1,'ATA'=>2];

    foreach ($jogadores as $j) {
        $pos = $j['posicao'];
        $limite = $titularLimites[$pos] ?? 1;
        $titular = ($posIdx[$pos] ?? 0) < $limite ? 1 : 0;
        if (isset($posIdx[$pos])) $posIdx[$pos]++;

        $insJog->execute([
            $saveId, $timeUsuarioId, $j['id'], $j['nome'], $j['posicao'], $j['forca'],
            $j['idade'], $j['salario'], $titular,
            $j['sk_goleiro'], $j['sk_agilidade'], $j['sk_passe'],
            $j['sk_armacao'], $j['sk_desarme'], $j['sk_finalizacao'], $j['sk_tecnica'],
        ]);
    }

    // 2. Classificação de todos os times
    $times = $pdo->query("SELECT id FROM ecofut_times ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
    $insClass = $pdo->prepare(
        "INSERT IGNORE INTO ecofut_classificacao (save_id, time_id, divisao) VALUES (?,?,1)"
    );
    foreach ($times as $tid) {
        $insClass->execute([$saveId, $tid]);
    }

    // 3. Gerar fixtures round-robin
    gerarFixtures($pdo, $saveId, $times, 1);

    // 4. Receita inicial de patrocinador
    $pdo->prepare(
        "INSERT INTO ecofut_financas (save_id, categoria, descricao, valor)
         VALUES (?, 'patrocinio', 'Patrocínio inicial da temporada', 500000)"
    )->execute([$saveId]);
}

// ── Geração de fixtures (round-robin) ────────────────────────────────────────

/**
 * Algoritmo "round robin" para n times → n-1 rodadas de ida.
 * Duplica para n*2-2 rodadas com mandos invertidos.
 */
function gerarFixtures(PDO $pdo, int $saveId, array $times, int $divisao): void {
    $n = count($times);
    if ($n < 2) return;

    $ids = $times;
    // Se ímpar, adiciona BYE (null)
    if ($n % 2 !== 0) $ids[] = null;

    $m  = count($ids);
    $fixo = $ids[0];
    $rot  = array_slice($ids, 1);

    $jogos = []; // [rodada, casa, fora]

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
        // Rotação
        $ultimo = array_pop($rot);
        array_unshift($rot, $ultimo);
    }

    // Turno de volta (mandos invertidos, rodadas + nRodadas)
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

// ── Avançar rodada ────────────────────────────────────────────────────────────

/**
 * Simula todas as partidas da rodada atual (exceto a do usuário se não simulada ainda).
 * Atualiza classificação, aplica desgaste, avança rodada_atual.
 *
 * @return array ['rodada'=>int, 'resultados'=>array, 'partida_usuario'=>array|null]
 */
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
            // Partida do usuário: não simula aqui, monta setup para o viewer JS
            $ehCasa  = (int)$p['time_casa_id'] === $timeUsuarioId;
            $jogMeu  = $ehCasa ? $dadosCasa['jogadores'] : $dadosFora['jogadores'];
            $jogAdv  = $ehCasa ? $dadosFora['jogadores'] : $dadosCasa['jogadores'];
            $mapJog  = fn($arr) => array_values(array_map(fn($j) => [
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
                'partida_id'    => (int)$p['id'],
                'rodada'        => $rodada,
                'nome_casa'     => $dadosCasa['nome'],
                'nome_fora'     => $dadosFora['nome'],
                'time_casa_id'  => (int)$p['time_casa_id'],
                'time_fora_id'  => (int)$p['time_fora_id'],
                'meu_time_id'   => $timeUsuarioId,
                'eh_casa'       => $ehCasa,
                'forca_meu'     => round(calcularForcaElenco($jogMeu, 65), 2),
                'forca_adv'     => round(calcularForcaElenco($jogAdv, 65), 2),
                'tatica_inicial'=> $taticaUser,
                'formacao_inicial' => $dadosJson['formacao'] ?? '4-3-3',
                'jogadores_meu' => $mapJog($jogMeu),
                'jogadores_adv' => $mapJog($jogAdv),
            ];
            continue;
        }

        $res = simularPartida($dadosCasa, $dadosFora);
        $pdo->prepare("UPDATE ecofut_partidas SET gols_casa=?,gols_fora=?,status='jogada',log_json=? WHERE id=?")
            ->execute([$res['gols_casa'], $res['gols_fora'], json_encode($res['log']), $p['id']]);
        atualizarClassificacao($pdo, $saveId, (int)$p['time_casa_id'], (int)$p['time_fora_id'], $res['gols_casa'], $res['gols_fora']);
        $resultados[] = ['partida_id'=>(int)$p['id'],'time_casa_id'=>(int)$p['time_casa_id'],'time_fora_id'=>(int)$p['time_fora_id'],'gols_casa'=>$res['gols_casa'],'gols_fora'=>$res['gols_fora']];
    }

    // Se o usuário não tem partida nesta rodada, processa encerramento agora
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
        if ($rodada >= 38) processarFimTemporada($pdo, $saveId);
    }

    return [
        'rodada'         => $rodada,
        'resultados'     => $resultados,
        'match_setup'    => $matchSetup,
        'time_usuario_id'=> $timeUsuarioId,
    ];
}

/**
 * Finaliza a partida do usuário após a simulação no cliente.
 * Salva resultado, atualiza classificação, aplica desgaste, avança rodada.
 */
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
    if ($rodada >= 38) processarFimTemporada($pdo, $saveId);

    return ['ok' => true, 'rodada' => $rodada, 'time_usuario_id' => $timeUsuarioId];
}

// ── Helpers internos ──────────────────────────────────────────────────────────

function obterDadosTimeParaSimulacao(PDO $pdo, int $saveId, int $timeId, int $timeUsuarioId): array {
    $stmt = $pdo->prepare("SELECT nome, forca_base FROM ecofut_times WHERE id = ?");
    $stmt->execute([$timeId]);
    $time = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($timeId === $timeUsuarioId) {
        // Usa elenco real do usuário
        $jStmt = $pdo->prepare(
            "SELECT * FROM ecofut_elenco WHERE save_id = ? AND time_id = ?"
        );
        $jStmt->execute([$saveId, $timeId]);
        $jogadores = $jStmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // IA: usa dados da base, simula titulares
        $jStmt = $pdo->prepare(
            "SELECT *, 1 as titular, 100 as energia, 75 as moral, 0 as contundido, 0 as suspenso
             FROM ecofut_jogadores_base WHERE time_id = ? LIMIT 25"
        );
        $jStmt->execute([$timeId]);
        $jogadores = $jStmt->fetchAll(PDO::FETCH_ASSOC);
        // Marca os 11 primeiros como titulares
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

    // Casa
    $vCasa = $gCasa > $gFora ? 1 : 0;
    $eCasa = $gCasa === $gFora ? 1 : 0;
    $dCasa = $gCasa < $gFora ? 1 : 0;
    $upd->execute([$vCasa, $eCasa, $dCasa, $gCasa, $gFora, $pontosCasa, $saveId, $casaId]);

    // Fora
    $vFora = $gFora > $gCasa ? 1 : 0;
    $eFora = $gCasa === $gFora ? 1 : 0;
    $dFora = $gFora < $gCasa ? 1 : 0;
    $upd->execute([$vFora, $eFora, $dFora, $gFora, $gCasa, $pontosFora, $saveId, $foraId]);
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
    // Prêmio de posição final (1º = 5M, decrescente)
    $classificacao = $pdo->prepare(
        "SELECT time_id FROM ecofut_classificacao
         WHERE save_id = ? AND divisao = 1
         ORDER BY pontos DESC, (gols_pro - gols_contra) DESC, gols_pro DESC"
    );
    $classificacao->execute([$saveId]);
    $ordem = $classificacao->fetchAll(PDO::FETCH_COLUMN);

    $saveRow = $pdo->prepare("SELECT time_id FROM ecofut_saves WHERE id = ?");
    $saveRow->execute([$saveId]);
    $timeId = (int)$saveRow->fetchColumn();

    $pos = array_search($timeId, $ordem);
    if ($pos !== false) {
        $premio = max(0, (20 - $pos) * 250000);
        if ($premio > 0) {
            $pdo->prepare("UPDATE ecofut_saves SET saldo = saldo + ? WHERE id = ?")->execute([$premio, $saveId]);
            $pdo->prepare(
                "INSERT INTO ecofut_financas (save_id, categoria, descricao, valor)
                 VALUES (?, 'premio', 'Prêmio por classificação final', ?)"
            )->execute([$saveId, $premio]);
        }
    }

    // Patrocínio nova temporada
    $pdo->prepare(
        "INSERT INTO ecofut_financas (save_id, categoria, descricao, valor)
         VALUES (?, 'patrocinio', 'Patrocínio temporada', 500000)"
    )->execute([$saveId]);
    $pdo->prepare("UPDATE ecofut_saves SET saldo = saldo + 500000 WHERE id = ?")->execute([$saveId]);
}
