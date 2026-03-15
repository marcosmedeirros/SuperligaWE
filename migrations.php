<?php
function runMigrations(PDO $pdo): void {
    $sql = "
    CREATE TABLE IF NOT EXISTS config_sistema ( id INT PRIMARY KEY, sprint_atual INT DEFAULT 1 );
    CREATE TABLE IF NOT EXISTS gm (
        id INT AUTO_INCREMENT PRIMARY KEY, nome VARCHAR(100) NOT NULL, nome_do_time VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL, senha VARCHAR(255) NOT NULL, pontos INT DEFAULT 0,
        trade_count INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE IF NOT EXISTS jogador (
        id INT AUTO_INCREMENT PRIMARY KEY, nome VARCHAR(100) NOT NULL, posicao VARCHAR(10) NOT NULL,
        overall INT NOT NULL, idade INT DEFAULT 25
    );
    CREATE TABLE IF NOT EXISTS elenco (
        id INT AUTO_INCREMENT PRIMARY KEY, gm_id INT NOT NULL, jogador_id INT NOT NULL, is_titular BOOLEAN DEFAULT FALSE,
        FOREIGN KEY (gm_id) REFERENCES gm(id) ON DELETE CASCADE, FOREIGN KEY (jogador_id) REFERENCES jogador(id) ON DELETE CASCADE
    );
    CREATE TABLE IF NOT EXISTS leilao (
        id INT AUTO_INCREMENT PRIMARY KEY, jogador_id INT NOT NULL, gm_vendedor_id INT NOT NULL,
        data_fim DATETIME NOT NULL, status VARCHAR(20) DEFAULT 'aberto',
        FOREIGN KEY (jogador_id) REFERENCES jogador(id) ON DELETE CASCADE, FOREIGN KEY (gm_vendedor_id) REFERENCES gm(id) ON DELETE CASCADE
    );
    CREATE TABLE IF NOT EXISTS leilao_oferta (
        id INT AUTO_INCREMENT PRIMARY KEY, leilao_id INT NOT NULL, gm_comprador_id INT NOT NULL,
        jogador_oferecido_id INT NOT NULL, status VARCHAR(20) DEFAULT 'pendente',
        FOREIGN KEY (leilao_id) REFERENCES leilao(id) ON DELETE CASCADE, FOREIGN KEY (gm_comprador_id) REFERENCES gm(id) ON DELETE CASCADE,
        FOREIGN KEY (jogador_oferecido_id) REFERENCES jogador(id) ON DELETE CASCADE
    );
    ";
    try {
        $pdo->exec($sql);
    } catch (Exception $e) {
        error_log($e->getMessage());
    }

    try {
        $pdo->exec("ALTER TABLE jogador ADD COLUMN idade INT DEFAULT 25");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE jogador DROP COLUMN time_real");
    } catch (Exception $e) {
    }
}

function seedDatabaseIfNeeded(PDO $pdo): void {
    $stmt = $pdo->query("SELECT COUNT(*) FROM gm");
    if ((int)$stmt->fetchColumn() === 0) {
        $pdo->exec("INSERT INTO config_sistema (id, sprint_atual) VALUES (1, 1)");
        $hash = password_hash('123456', PASSWORD_DEFAULT);
        for ($i = 1; $i <= 20; $i++) {
            $pdo->exec("INSERT INTO gm (nome, nome_do_time, email, senha, pontos) VALUES ('Usuario $i', 'Franquia $i', 'gm$i@email.com', '$hash', " . rand(10, 50) . ")");
        }
        $posicoes = ['GOL', 'ZAG', 'ZAG', 'LD', 'LE', 'VOL', 'MC', 'MEI', 'PE', 'PD', 'ATA', 'GOL', 'ZAG', 'MC', 'ATA', 'PE'];
        $jogador_id = 1;
        for ($gm_id = 1; $gm_id <= 20; $gm_id++) {
            foreach ($posicoes as $index => $pos) {
                $ovr = rand(80, 93);
                $idade = rand(18, 35);
                $nome = "Jogador " . $jogador_id;
                $pdo->exec("INSERT INTO jogador (nome, posicao, overall, idade) VALUES ('$nome', '$pos', $ovr, $idade)");
                $is_titular = ($index < 11) ? 1 : 0;
                $pdo->exec("INSERT INTO elenco (gm_id, jogador_id, is_titular) VALUES ($gm_id, $jogador_id, $is_titular)");
                $jogador_id++;
            }
        }
        $pdo->exec("UPDATE gm SET nome = 'Marcos Medeiros', email='admin@admin.com' WHERE id = 1");
    }
}
