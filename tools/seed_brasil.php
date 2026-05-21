<?php
/**
 * Seed dos times brasileiros reais — EcoFut
 * Série A (20 times, div=1) + Série B (20 times, div=2)
 * Usa BanParser para times com arquivo .ban; geração sintética para os demais.
 */

require_once __DIR__ . '/parse_ban.php';

// ── Definição dos 40 times ────────────────────────────────────────────────────

function definirTimesBrasileiros(): array
{
    // [slug, ban_file|null, nome, apelido, estado, forca_base, cor1, cor2, estadio, capacidade]
    $serieA = [
        ['flamengo',    null,                    'Flamengo',            'FLA', 'RJ', 88, '#CC0000', '#000000', 'Maracanã',              78000],
        ['palmeiras',   null,                    'Palmeiras',           'PAL', 'SP', 87, '#006400', '#FFFFFF', 'Allianz Parque',        43000],
        ['atleticomg',  'atleticomg_bra.ban',    'Atlético MG',         'CAM', 'MG', 86, '#000000', '#FFFFFF', 'Arena MRV',             46000],
        ['corinthians', 'corinthians_bra.ban',   'Corinthians',         'COR', 'SP', 83, '#000000', '#FFFFFF', 'Neo Química Arena',     47000],
        ['fluminense',  null,                    'Fluminense',          'FLU', 'RJ', 81, '#8B0000', '#3CB371', 'Maracanã',              78000],
        ['saopaulo',    'saopaulo_bra.ban',      'São Paulo',           'SPF', 'SP', 80, '#CC0000', '#000000', 'MorumBIS',              70000],
        ['internacional','internacional_bra.ban','Internacional',        'INT', 'RS', 79, '#CC0000', '#FFFFFF', 'Beira-Rio',             51000],
        ['gremio',      null,                    'Grêmio',              'GRE', 'RS', 78, '#003087', '#000000', 'Arena do Grêmio',       55000],
        ['botafogorj',  'botafogorj_bra.ban',    'Botafogo',            'BOT', 'RJ', 77, '#000000', '#FFFFFF', 'Nilton Santos',         46000],
        ['cruzeiro',    'cruzeiro_bra.ban',      'Cruzeiro',            'CRU', 'MG', 76, '#003087', '#FFFFFF', 'Mineirão',              62000],
        ['atleticopr',  'atleticopr_bra.ban',    'Athletico PR',        'CAP', 'PR', 74, '#CC0000', '#000000', 'Ligga Arena',           42000],
        ['bahia',       null,                    'Bahia',               'BAH', 'BA', 73, '#0000CD', '#CC0000', 'Fonte Nova',            50000],
        ['bragantino',  'bragantino_bra.ban',    'Red Bull Bragantino', 'RBB', 'SP', 73, '#CC0000', '#FFFFFF', 'Nabi Abi Chedid',       22000],
        ['fortaleza',   null,                    'Fortaleza',           'FOR', 'CE', 72, '#0000CD', '#CC0000', 'Castelão',              63000],
        ['vasco',       null,                    'Vasco da Gama',       'VAS', 'RJ', 71, '#000000', '#FFFFFF', 'São Januário',          21000],
        ['cuiaba',      'cuiaba_bra.ban',        'Cuiabá',              'CUI', 'MT', 68, '#FFD700', '#008000', 'Arena Pantanal',        40000],
        ['atleticogo',  'atleticogo_bra.ban',    'Atlético GO',         'ACG', 'GO', 67, '#CC0000', '#000000', 'Serra Dourada',         11000],
        ['criciuma',    'criciuma_bra.ban',      'Criciúma',            'CRI', 'SC', 66, '#FFD700', '#000000', 'Heriberto Hülse',       19000],
        ['vitoria',     null,                    'Vitória',             'VIT', 'BA', 65, '#CC0000', '#000000', 'Barradão',              35000],
        ['juventude',   null,                    'Juventude',           'JUV', 'RS', 64, '#006400', '#FFFFFF', 'Alfredo Jaconi',        19000],
    ];

    $serieB = [
        ['santos',       null,                   'Santos',              'SAN', 'SP', 73, '#000000', '#FFFFFF', 'Vila Belmiro',          16000],
        ['americamg',    'americamg_bra.ban',    'América MG',          'AME', 'MG', 70, '#006400', '#FFFFFF', 'Arena Independência',   23000],
        ['sport',        null,                   'Sport Recife',        'SPO', 'PE', 69, '#CC0000', '#000000', 'Ilha do Retiro',        25000],
        ['goias',        null,                   'Goiás',               'GOI', 'GO', 68, '#008000', '#FFFFFF', 'Serrinha',              13000],
        ['coritiba',     'coritiba_bra.ban',     'Coritiba',            'CFC', 'PR', 67, '#006400', '#FFFFFF', 'Couto Pereira',         41000],
        ['ceara',        'ceara_bra.ban',        'Ceará',               'CEA', 'CE', 67, '#000000', '#FFFFFF', 'Castelão',              63000],
        ['chapecoense',  'chapecoense_bra.ban',  'Chapecoense',         'CHA', 'SC', 65, '#006400', '#FFFFFF', 'Arena Condá',           22000],
        ['avai',         'avai_bra.ban',         'Avaí',                'AVI', 'SC', 64, '#0000CD', '#FFFFFF', 'Ressacada',             18000],
        ['guaranisp',    'guaranisp_bra.ban',    'Guarani',             'GUA', 'SP', 63, '#008000', '#FFFFFF', 'Brinco de Ouro',        20000],
        ['pontepreta',   'pontepreta_bra.ban',   'Ponte Preta',         'MAC', 'SP', 63, '#000000', '#FFFFFF', 'Moisés Lucarelli',      19000],
        ['crb',          'crb_bra.ban',          'CRB',                 'CRB', 'AL', 62, '#000080', '#CC0000', 'Rei Pelé',              32000],
        ['csa',          'csa_bra.ban',          'CSA',                 'CSA', 'AL', 61, '#0000CD', '#000000', 'Rei Pelé',              32000],
        ['botafogosp',   'botafogosp_bra.ban',   'Botafogo SP',         'BFG', 'SP', 61, '#000000', '#FFFFFF', 'Santa Cruz',            12000],
        ['saocaetano',   'saocaetano_bra.ban',   'São Caetano',         'SAO', 'SP', 60, '#0000CD', '#CC0000', 'Anacleto Campanella',   18000],
        ['brusquesc',    'brusquesc_bra.ban',    'Brusque',             'BRU', 'SC', 60, '#0000CD', '#CC0000', 'Augusto Bauer',          5000],
        ['nautico',      null,                   'Náutico',             'NAU', 'PE', 62, '#CC0000', '#FFFFFF', 'Aflitos',               12000],
        ['novorizontino',null,                   'Novorizontino',       'NOV', 'SP', 61, '#000080', '#FFFFFF', 'Jorge Ismael de Biasi', 15000],
        ['operariopr',   null,                   'Operário PR',         'OPE', 'PR', 62, '#000000', '#FFFFFF', 'Germano Krüger',        16000],
        ['sampaio',      null,                   'Sampaio Corrêa',      'SAC', 'MA', 60, '#CC0000', '#000000', 'Castelão MA',           11000],
        ['mirassol',     null,                   'Mirassol',            'MIR', 'SP', 60, '#FFD700', '#000000', 'José Maria de Campos',  10000],
    ];

    return [$serieA, $serieB];
}

