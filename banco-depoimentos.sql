CREATE TABLE IF NOT EXISTS depoimentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  cidade VARCHAR(150) NOT NULL,
  rede_social VARCHAR(255) NULL,
  foto VARCHAR(255) NULL,
  depoimento TEXT NOT NULL,
  tempo_conhece VARCHAR(100) NOT NULL,
  autorizacao BOOLEAN NOT NULL DEFAULT 0,
  status ENUM('pendente','aprovado','recusado') NOT NULL DEFAULT 'pendente',
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_depoimentos_status (status),
  INDEX idx_depoimentos_criado_em (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
