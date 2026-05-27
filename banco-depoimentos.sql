CREATE TABLE IF NOT EXISTS depoimentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  cidade VARCHAR(150) NOT NULL,
  rede_social VARCHAR(255) NULL,
  foto VARCHAR(255) NULL,
  depoimento TEXT NOT NULL,
  resposta_admin TEXT NULL,
  respondido_em DATETIME NULL,
  tempo_conhece VARCHAR(100) NOT NULL,
  autorizacao BOOLEAN NOT NULL DEFAULT 0,
  status ENUM('pendente','aprovado','recusado') NOT NULL DEFAULT 'pendente',
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_depoimentos_status (status),
  INDEX idx_depoimentos_criado_em (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS depoimento_reacoes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  depoimento_id INT UNSIGNED NOT NULL,
  tipo ENUM('like','love','haha','sad') NOT NULL,
  identificador_usuario VARCHAR(120) NOT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_reacao_usuario (depoimento_id, identificador_usuario),
  INDEX idx_depoimento_id (depoimento_id),
  INDEX idx_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Para bancos ja existentes sem resposta administrativa, execute uma vez:
-- ALTER TABLE depoimentos
--   ADD COLUMN resposta_admin TEXT NULL AFTER depoimento,
--   ADD COLUMN respondido_em DATETIME NULL AFTER resposta_admin;
