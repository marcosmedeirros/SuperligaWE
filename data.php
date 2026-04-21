<?php
$page_data = [];

// ── SAVES PAGE ────────────────────────────────────────────────────────────────
if ($page === 'saves' && $db_connected && isset($_SESSION['ecofut_usuario_id'])) {
    $uid = (int)$_SESSION['ecofut_usuario_id'];

    $stmt = $pdo->prepare(
        "SELECT s.slot, s.nome_treinador, s.nome_time, s.temporada, s.saldo,
                s.rodada_atual, s.updated_at, t.cor1, t.cor2
         FROM ecofut_saves s
         LEFT JOIN ecofut_times t ON t.id = s.time_id
         WHERE s.usuario_id = ? ORDER BY s.slot ASC"
    );
    $stmt->execute([$uid]);

    $saves = [1 => null, 2 => null];
    foreach ($stmt->fetchAll() as $row) {
        $saves[(int)$row['slot']] = $row;
    }
    $page_data['saves'] = $saves;

    // Lista de times para o picker
    $page_data['times'] = $pdo->query(
        "SELECT id, nome, apelido, estado, forca_base, cor1, cor2, estadio, capacidade
         FROM ecofut_times ORDER BY forca_base DESC"
    )->fetchAll(PDO::FETCH_ASSOC);
}

// ── APP PAGE ──────────────────────────────────────────────────────────────────
if ($page === 'app' && $db_connected && isset($_SESSION['ecofut_save_id'])) {
    $save_id = (int)$_SESSION['ecofut_save_id'];

    // Save + time
    $stmt = $pdo->prepare(
        "SELECT s.*, t.nome AS time_nome, t.cor1, t.cor2, t.estadio, t.capacidade, t.forca_base
         FROM ecofut_saves s
         LEFT JOIN ecofut_times t ON t.id = s.time_id
         WHERE s.id = ?"
    );
    $stmt->execute([$save_id]);
    $saveRow = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($saveRow):

    $page_data['save'] = $saveRow;
    $page_data['time'] = [
        'nome'      => $saveRow['time_nome'],
        'cor1'      => $saveRow['cor1'],
        'cor2'      => $saveRow['cor2'],
        'estadio'   => $saveRow['estadio'],
        'capacidade'=> $saveRow['capacidade'],
        'forca_base'=> $saveRow['forca_base'],
    ];

    $timeId  = (int)$saveRow['time_id'];
    $rodada  = (int)$saveRow['rodada_atual'];

    // Elenco do usuário
    $eStmt = $pdo->prepare(
        "SELECT * FROM ecofut_elenco WHERE save_id = ?
         ORDER BY FIELD(posicao,'GOL','ZAG','LD','LE','VOL','MC','MEI','PE','PD','ATA'),
                  titular DESC, forca DESC"
    );
    $eStmt->execute([$save_id]);
    $page_data['elenco'] = $eStmt->fetchAll(PDO::FETCH_ASSOC);

    // Classificação (com nomes dos times)
    $cStmt = $pdo->prepare(
        "SELECT c.*, t.nome
         FROM ecofut_classificacao c
         JOIN ecofut_times t ON t.id = c.time_id
         WHERE c.save_id = ? AND c.divisao = 1
         ORDER BY c.pontos DESC, (c.gols_pro - c.gols_contra) DESC, c.gols_pro DESC"
    );
    $cStmt->execute([$save_id]);
    $page_data['classificacao'] = $cStmt->fetchAll(PDO::FETCH_ASSOC);

    // Partidas com nomes (rodadas atuais + anteriores para exibição)
    $pStmt = $pdo->prepare(
        "SELECT p.*,
                tc.nome AS nome_casa, tf.nome AS nome_fora
         FROM ecofut_partidas p
         JOIN ecofut_times tc ON tc.id = p.time_casa_id
         JOIN ecofut_times tf ON tf.id = p.time_fora_id
         WHERE p.save_id = ?
         ORDER BY p.rodada ASC, p.id ASC"
    );
    $pStmt->execute([$save_id]);
    $page_data['partidas'] = $pStmt->fetchAll(PDO::FETCH_ASSOC);

    // Próxima partida
    $proxStmt = $pdo->prepare(
        "SELECT p.*, tc.nome AS nome_casa, tf.nome AS nome_fora
         FROM ecofut_partidas p
         JOIN ecofut_times tc ON tc.id = p.time_casa_id
         JOIN ecofut_times tf ON tf.id = p.time_fora_id
         WHERE p.save_id = ? AND p.rodada = ? AND p.status = 'agendada'
           AND (p.time_casa_id = ? OR p.time_fora_id = ?)
         LIMIT 1"
    );
    $proxStmt->execute([$save_id, $rodada, $timeId, $timeId]);
    $page_data['proxima_partida'] = $proxStmt->fetch(PDO::FETCH_ASSOC) ?: null;

    // Se não há partida do usuário agendada, busca qualquer jogo agendado
    if (!$page_data['proxima_partida']) {
        $qqStmt = $pdo->prepare(
            "SELECT p.*, tc.nome AS nome_casa, tf.nome AS nome_fora
             FROM ecofut_partidas p
             JOIN ecofut_times tc ON tc.id = p.time_casa_id
             JOIN ecofut_times tf ON tf.id = p.time_fora_id
             WHERE p.save_id = ? AND p.rodada = ? AND p.status = 'agendada'
             LIMIT 1"
        );
        $qqStmt->execute([$save_id, $rodada]);
        $page_data['proxima_partida'] = $qqStmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // Finanças (últimas 100 entradas)
    $fStmt = $pdo->prepare(
        "SELECT * FROM ecofut_financas WHERE save_id = ?
         ORDER BY created_at ASC LIMIT 100"
    );
    $fStmt->execute([$save_id]);
    $page_data['financas'] = $fStmt->fetchAll(PDO::FETCH_ASSOC);

    // Mercado: jogadores de outros times (não no elenco do usuário), limitado
    $mStmt = $pdo->prepare(
        "SELECT jb.* FROM ecofut_jogadores_base jb
         WHERE jb.time_id != ?
           AND jb.id NOT IN (
               SELECT jogador_base_id FROM ecofut_elenco
               WHERE save_id = ? AND jogador_base_id IS NOT NULL
           )
         ORDER BY jb.forca DESC
         LIMIT 60"
    );
    $mStmt->execute([$timeId, $save_id]);
    $page_data['mercado'] = $mStmt->fetchAll(PDO::FETCH_ASSOC);

    // Log da última partida simulada (da sessão)
    if (isset($_SESSION['ecofut_log_partida'])) {
        $page_data['log_partida'] = $_SESSION['ecofut_log_partida'];
        unset($_SESSION['ecofut_log_partida']);
    }

    endif; // if ($saveRow)
}
