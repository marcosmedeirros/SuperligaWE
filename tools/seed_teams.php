<?php
function seedEcofutTimes(PDO $pdo): void {

    // ── 20 times fictícios Série A ────────────────────────────────────────────
    $times = [
        // slug, nome, apelido, estado, forca, cor1, cor2, estadio, capacidade
        ['dragoes',    'Dragões FC',       'DRG','SP', 82,'#CC0000','#FFD700','Estádio Vulcão',      62000],
        ['leoes',      'Leões EC',         'LEO','RJ', 80,'#FF8C00','#1C1C1C','Arena do Rei',        55000],
        ['fenix',      'Fênix CA',         'FEN','MG', 79,'#8B0000','#FF6600','Arena Fênix',         48000],
        ['falcoes',    'Falcões GE',       'FAL','RS', 76,'#003366','#CCCCCC','Arena das Asas',      50000],
        ['lobos',      'Lobos SC',         'LOB','PE', 75,'#4B0082','#FFFFFF','Estádio do Norte',    40000],
        ['panteras',   'Panteras AF',      'PAN','BA', 74,'#1A1A2E','#FFD700','Arena Pantera',       45000],
        ['tubaraoes',  'Tubarões FC',      'TUB','SC', 73,'#00008B','#FFFFFF','Estádio Oceânico',    38000],
        ['aguias',     'Águias CE',        'AGU','GO', 72,'#006400','#FFD700','Arena das Alturas',   42000],
        ['vulcoes',    'Vulcões Sport',    'VUL','CE', 71,'#8B0000','#FF4500','Estádio do Vulcão',   36000],
        ['trovoes',    'Trovões AF',       'TRO','AM', 70,'#1C1C1C','#00BFFF','Arena Amazônia',      48000],
        ['corsarios',  'Corsários SC',     'COS','RN', 68,'#000080','#FFFFFF','Estádio Costeiro',    28000],
        ['templarios', 'Templários FC',    'TEM','DF', 68,'#8B0000','#CCCCCC','Arena Central',       35000],
        ['samurais',   'Samurais CA',      'SAM','RJ', 67,'#CC0000','#FFFFFF','Estádio Oriente',     32000],
        ['guerreiros', 'Guerreiros EC',    'GUE','PR', 67,'#006400','#FFD700','Arena Guerreira',     30000],
        ['titanicos',  'Titânicos SC',     'TIT','SP', 66,'#4169E1','#FFFFFF','Estádio Titan',       40000],
        ['nomades',    'Nômades GF',       'NOM','MT', 65,'#8B4513','#FFD700','Arena Cerrado',       25000],
        ['ursos',      'Ursos Esporte',    'URS','RS', 63,'#4B0082','#CCCCCC','Estádio Polar',       28000],
        ['eternos',    'Eternos CF',       'ETE','MG', 63,'#2F4F4F','#FFD700','Arena Eterna',        32000],
        ['fantasmas',  'Fantasmas AC',     'FAN','GO', 62,'#1C1C1C','#00FF7F','Estádio Sombra',      22000],
        ['nubivagos',  'Nubivagos FC',     'NUB','SC', 61,'#483D8B','#FFFFFF','Arena das Nuvens',    24000],
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

    try {
    $pdo->beginTransaction();

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

    $pdo->commit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e; // relança para o caller registrar o erro
    }
}
