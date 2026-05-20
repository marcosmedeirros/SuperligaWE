<?php
function runMigrations(PDO $pdo): void {
    $tabelas = [
        "CREATE TABLE IF NOT EXISTS ecofut_usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(100) NOT NULL,
            email VARCHAR(150) UNIQUE NOT NULL,
            senha VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS ecofut_saves (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            slot TINYINT NOT NULL,
            nome_treinador VARCHAR(100) NOT NULL,
            nome_time VARCHAR(100) NOT NULL,
            time_id INT DEFAULT NULL,
            temporada INT DEFAULT 1,
            rodada_atual INT DEFAULT 1,
            saldo BIGINT DEFAULT 10000000,
            dados_json LONGTEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_usuario_slot (usuario_id, slot),
            FOREIGN KEY (usuario_id) REFERENCES ecofut_usuarios(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS ecofut_times (
            id INT AUTO_INCREMENT PRIMARY KEY,
            slug VARCHAR(50) UNIQUE NOT NULL,
            nome VARCHAR(100) NOT NULL,
            apelido VARCHAR(10) NOT NULL,
            estado CHAR(2) NOT NULL,
            divisao TINYINT DEFAULT 1,
            forca_base INT DEFAULT 65,
            cor1 VARCHAR(10) DEFAULT '#22c55e',
            cor2 VARCHAR(10) DEFAULT '#ffffff',
            estadio VARCHAR(100) DEFAULT '',
            capacidade INT DEFAULT 30000
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS ecofut_jogadores_base (
            id INT AUTO_INCREMENT PRIMARY KEY,
            time_id INT NOT NULL,
            nome VARCHAR(100) NOT NULL,
            posicao VARCHAR(5) NOT NULL,
            forca INT DEFAULT 60,
            idade INT DEFAULT 25,
            salario INT DEFAULT 50000,
            sk_goleiro INT DEFAULT 0,
            sk_agilidade INT DEFAULT 50,
            sk_passe INT DEFAULT 50,
            sk_armacao INT DEFAULT 50,
            sk_desarme INT DEFAULT 50,
            sk_finalizacao INT DEFAULT 50,
            sk_tecnica INT DEFAULT 50,
            FOREIGN KEY (time_id) REFERENCES ecofut_times(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS ecofut_elenco (
            id INT AUTO_INCREMENT PRIMARY KEY,
            save_id INT NOT NULL,
            time_id INT NOT NULL,
            jogador_base_id INT DEFAULT NULL,
            nome VARCHAR(100) NOT NULL,
            posicao VARCHAR(5) NOT NULL,
            forca INT DEFAULT 60,
            idade INT DEFAULT 25,
            energia INT DEFAULT 100,
            moral INT DEFAULT 75,
            contundido TINYINT DEFAULT 0,
            suspenso TINYINT DEFAULT 0,
            salario INT DEFAULT 50000,
            meses_contrato INT DEFAULT 12,
            titular TINYINT DEFAULT 0,
            sk_goleiro INT DEFAULT 0,
            sk_agilidade INT DEFAULT 50,
            sk_passe INT DEFAULT 50,
            sk_armacao INT DEFAULT 50,
            sk_desarme INT DEFAULT 50,
            sk_finalizacao INT DEFAULT 50,
            sk_tecnica INT DEFAULT 50,
            FOREIGN KEY (save_id) REFERENCES ecofut_saves(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS ecofut_classificacao (
            id INT AUTO_INCREMENT PRIMARY KEY,
            save_id INT NOT NULL,
            time_id INT NOT NULL,
            divisao TINYINT DEFAULT 1,
            pontos INT DEFAULT 0,
            jogos INT DEFAULT 0,
            vitorias INT DEFAULT 0,
            empates INT DEFAULT 0,
            derrotas INT DEFAULT 0,
            gols_pro INT DEFAULT 0,
            gols_contra INT DEFAULT 0,
            UNIQUE KEY uq_save_time_div (save_id, time_id, divisao),
            FOREIGN KEY (save_id) REFERENCES ecofut_saves(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS ecofut_partidas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            save_id INT NOT NULL,
            rodada INT NOT NULL,
            divisao TINYINT DEFAULT 1,
            time_casa_id INT NOT NULL,
            time_fora_id INT NOT NULL,
            gols_casa INT DEFAULT NULL,
            gols_fora INT DEFAULT NULL,
            status ENUM('agendada','jogada') DEFAULT 'agendada',
            log_json TEXT,
            FOREIGN KEY (save_id) REFERENCES ecofut_saves(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS ecofut_financas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            save_id INT NOT NULL,
            categoria VARCHAR(50) NOT NULL,
            descricao VARCHAR(200) NOT NULL,
            valor BIGINT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (save_id) REFERENCES ecofut_saves(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    ];

    foreach ($tabelas as $sql) {
        try { $pdo->exec($sql); } catch (Exception $e) { error_log('EcoFut migration: ' . $e->getMessage()); }
    }

    // Adiciona colunas de estatísticas ao elenco (compatível com MySQL 5.7)
    try {
        $hasCol = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ecofut_elenco' AND COLUMN_NAME = 'partidas'")->fetchColumn();
        if (!$hasCol) {
            $pdo->exec("ALTER TABLE ecofut_elenco
                ADD COLUMN partidas   INT   DEFAULT 0,
                ADD COLUMN gols       INT   DEFAULT 0,
                ADD COLUMN assists    INT   DEFAULT 0,
                ADD COLUMN amarelos   INT   DEFAULT 0,
                ADD COLUMN vermelhos  INT   DEFAULT 0,
                ADD COLUMN nota_total FLOAT DEFAULT 0");
        }
    } catch (Exception $e) { error_log('EcoFut migration stats: ' . $e->getMessage()); }

    // Colunas de potencial e lesões
    try {
        $hasPot = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ecofut_elenco' AND COLUMN_NAME = 'potencial'")->fetchColumn();
        if (!$hasPot) {
            $pdo->exec("ALTER TABLE ecofut_elenco
                ADD COLUMN potencial      INT DEFAULT 70,
                ADD COLUMN lesoes_rodadas INT DEFAULT 0");
        }
    } catch (Exception $e) { error_log('EcoFut migration potencial: ' . $e->getMessage()); }

    // Tabela Copa
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS ecofut_copa (
            id INT AUTO_INCREMENT PRIMARY KEY,
            save_id INT NOT NULL,
            fase ENUM('quartas','semifinal','final') NOT NULL,
            time_casa_id INT NOT NULL,
            time_fora_id INT NOT NULL,
            gols_casa INT DEFAULT NULL,
            gols_fora INT DEFAULT NULL,
            status ENUM('agendada','jogada') DEFAULT 'agendada',
            FOREIGN KEY (save_id) REFERENCES ecofut_saves(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Exception $e) { error_log('EcoFut migration copa: ' . $e->getMessage()); }

    // Seed times se tabela estiver vazia ou com seed incompleto (< 20 times)
    try {
        $timesCount = (int)$pdo->query("SELECT COUNT(*) FROM ecofut_times")->fetchColumn();
        if ($timesCount < 20) {
            // Limpa seed parcial para evitar inconsistência
            if ($timesCount > 0) {
                $pdo->exec("DELETE FROM ecofut_jogadores_base");
                $pdo->exec("DELETE FROM ecofut_times");
            }
            require_once __DIR__ . '/tools/seed_teams.php';
            seedEcofutTimes($pdo);
        }
    } catch (Exception $e) {
        error_log('EcoFut seed error: ' . $e->getMessage());
        // Armazena erro na sessão para diagnóstico
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['ecofut_seed_erro'] = $e->getMessage();
        }
    }
}
