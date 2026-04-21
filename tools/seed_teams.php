<?php
function seedEcofutTimes(PDO $pdo): void {

    // ── 20 times Série A ───────────────────────────────────────────────────────
    $times = [
        // slug, nome, apelido, estado, forca, cor1, cor2, estadio, capacidade
        ['flamengo',     'Flamengo',        'FLA','RJ', 82,'#CC0000','#000000','Maracanã',          78838],
        ['palmeiras',    'Palmeiras',        'PAL','SP', 80,'#006400','#FFFFFF','Allianz Parque',    43713],
        ['atletico_mg',  'Atlético-MG',      'GAL','MG', 79,'#000000','#FFFFFF','Arena MRV',         46000],
        ['fluminense',   'Fluminense',       'FLU','RJ', 76,'#8B0000','#006400','Maracanã',          78838],
        ['botafogo',     'Botafogo',         'BOT','RJ', 75,'#000000','#CCCCCC','Nilton Santos',     46000],
        ['corinthians',  'Corinthians',      'COR','SP', 74,'#000000','#CCCCCC','Neo Química Arena', 47605],
        ['sao_paulo',    'São Paulo',        'SAO','SP', 73,'#CC0000','#FFFFFF','MorumBIS',          72000],
        ['internacional','Internacional',    'INT','RS', 72,'#CC0000','#FFFFFF','Beira-Rio',         50128],
        ['gremio',       'Grêmio',           'GRE','RS', 71,'#0044AA','#000000','Arena Grêmio',      55000],
        ['cruzeiro',     'Cruzeiro',         'CRU','MG', 70,'#001F8A','#FFFFFF','Mineirão',          61846],
        ['vasco',        'Vasco da Gama',    'VAS','RJ', 68,'#000000','#CCCCCC','São Januário',      21000],
        ['santos',       'Santos',           'SAN','SP', 67,'#FFFFFF','#000000','Vila Belmiro',      16798],
        ['athletico_pr', 'Athletico-PR',     'CAP','PR', 68,'#CC0000','#000000','Ligga Arena',       32000],
        ['fortaleza',    'Fortaleza',        'FOR','CE', 67,'#003366','#CC0000','Castelão',          63903],
        ['bragantino',   'Bragantino',       'RBB','SP', 66,'#CC0000','#FFFFFF','Nabi Abi Chedid',   18000],
        ['bahia',        'Bahia',            'BAH','BA', 65,'#0056A2','#CC0000','Arena Fonte Nova',  47907],
        ['sport',        'Sport Recife',     'SPT','PE', 63,'#CC0000','#000000','Ilha do Retiro',    22000],
        ['ceara',        'Ceará',            'CEA','CE', 63,'#000000','#CCCCCC','Castelão',          63903],
        ['coritiba',     'Coritiba',         'COT','PR', 62,'#006400','#FFFFFF','Couto Pereira',     42700],
        ['criciuma',     'Criciúma',         'CRI','SC', 61,'#FFD700','#000000','Heriberto Hulse',   21000],
    ];

    $primeiroNomes = ['Gabriel','Lucas','Felipe','João','Rafael','Mateus','Pedro','André','Diego','Thiago',
        'Carlos','Paulo','Marcos','Bruno','Gustavo','Rodrigo','Daniel','Leandro','Eduardo','Roberto',
        'Victor','Alan','Wendell','Everton','Douglas','Wesley','Willian','Vinicius','Renan','Caio',
        'Henrique','Leonardo','Ederson','Alisson','Cássio','Murilo','Davi','Natan','Igor','Arthur',
        'Enzo','Ronaldo','Emerson','Luan','Yago','Júnior','Patrick','Marquinhos','Allan','Thales'];

    $sobrenomes = ['Silva','Santos','Oliveira','Souza','Lima','Costa','Pereira','Ferreira','Rodrigues','Alves',
        'Nascimento','Carvalho','Martins','Araújo','Barbosa','Ribeiro','Rocha','Cardoso','Correia','Mendes',
        'Gomes','Batista','Castro','Cavalcanti','Moura','Medeiros','Machado','Xavier','Nogueira','Melo',
        'Ramos','Monteiro','Dias','Farias','Borges','Andrade','Teixeira','Moreira','Neto','Júnior',
        'Viana','Pinto','Cunha','Lemos','Freitas','Queiroz','Azevedo','Miranda','Campos','Brito'];

    // Posições por ordem no squad (25 jogadores)
    $template = [
        'GOL','GOL','GOL',
        'ZAG','ZAG','ZAG',
        'LD','LD','LE','LE',
        'VOL','VOL','VOL',
        'MC','MC',
        'MEI','MEI',
        'PE','PE','PD','PD',
        'ATA','ATA','ATA','ATA',
    ];

    // Multiplicadores de habilidade por posição
    $skillMult = [
        'GOL' => ['sk_goleiro'=>1.0,'sk_agilidade'=>0.55,'sk_passe'=>0.35,'sk_armacao'=>0.20,'sk_desarme'=>0.25,'sk_finalizacao'=>0.15,'sk_tecnica'=>0.40],
        'ZAG' => ['sk_goleiro'=>0.05,'sk_agilidade'=>0.55,'sk_passe'=>0.55,'sk_armacao'=>0.30,'sk_desarme'=>0.95,'sk_finalizacao'=>0.30,'sk_tecnica'=>0.50],
        'LD'  => ['sk_goleiro'=>0.05,'sk_agilidade'=>0.90,'sk_passe'=>0.60,'sk_armacao'=>0.40,'sk_desarme'=>0.70,'sk_finalizacao'=>0.40,'sk_tecnica'=>0.60],
        'LE'  => ['sk_goleiro'=>0.05,'sk_agilidade'=>0.90,'sk_passe'=>0.60,'sk_armacao'=>0.40,'sk_desarme'=>0.70,'sk_finalizacao'=>0.40,'sk_tecnica'=>0.60],
        'VOL' => ['sk_goleiro'=>0.05,'sk_agilidade'=>0.65,'sk_passe'=>0.70,'sk_armacao'=>0.50,'sk_desarme'=>0.95,'sk_finalizacao'=>0.30,'sk_tecnica'=>0.60],
        'MC'  => ['sk_goleiro'=>0.05,'sk_agilidade'=>0.65,'sk_passe'=>0.95,'sk_armacao'=>0.70,'sk_desarme'=>0.55,'sk_finalizacao'=>0.45,'sk_tecnica'=>0.70],
        'MEI' => ['sk_goleiro'=>0.05,'sk_agilidade'=>0.70,'sk_passe'=>0.80,'sk_armacao'=>0.95,'sk_desarme'=>0.35,'sk_finalizacao'=>0.65,'sk_tecnica'=>0.80],
        'PE'  => ['sk_goleiro'=>0.05,'sk_agilidade'=>0.95,'sk_passe'=>0.50,'sk_armacao'=>0.60,'sk_desarme'=>0.20,'sk_finalizacao'=>0.70,'sk_tecnica'=>0.80],
        'PD'  => ['sk_goleiro'=>0.05,'sk_agilidade'=>0.95,'sk_passe'=>0.50,'sk_armacao'=>0.60,'sk_desarme'=>0.20,'sk_finalizacao'=>0.70,'sk_tecnica'=>0.80],
        'ATA' => ['sk_goleiro'=>0.05,'sk_agilidade'=>0.80,'sk_passe'=>0.40,'sk_armacao'=>0.50,'sk_desarme'=>0.20,'sk_finalizacao'=>0.98,'sk_tecnica'=>0.70],
    ];

    // Idade típica por posição (min, max)
    $idadeRange = [
        'GOL'=>[24,36],'ZAG'=>[21,34],'LD'=>[19,33],'LE'=>[19,33],
        'VOL'=>[20,34],'MC'=>[19,32],'MEI'=>[19,31],
        'PE'=>[18,30],'PD'=>[18,30],'ATA'=>[18,33],
    ];

    $usedNames = [];

    $stmtTime = $pdo->prepare(
        "INSERT INTO ecofut_times (slug,nome,apelido,estado,divisao,forca_base,cor1,cor2,estadio,capacidade)
         VALUES (?,?,?,?,1,?,?,?,?,?)"
    );
    $stmtJog = $pdo->prepare(
        "INSERT INTO ecofut_jogadores_base
         (time_id,nome,posicao,forca,idade,salario,sk_goleiro,sk_agilidade,sk_passe,sk_armacao,sk_desarme,sk_finalizacao,sk_tecnica)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)"
    );

    $seed = 42; // determinístico

    foreach ($times as $idx => [$slug,$nome,$apelido,$estado,$forca,$cor1,$cor2,$estadio,$cap]) {
        $stmtTime->execute([$slug,$nome,$apelido,$estado,$forca,$cor1,$cor2,$estadio,$cap]);
        $timeId = (int)$pdo->lastInsertId();

        foreach ($template as $posIdx => $pos) {
            // Nome único
            do {
                $seed = ($seed * 1103515245 + 12345) & 0x7fffffff;
                $fn  = $primeiroNomes[$seed % count($primeiroNomes)];
                $seed = ($seed * 1103515245 + 12345) & 0x7fffffff;
                $ln  = $sobrenomes[$seed % count($sobrenomes)];
                $nomeCand = "$fn $ln";
            } while (in_array($nomeCand, $usedNames));
            $usedNames[] = $nomeCand;

            // Força do jogador: base ± variação
            $seed = ($seed * 1103515245 + 12345) & 0x7fffffff;
            $var  = ($seed % 21) - 10; // -10 a +10
            // Starters (primeiros de cada posição) são mais fortes
            $isTitular = ($posIdx < 11);
            $forcaJ = max(45, min(95, $forca + $var + ($isTitular ? 3 : -3)));

            // Idade
            [$idMin, $idMax] = $idadeRange[$pos];
            $seed  = ($seed * 1103515245 + 12345) & 0x7fffffff;
            $idade = $idMin + ($seed % ($idMax - $idMin + 1));

            // Skills derivados da posição
            $mult = $skillMult[$pos];
            $sk = [];
            foreach ($mult as $k => $m) {
                $seed  = ($seed * 1103515245 + 12345) & 0x7fffffff;
                $noise = ($seed % 11) - 5; // ±5
                $sk[$k] = max(1, min(99, (int)round($forcaJ * $m + $noise)));
            }

            // Salário: proporcional à força
            $salario = (int)(($forcaJ ** 2 / 100) * 800 + 5000);

            $stmtJog->execute([
                $timeId, $nomeCand, $pos, $forcaJ, $idade, $salario,
                $sk['sk_goleiro'], $sk['sk_agilidade'], $sk['sk_passe'],
                $sk['sk_armacao'], $sk['sk_desarme'], $sk['sk_finalizacao'], $sk['sk_tecnica'],
            ]);
        }
    }
}
