<?php
/**
 * Motor de simulação de partidas — EcoFut
 * Usa distribuição de Poisson para geração de gols, com modificadores
 * de força, tática e vantagem de campo.
 */

// ── Constantes táticas ────────────────────────────────────────────────────────
const TAT_EQUILIBRADO   = 'equilibrado';
const TAT_ATAQUE        = 'ataque';
const TAT_CONTRA        = 'contra-ataque';
const TAT_PRESSAO       = 'pressao';
const TAT_DEFENSIVO     = 'defensivo';

// Modificadores [lambda_atk, lambda_def] por tática
const TAT_MOD = [
    TAT_EQUILIBRADO => [1.00, 1.00],
    TAT_ATAQUE      => [1.25, 0.85],
    TAT_CONTRA      => [0.80, 1.20],
    TAT_PRESSAO     => [1.15, 0.90],
    TAT_DEFENSIVO   => [0.70, 1.30],
];

// ── Função principal ──────────────────────────────────────────────────────────

/**
 * Simula uma partida e retorna array com resultado + log de eventos.
 *
 * @param array $casa   ['forca'=>int, 'tatica'=>string, 'jogadores'=>array, 'nome'=>string, 'id'=>int]
 * @param array $fora   [idem]
 * @param bool  $neutro Sem vantagem de campo (finais, jogos em campo neutro)
 * @return array ['gols_casa'=>int, 'gols_fora'=>int, 'log'=>array]
 */
function simularPartida(array $casa, array $fora, bool $neutro = false): array {
    $forcaCasa = calcularForcaElenco($casa['jogadores'] ?? [], $casa['forca'] ?? 65);
    $forcaFora = calcularForcaElenco($fora['jogadores']  ?? [], $fora['forca']  ?? 65);

    // Vantagem de campo: +8% no λ de ataque do mandante
    $homeBonus = $neutro ? 1.0 : 1.08;

    // Lambda base = força / 55 (calibrado para ~1.5 gols/time em forças iguais)
    $lambdaBase = 1.5;
    $ratio = $forcaCasa / max($forcaFora, 1);

    $tatCasa = $casa['tatica'] ?? TAT_EQUILIBRADO;
    $tatFora = $fora['tatica'] ?? TAT_EQUILIBRADO;
    [$atkModCasa, $defModCasa] = TAT_MOD[$tatCasa] ?? [1.0, 1.0];
    [$atkModFora, $defModFora] = TAT_MOD[$tatFora] ?? [1.0, 1.0];

    // λ de gols de cada time = base * ratio_força * mod_ataque / mod_defesa_adversário * homeBonus
    $lambdaCasa = $lambdaBase * sqrt($ratio)     * $atkModCasa / $defModFora * $homeBonus;
    $lambdaFora = $lambdaBase * sqrt(1 / $ratio) * $atkModFora / $defModCasa;

    $lambdaCasa = max(0.3, min(5.0, $lambdaCasa));
    $lambdaFora = max(0.3, min(5.0, $lambdaFora));

    $golsCasa = poissonSample($lambdaCasa);
    $golsFora = poissonSample($lambdaFora);

    $log = gerarLog($casa, $fora, $golsCasa, $golsFora);

    return [
        'gols_casa' => $golsCasa,
        'gols_fora' => $golsFora,
        'log'       => $log,
    ];
}

// ── Força do elenco ───────────────────────────────────────────────────────────

function calcularForcaElenco(array $jogadores, int $forcaBase = 65): float {
    if (empty($jogadores)) return $forcaBase;

    // Usa os 11 titulares (ou qualquer disponível), média ponderada
    $titulares  = array_filter($jogadores, fn($j) => ($j['titular'] ?? 0) && !($j['contundido'] ?? 0) && !($j['suspenso'] ?? 0));
    $usados     = array_slice(array_values($titulares), 0, 11);

    if (count($usados) < 11) {
        // Completa com reservas
        $reservas = array_filter($jogadores, fn($j) => !($j['titular'] ?? 0) && !($j['contundido'] ?? 0) && !($j['suspenso'] ?? 0));
        usort($reservas, fn($a, $b) => ($b['forca'] ?? 0) <=> ($a['forca'] ?? 0));
        $faltam = 11 - count($usados);
        $usados = array_merge($usados, array_slice(array_values($reservas), 0, $faltam));
    }

    if (empty($usados)) return $forcaBase;

    $somaForca = array_sum(array_column($usados, 'forca'));
    $media     = $somaForca / count($usados);

    // Penalidade de moral/energia
    $somaFator = 0;
    foreach ($usados as $j) {
        $moral   = ($j['moral']   ?? 75) / 100;
        $energia = ($j['energia'] ?? 100) / 100;
        $somaFator += ($moral * 0.3 + $energia * 0.7);
    }
    $fatorMedio = $somaFator / count($usados);

    return $media * (0.80 + 0.20 * $fatorMedio);
}

