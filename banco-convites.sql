CREATE TABLE IF NOT EXISTS convites_cliente (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  token CHAR(64) NOT NULL UNIQUE,
  nome VARCHAR(120) NOT NULL,
  empresa VARCHAR(160) NULL,
  email VARCHAR(160) NULL,
  telefone VARCHAR(30) NULL,
  status ENUM('pendente','usado','expirado') NOT NULL DEFAULT 'pendente',
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expira_em DATETIME NOT NULL,
  usado_em DATETIME NULL,
  INDEX idx_convites_token (token),
  INDEX idx_convites_email (email),
  INDEX idx_convites_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE convites_cliente
  MODIFY email VARCHAR(160) NULL;

ALTER TABLE convites_cliente
  ADD COLUMN projeto_nome VARCHAR(160) NULL AFTER telefone,
  ADD COLUMN projeto_dominio VARCHAR(180) NULL AFTER projeto_nome,
  ADD COLUMN projeto_descricao TEXT NULL AFTER projeto_dominio,
  ADD COLUMN paginas_json TEXT NULL AFTER projeto_descricao;