// ── Mapeamento de skills por posição ─────────────────────────────────────────

function skillsPorPosicao(string $pos, int $forca, int $seed): array
{
    $s = $seed;
    $lcg = function (int &$s) { $s = ($s * 1103515245 + 12345) & 0x7fffffff; return $s; };
    $rnd = function (int $min, int $max) use (&$s, $lcg) { $lcg($s); return $min + ($s % ($max - $min + 1)); };

    $mult = [
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
    ][$pos] ?? ['sk_goleiro'=>0.05,'sk_agilidade'=>0.65,'sk_passe'=>0.65,'sk_armacao'=>0.55,'sk_desarme'=>0.65,'sk_finalizacao'=>0.45,'sk_tecnica'=>0.65];

    $sk = [];
    foreach ($mult as $k => $m) {
        $noise = $rnd(-5, 5);
        $sk[$k] = max(1, min(99, (int)round($forca * $m + $noise)));
    }
    return $sk;
}

// ── Geração sintética de elenco ───────────────────────────────────────────────

function gerarElencoSintetico(int $forca, int $timeSeed): array
{
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

    $idadeRange = [
        'GOL'=>[24,36],'ZAG'=>[21,34],'LD'=>[19,33],'LE'=>[19,33],
        'VOL'=>[20,34],'MC'=>[19,32],'MEI'=>[19,31],
        'PE'=>[18,30],'PD'=>[18,30],'ATA'=>[18,33],
    ];

    $primeiroNomes = ['Gabriel','Lucas','Felipe','João','Rafael','Mateus','Pedro','André','Diego','Thiago',
        'Carlos','Paulo','Marcos','Bruno','Gustavo','Rodrigo','Daniel','Leandro','Eduardo','Roberto',
        'Victor','Alan','Wendell','Everton','Douglas','Wesley','Willian','Vinicius','Renan','Caio',
        'Henrique','Leonardo','Ederson','Alisson','Cássio','Murilo','Davi','Natan','Igor','Arthur',
        'Enzo','Emerson','Luan','Yago','Júnior','Patrick','Marquinhos','Allan','Thales','Bernardo'];

    $sobrenomes = ['Silva','Santos','Oliveira','Souza','Lima','Costa','Pereira','Ferreira','Rodrigues','Alves',
        'Nascimento','Carvalho','Martins','Araújo','Barbosa','Ribeiro','Rocha','Cardoso','Correia','Mendes',
        'Gomes','Batista','Castro','Cavalcanti','Moura','Medeiros','Machado','Xavier','Nogueira','Melo',
        'Ramos','Monteiro','Dias','Farias','Borges','Andrade','Teixeira','Moreira','Neto','Júnior',
        'Viana','Pinto','Cunha','Lemos','Freitas','Queiroz','Azevedo','Miranda','Campos','Brito'];

    $s    = $timeSeed;
    $lcg  = function () use (&$s) { $s = ($s * 1103515245 + 12345) & 0x7fffffff; return $s; };
    $jogadores = [];
    $nomesUsados = [];

    foreach ($template as $posIdx => $pos) {
        do {
            $fn  = $primeiroNomes[$lcg() % count($primeiroNomes)];
            $ln  = $sobrenomes[$lcg()   % count($sobrenomes)];
            $nome = "$fn $ln";
        } while (in_array($nome, $nomesUsados));
        $nomesUsados[] = $nome;

        $var     = ($lcg() % 21) - 10;
        $isTit   = $posIdx < 11;
        $forcaJ  = max(45, min(95, $forca + $var + ($isTit ? 3 : -4)));

        [$idMin, $idMax] = $idadeRange[$pos];
        $idade = $idMin + ($lcg() % ($idMax - $idMin + 1));

        $salario = (int)(($forcaJ ** 2 / 100) * 800 + 5000);
        $sk      = skillsPorPosicao($pos, $forcaJ, $s);

        $jogadores[] = compact('nome','pos','forcaJ','idade','salario','sk');
    }
    return $jogadores;
}