// ── Distribuição de Poisson ───────────────────────────────────────────────────

function poissonSample(float $lambda): int {
    // Algoritmo de Knuth
    $L = exp(-$lambda);
    $k = 0;
    $p = 1.0;
    do {
        $k++;
        $p *= lcg_value();
    } while ($p > $L);
    return $k - 1;
}

// ── Log de eventos ────────────────────────────────────────────────────────────

function gerarLog(array $casa, array $fora, int $golsCasa, int $golsFora): array {
    $eventos = [];

    $minutosCasa = sortedRandMinutes($golsCasa);
    $minutosFora = sortedRandMinutes($golsFora);

    $artCasa = artilheirosDisponiveis($casa['jogadores'] ?? []);
    $artFora = artilheirosDisponiveis($fora['jogadores'] ?? []);
    $passCasa = passadoresDisponiveis($casa['jogadores'] ?? []);
    $passFora = passadoresDisponiveis($fora['jogadores'] ?? []);

    foreach ($minutosCasa as $min) {
        $scorer = sortearArtilheiro($artCasa);
        $eventos[] = [
            'tipo'      => 'gol',
            'minuto'    => $min,
            'time'      => 'casa',
            'time_id'   => $casa['id'] ?? 0,
            'jogador'   => $scorer,
            'assistido' => lcg_value() > 0.25 ? sortearPassador($passCasa, $scorer) : null,
        ];
    }
    foreach ($minutosFora as $min) {
        $scorer = sortearArtilheiro($artFora);
        $eventos[] = [
            'tipo'      => 'gol',
            'minuto'    => $min,
            'time'      => 'fora',
            'time_id'   => $fora['id'] ?? 0,
            'jogador'   => $scorer,
            'assistido' => lcg_value() > 0.25 ? sortearPassador($passFora, $scorer) : null,
        ];
    }

    // Chutes defendidos (3-6 por time)
    foreach ([['casa', $casa], ['fora', $fora]] as [$lado, $time]) {
        $nChutes = rand(3, 6);
        $art = $lado === 'casa' ? $artCasa : $artFora;
        for ($i = 0; $i < $nChutes; $i++) {
            $eventos[] = [
                'tipo'    => 'defesa',
                'minuto'  => rand(1, 90),
                'time'    => $lado,
                'time_id' => $time['id'] ?? 0,
                'jogador' => sortearArtilheiro($art),
            ];
        }
    }

    // Cartões amarelos
    $nAmarelos = rand(2, 5);
    for ($i = 0; $i < $nAmarelos; $i++) {
        $eCasa = lcg_value() > 0.5;
        $jogs  = $eCasa ? ($casa['jogadores'] ?? []) : ($fora['jogadores'] ?? []);
        if (!empty($jogs)) {
            $j = $jogs[array_rand($jogs)];
            $eventos[] = [
                'tipo'    => 'amarelo',
                'minuto'  => rand(1, 90),
                'time'    => $eCasa ? 'casa' : 'fora',
                'time_id' => $eCasa ? ($casa['id'] ?? 0) : ($fora['id'] ?? 0),
                'jogador' => $j['nome'] ?? 'Jogador',
            ];
        }
    }

    // Faltas (4-8 por partida)
    $nFaltas = rand(4, 8);
    for ($i = 0; $i < $nFaltas; $i++) {
        $eCasa = lcg_value() > 0.5;
        $jogs  = $eCasa ? ($casa['jogadores'] ?? []) : ($fora['jogadores'] ?? []);
        if (!empty($jogs)) {
            $j = $jogs[array_rand($jogs)];
            $eventos[] = [
                'tipo'    => 'falta',
                'minuto'  => rand(1, 90),
                'time'    => $eCasa ? 'casa' : 'fora',
                'time_id' => $eCasa ? ($casa['id'] ?? 0) : ($fora['id'] ?? 0),
                'jogador' => $j['nome'] ?? 'Jogador',
            ];
        }
    }

    // Cartão vermelho (5% chance)
    if (lcg_value() < 0.05) {
        $eCasa = lcg_value() > 0.5;
        $jogs  = $eCasa ? ($casa['jogadores'] ?? []) : ($fora['jogadores'] ?? []);
        if (!empty($jogs)) {
            $j = $jogs[array_rand($jogs)];
            $eventos[] = [
                'tipo'    => 'vermelho',
                'minuto'  => rand(20, 90),
                'time'    => $eCasa ? 'casa' : 'fora',
                'time_id' => $eCasa ? ($casa['id'] ?? 0) : ($fora['id'] ?? 0),
                'jogador' => $j['nome'] ?? 'Jogador',
            ];
        }
    }

    usort($eventos, fn($a, $b) => $a['minuto'] <=> $b['minuto']);
    return $eventos;
}

