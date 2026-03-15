<?php
$app_data = [];

if ($page === 'app') {
    $gm_id_logado = $_SESSION['gm_id'] ?? 1;
    $cap_limit = 704;

    if ($db_connected) {
        $sprint_atual = $pdo->query("SELECT sprint_atual FROM config_sistema")->fetchColumn();
        $gms = $pdo->query("SELECT id, nome as name, nome_do_time as teamName, pontos as points, trade_count FROM gm ORDER BY pontos DESC")->fetchAll();

        $stmt = $pdo->prepare("SELECT j.id, j.nome as name, j.posicao as pos, j.overall as ovr, j.idade, e.is_titular FROM jogador j JOIN elenco e ON j.id = e.jogador_id WHERE e.gm_id = ? ORDER BY e.is_titular DESC, j.overall DESC");
        $stmt->execute([$gm_id_logado]);
        $players = $stmt->fetchAll();

        $all_players = $pdo->query("SELECT j.id, j.nome as name, j.posicao as pos, j.overall as ovr, j.idade, IFNULL(g.nome_do_time, 'Livre') as gm_dono FROM jogador j LEFT JOIN elenco e ON j.id = e.jogador_id LEFT JOIN gm g ON e.gm_id = g.id ORDER BY j.overall DESC")->fetchAll();

        $stmtCap = $pdo->prepare("SELECT SUM(overall) as top_8_sum FROM (SELECT j.overall FROM jogador j JOIN elenco e ON j.id = e.jogador_id WHERE e.gm_id = ? ORDER BY j.overall DESC LIMIT 8) as subquery");
        $stmtCap->execute([$gm_id_logado]);
        $cap_sum = $stmtCap->fetchColumn() ?: 0;

        $auctions = $pdo->query("SELECT l.id, j.nome, j.overall, j.posicao, gm.nome_do_time as vendedor, l.data_fim FROM leilao l JOIN jogador j ON l.jogador_id = j.id JOIN gm ON l.gm_vendedor_id = gm.id WHERE l.status = 'aberto' AND l.data_fim > NOW()")->fetchAll();

        $minha_posicao = 1;
        foreach ($gms as $index => $g) {
            if ($g['id'] == $gm_id_logado) {
                $minha_posicao = $index + 1;
            }
        }

        $meu_gm = array_filter($gms, fn($g) => $g['id'] == $gm_id_logado);
        $meu_gm = reset($meu_gm);
    } else {
        $sprint_atual = 1;
        $gms = [];
        $players = [];
        $all_players = [];
        $cap_sum = 0;
        $auctions = [];
        $meu_gm = ['trade_count' => 0];
        $minha_posicao = 1;
    }

    $app_data = [
        'sprint' => $sprint_atual,
        'max_sprints' => 15,
        'db_status' => $db_connected,
        'gm_logado' => [
            'id' => $gm_id_logado,
            'nome_time' => $_SESSION['gm_name'],
            'trade_count' => $meu_gm['trade_count'] ?? 0,
            'max_trades' => 10,
            'cap_sum' => (int)$cap_sum,
            'cap_limit' => $cap_limit,
            'cap_ok' => ((int)$cap_sum <= $cap_limit),
            'posicao_ranking' => $minha_posicao,
            'pontos' => $meu_gm['points'] ?? 0,
            'total_atletas' => count($players)
        ],
        'gms' => $gms,
        'players' => $players,
        'all_players' => $all_players,
        'auctions' => $auctions,
        'formations' => ['4-3-3', '4-4-2', '4-2-3-1', '3-5-2', '5-3-2']
    ];
}