// ── Extração de elenco do arquivo .ban ───────────────────────────────────────

function extrairElencoDoban(array $timeData, int $forcaBase): array
{
    $posicaoMap = [
        1=>'GOL',2=>'ZAG',3=>'LD',4=>'LE',5=>'VOL',
        6=>'MC',7=>'MEI',8=>'PE',9=>'PD',10=>'ATA',
    ];

    $idadeRange = [
        'GOL'=>[24,36],'ZAG'=>[21,34],'LD'=>[19,33],'LE'=>[19,33],
        'VOL'=>[20,34],'MC'=>[19,32],'MEI'=>[19,31],
        'PE'=>[18,30],'PD'=>[18,30],'ATA'=>[18,33],
    ];

    $jogadores = [];
    $nomesUsados = [];
    $seed = crc32(implode(',', array_column($timeData['jogadores'], 'nome')));

    foreach ($timeData['jogadores'] as $p) {
        $nome = trim($p['nome'] ?? '');
        if (empty($nome) || in_array($nome, $nomesUsados)) continue;
        $nomesUsados[] = $nome;

        $posN    = (int)($p['posicao_n'] ?? 6);
        $posicao = $posicaoMap[$posN] ?? 'MC';

        $forcaRaw = (int)($p['forca'] ?? 0);
        $forcaJ   = $forcaRaw >= 45 ? max(45, min(95, $forcaRaw))
                                    : max(45, min(95, $forcaBase + rand(-8, 8)));

        [$idMin, $idMax] = $idadeRange[$posicao];
        $seed  = ($seed * 1103515245 + 12345) & 0x7fffffff;
        $idade = $idMin + ($seed % ($idMax - $idMin + 1));

        $salario = (int)(($forcaJ ** 2 / 100) * 800 + 5000);
        $sk      = skillsPorPosicao($posicao, $forcaJ, $seed);

        $jogadores[] = ['nome'=>$nome,'pos'=>$posicao,'forcaJ'=>$forcaJ,'idade'=>$idade,'salario'=>$salario,'sk'=>$sk];
    }

    // Garante mínimo de 22 jogadores
    if (count($jogadores) < 22) {
        $extra = gerarElencoSintetico($forcaBase, crc32($timeData['nome'] ?? 'x'));
        foreach ($extra as $j) {
            if (count($jogadores) >= 25) break;
            if (!in_array($j['nome'], $nomesUsados)) {
                $jogadores[]   = $j;
                $nomesUsados[] = $j['nome'];
            }
        }
    }

    return array_slice($jogadores, 0, 25);
}

