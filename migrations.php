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
            temporada INT DEFAULT 1,
            saldo BIGINT DEFAULT 10000000,
            dados_json LONGTEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_usuario_slot (usuario_id, slot),
            FOREIGN KEY (usuario_id) REFERENCES ecofut_usuarios(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    ];

    foreach ($tabelas as $sql) {
        try {
            $pdo->exec($sql);
        } catch (Exception $e) {
            error_log('EcoFut migration error: ' . $e->getMessage());
        }
    }
}