function passadoresDisponiveis(array $jogadores): array {
    if (empty($jogadores)) return [['nome' => 'Jogador', 'sk_armacao' => 50]];
    $ativos = array_filter($jogadores, fn($j) => !($j['contundido'] ?? 0) && !($j['suspenso'] ?? 0));
    $midfield = array_filter($ativos ?: $jogadores, fn($j) => in_array($j['posicao'] ?? '', ['MC','MEI','VOL','PE','PD','LD','LE']));
    return array_values(!empty($midfield) ? $midfield : ($ativos ?: $jogadores));
}

function sortearPassador(array $jogadores, string $scorer): string {
    $jogs = array_values(array_filter($jogadores, fn($j) => ($j['nome'] ?? '') !== $scorer));
    if (empty($jogs)) return '';
    $totalPeso = 0;
    $pesos = [];
    foreach ($jogs as $j) {
        $arm  = $j['sk_armacao'] ?? 50;
        $p    = max(1, $arm);
        $pesos[] = $p;
        $totalPeso += $p;
    }
    $r = lcg_value() * $totalPeso;
    $acc = 0;
    foreach ($jogs as $i => $j) {
        $acc += $pesos[$i];
        if ($r <= $acc) return $j['nome'] ?? '';
    }
    return $jogs[0]['nome'] ?? '';
}

function sortedRandMinutes(int $n): array {
    $mins = [];
    for ($i = 0; $i < $n; $i++) {
        $mins[] = rand(1, 90);
    }
    sort($mins);
    return $mins;
}

function artilheirosDisponiveis(array $jogadores): array {
    if (empty($jogadores)) return [['nome' => 'Jogador', 'sk_finalizacao' => 50]];
    $ativos = array_filter($jogadores, fn($j) => !($j['contundido'] ?? 0) && !($j['suspenso'] ?? 0));
    return empty($ativos) ? $jogadores : array_values($ativos);
}

function sortearArtilheiro(array $jogadores): string {
    if (empty($jogadores)) return 'Jogador';

    // Peso proporcional à finalização (atacantes marcam mais)
    $totalPeso = 0;
    $pesos     = [];
    foreach ($jogadores as $j) {
        $fin  = $j['sk_finalizacao'] ?? 50;
        $pos  = $j['posicao'] ?? 'MC';
        $mult = in_array($pos, ['ATA', 'PE', 'PD', 'MEI']) ? 3.0 : 1.0;
        $p    = max(1, $fin * $mult);
        $pesos[] = $p;
        $totalPeso += $p;
    }

    $r = lcg_value() * $totalPeso;
    $acc = 0;
    foreach ($jogadores as $i => $j) {
        $acc += $pesos[$i];
        if ($r <= $acc) return $j['nome'] ?? 'Jogador';
    }
    return $jogadores[0]['nome'] ?? 'Jogador';
}

// ── Atualiza energia/moral pós-partida ────────────────────────────────────────

/**
 * Aplica desgaste pós-jogo nos jogadores do elenco do usuário.
 * Chame após simular a partida do usuário.
 *
 * @param PDO   $pdo
 * @param int   $saveId
 * @param array $resultado ['gols_casa', 'gols_fora', 'time_casa_id', 'time_fora_id']
 * @param int   $timeUsuarioId
 */
function aplicarDesgastePosJogo(PDO $pdo, int $saveId, array $resultado, int $timeUsuarioId): void {
    $ganhou  = ($resultado['time_casa_id'] === $timeUsuarioId)
        ? $resultado['gols_casa'] > $resultado['gols_fora']
        : $resultado['gols_fora'] > $resultado['gols_casa'];
    $empatou = $resultado['gols_casa'] === $resultado['gols_fora'];

    // Variação de moral
    $deltaMoral = $ganhou ? rand(5, 12) : ($empatou ? rand(-3, 3) : rand(-12, -5));

    $stmt = $pdo->prepare(
        "UPDATE ecofut_elenco
         SET energia   = GREATEST(20, energia - ?),
             moral     = GREATEST(10, LEAST(100, moral + ?))
         WHERE save_id = ?"
    );
    $stmt->execute([rand(8, 15), $deltaMoral, $saveId]);
}

/**
 * Recupera energia dos jogadores que não jogaram (+ recuperação passiva por semana).
 */
function recuperarEnergiaBancoLesionados(PDO $pdo, int $saveId): void {
    $pdo->prepare(
        "UPDATE ecofut_elenco
         SET energia = LEAST(100, energia + 10)
         WHERE save_id = ? AND (contundido = 1 OR suspenso = 1 OR titular = 0)"
    )->execute([$saveId]);
}
