<?php
/**
 * Parser de arquivos .ban do Brasfoot (Java Serialization)
 * Extrai dados de times e jogadores para o EcoFut
 */

class BanParser {
    private string $data;
    private int    $pos;
    private array  $handles = [];   // reference table
    private int    $nextHandle = 0x7e0000;

    public function parseFile(string $path): ?array {
        $raw = file_get_contents($path);
        if (!$raw || strlen($raw) < 4) return null;
        // magic ACED 0005
        if (ord($raw[0]) !== 0xAC || ord($raw[1]) !== 0xED) return null;

        $this->data    = $raw;
        $this->pos     = 4;
        $this->handles = [];
        $this->nextHandle = 0x7e0000;

        return $this->readContent();
    }

    // ── Low-level readers ────────────────────────────────────────────────────

    private function readU8(): int {
        return ord($this->data[$this->pos++]);
    }
    private function readI8(): int {
        $v = ord($this->data[$this->pos++]);
        return $v > 127 ? $v - 256 : $v;
    }
    private function readU16(): int {
        $a = ord($this->data[$this->pos++]);
        $b = ord($this->data[$this->pos++]);
        return ($a << 8) | $b;
    }
    private function readI32(): int {
        $bytes = substr($this->data, $this->pos, 4);
        $this->pos += 4;
        $v = unpack('N', $bytes)[1];
        return $v > 0x7fffffff ? $v - 0x100000000 : $v;
    }
    private function readI64(): string {
        $bytes = substr($this->data, $this->pos, 8);
        $this->pos += 8;
        return bin2hex($bytes); // return as hex string
    }
    private function readBool(): bool {
        return $this->readU8() !== 0;
    }
    private function readUtf(): string {
        $len = $this->readU16();
        $s   = substr($this->data, $this->pos, $len);
        $this->pos += $len;
        return $s;
    }

    // ── Java serialisation protocol ──────────────────────────────────────────

    private function newHandle(mixed $obj): int {
        $h = $this->nextHandle++;
        $this->handles[$h] = $obj;
        return $h;
    }

    private function readContent(): mixed {
        $tc = $this->readU8();
        return $this->dispatchTC($tc);
    }

    private function dispatchTC(int $tc): mixed {
        switch ($tc) {
            case 0x73: return $this->readNewObject();
            case 0x74: return $this->readNewString();
            case 0x75: return $this->readNewArray();
            case 0x71: return $this->readReference();
            case 0x70: return null;             // TC_NULL
            case 0x77: return $this->skipBlock(); // TC_BLOCKDATA
            case 0x7a: return $this->skipBlockLong(); // TC_BLOCKDATALONG
            default:   return null;
        }
    }

    private function readNewString(): string {
        $s = $this->readUtf();
        $this->newHandle($s);
        return $s;
    }

    private function readReference(): mixed {
        $h = $this->readI32();
        return $this->handles[$h] ?? null;
    }

    private function skipBlock(): null {
        $len = $this->readU8();
        $this->pos += $len;
        return null;
    }
    private function skipBlockLong(): null {
        $len = $this->readI32();
        $this->pos += $len;
        return null;
    }

    // ── Class descriptor ─────────────────────────────────────────────────────

    private function readClassDesc(): ?array {
        $tc = $this->readU8();
        if ($tc === 0x70) return null; // TC_NULL
        if ($tc === 0x71) {            // TC_REFERENCE
            $h = $this->readI32();
            return $this->handles[$h] ?? null;
        }
        if ($tc !== 0x72) return null; // expect TC_CLASSDESC

        $name    = $this->readUtf();
        $uid     = $this->readI64();   // serialVersionUID (skip)
        $flags   = $this->readU8();
        $nFields = $this->readU16();

        $fields = [];
        for ($i = 0; $i < $nFields; $i++) {
            $typeCode = chr($this->readU8());
            $fname    = $this->readUtf();
            $ftype    = null;
            if ($typeCode === 'L' || $typeCode === '[') {
                // object/array field: read className string
                $ftype = $this->readClassNameString();
            }
            $fields[] = ['name' => $fname, 'type' => $typeCode, 'cls' => $ftype];
        }

        // TC_ENDBLOCKDATA
        $this->readU8(); // 0x78
        // superclass
        $super = $this->readClassDesc();

        $desc = ['name' => $name, 'fields' => $fields, 'super' => $super];
        $this->newHandle($desc);
        return $desc;
    }

    private function readClassNameString(): string {
        $tc = $this->readU8();
        if ($tc === 0x74) { // TC_STRING
            $s = $this->readUtf();
            $this->newHandle($s);
            return $s;
        }
        if ($tc === 0x71) { // TC_REFERENCE
            $h = $this->readI32();
            return $this->handles[$h] ?? '';
        }
        return '';
    }