// ── Seed principal ─────────────────────────────────────────────────────────────

function seedBrasil(PDO $pdo): void
{
    [$serieA, $serieB] = definirTimesBrasileiros();

    $banDir = __DIR__ . '/../teams/';
    $parser = new BanParser();

    $stmtTime = $pdo->prepare(
        "INSERT INTO ecofut_times (slug,nome,apelido,estado,divisao,forca_base,cor1,cor2,estadio,capacidade)
         VALUES (?,?,?,?,?,?,?,?,?,?)"
    );
    $stmtJog = $pdo->prepare(
        "INSERT INTO ecofut_jogadores_base
         (time_id,nome,posicao,forca,idade,salario,sk_goleiro,sk_agilidade,sk_passe,sk_armacao,sk_desarme,sk_finalizacao,sk_tecnica)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)"
    );

    $pdo->beginTransaction();

    foreach ([1 => $serieA, 2 => $serieB] as $divisao => $times) {
        foreach ($times as $idx => [$slug, $banFile, $nome, $apelido, $estado, $forca, $cor1, $cor2, $estadio, $cap]) {
            $stmtTime->execute([$slug, $nome, $apelido, $estado, $divisao, $forca, $cor1, $cor2, $estadio, $cap]);
            $timeId = (int)$pdo->lastInsertId();

            // Tenta carregar do .ban
            $jogadores = [];
            if ($banFile) {
                $banPath = $banDir . $banFile;
                if (file_exists($banPath)) {
                    $obj = $parser->parseFile($banPath);
                    if ($obj) {
                        $timeData = extractTime($obj);
                        if ($timeData && count($timeData['jogadores']) >= 15) {
                            $jogadores = extrairElencoDoban($timeData, $forca);
                        }
                    }
                }
            }

            // Fallback sintético
            if (count($jogadores) < 20) {
                $seedVal   = crc32($slug . $nome);
                $jogadores = gerarElencoSintetico($forca, $seedVal);
            }

            foreach ($jogadores as $j) {
                $sk = $j['sk'];
                $stmtJog->execute([
                    $timeId, $j['nome'], $j['pos'], $j['forcaJ'], $j['idade'], $j['salario'],
                    $sk['sk_goleiro'], $sk['sk_agilidade'], $sk['sk_passe'],
                    $sk['sk_armacao'], $sk['sk_desarme'], $sk['sk_finalizacao'], $sk['sk_tecnica'],
                ]);
            }
        }
    }

    $pdo->commit();
}