    // ── Object ───────────────────────────────────────────────────────────────

    private function readNewObject(): array {
        $desc   = $this->readClassDesc();
        $handle = $this->newHandle([]); // placeholder

        $obj = $this->readClassData($desc);
        $this->handles[$handle] = $obj;
        return $obj;
    }

    private function readClassData(?array $desc): array {
        if ($desc === null) return [];

        // Superclass data first
        $obj = $this->readClassData($desc['super'] ?? null);

        // Split fields: primitives first (alphabetical), then objects
        $primFields = array_filter($desc['fields'], fn($f) => $f['type'] !== 'L' && $f['type'] !== '[');
        $objFields  = array_filter($desc['fields'], fn($f) => $f['type'] === 'L' || $f['type'] === '[');

        foreach ($primFields as $f) {
            $obj[$f['name']] = $this->readPrimitive($f['type']);
        }
        foreach ($objFields as $f) {
            $obj[$f['name']] = $this->readContent();
        }

        return $obj;
    }

    private function readPrimitive(string $type): mixed {
        switch ($type) {
            case 'I': return $this->readI32();
            case 'Z': return $this->readBool();
            case 'B': return $this->readI8();
            case 'S': return (int)(unpack('n', substr($this->data, $this->pos, 2))[1]); // short
                      $this->pos += 2; // unreachable but left for clarity
            case 'J': $this->pos += 8; return 0; // long (skip)
            case 'F': $this->pos += 4; return 0.0; // float (skip)
            case 'D': $this->pos += 8; return 0.0; // double (skip)
            case 'C': $this->pos += 2; return ''; // char (skip)
            default:  return null;
        }
    }

    // ── Array ────────────────────────────────────────────────────────────────

    private function readNewArray(): array {
        $desc   = $this->readClassDesc();
        $handle = $this->newHandle([]);
        $size   = $this->readI32();
        $items  = [];

        $typeCode = isset($desc['name']) ? $desc['name'][1] : 'L'; // e.g. "[I" or "[L..."
        for ($i = 0; $i < $size; $i++) {
            if ($typeCode === 'L' || $typeCode === '[') {
                $items[] = $this->readContent();
            } else {
                $items[] = $this->readPrimitive($typeCode);
            }
        }
        $this->handles[$handle] = $items;
        return $items;
    }
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function posicaoPorCodigo(int $cod): string {
    // Based on empirical analysis of field 'c' values across multiple teams
    $mapa = [
        1 => 'GOL', 2 => 'ZAG', 3 => 'LD',  4 => 'LE',
        5 => 'VOL', 6 => 'MC',  7 => 'MEI', 8 => 'PE',
        9 => 'PD', 10 => 'ATA',
    ];
    return $mapa[$cod] ?? 'MC';
}

function extractTime(array $obj): ?array {
    // Team object (e.t) fields: a,aid,b,c,g,i,id,n,o,sid,tid (ints), valid (bool), vid (int)
    // Strings: cor1,cor2,d(slug),e(name),f(stadium),h(coach)
    // List: l (players), m (string)
    if (!isset($obj['e'])) return null;

    $players = [];
    $lista = $obj['l'] ?? [];
    if (is_array($lista)) {
        foreach ($lista as $p) {
            if (!is_array($p) || !isset($p['a'])) continue;
            $players[] = [
                'nome'      => $p['a'],
                'forca'     => $p['d'] ?? 0,
                'posicao_n' => $p['c'] ?? 0,  // will be resolved later
                'sk_a'      => $p['e'] ?? 0,  // agilidade?
                'sk_b'      => $p['f'] ?? 0,
                'sk_c'      => $p['g'] ?? 0,
                'sk_d'      => $p['h'] ?? 0,
                'sk_e'      => $p['hash'] ?? 0,
                'sk_f'      => $p['i'] ?? 0,
                'sid'       => $p['sid'] ?? 0,
                'aid'       => $p['aid'] ?? 0,
                'raw'       => $p,
            ];
        }
    }

    return [
        'nome'   => $obj['e'],
        'slug'   => $obj['d'] ?? '',
        'estadio'=> $obj['f'] ?? '',
        'trein'  => $obj['h'] ?? '',
        'cor1'   => $obj['cor1'] ?? '#22c55e',
        'cor2'   => $obj['cor2'] ?? '#000000',
        'jogadores' => $players,
    ];
}

// ── CLI mode: dump a single file ──────────────────────────────────────────────

if (php_sapi_name() === 'cli' && isset($argv[1])) {
    $parser = new BanParser();
    $obj    = $parser->parseFile($argv[1]);
    if (!$obj) { echo "Falha ao parsear.\n"; exit(1); }
    $time = extractTime($obj);
    echo json_encode($time, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}
