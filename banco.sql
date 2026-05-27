CREATE TABLE clientes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  senha VARCHAR(255) NOT NULL,
  empresa VARCHAR(160) NULL,
  telefone VARCHAR(30) NULL,
  status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admins (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  senha VARCHAR(255) NOT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE projetos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT UNSIGNED NOT NULL,
  nome VARCHAR(160) NOT NULL,
  dominio VARCHAR(180) NULL,
  descricao TEXT NULL,
  status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_projetos_clientes
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE paginas_projeto (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  projeto_id INT UNSIGNED NOT NULL,
  nome_pagina VARCHAR(120) NOT NULL,
  CONSTRAINT fk_paginas_projeto
    FOREIGN KEY (projeto_id) REFERENCES projetos(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE solicitacoes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT UNSIGNED NOT NULL,
  projeto_id INT UNSIGNED NOT NULL,
  pagina VARCHAR(120) NOT NULL,
  tipo_alteracao VARCHAR(80) NOT NULL,
  descricao TEXT NOT NULL,
  status ENUM('Recebido', 'Em análise', 'Em desenvolvimento', 'Aguardando cliente', 'Concluído') NOT NULL DEFAULT 'Recebido',
  resposta_admin TEXT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_solicitacoes_clientes
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_solicitacoes_projetos
    FOREIGN KEY (projeto_id) REFERENCES projetos(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE arquivos_solicitacao (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  solicitacao_id INT UNSIGNED NOT NULL,
  nome_original VARCHAR(255) NOT NULL,
  nome_arquivo VARCHAR(255) NOT NULL,
  caminho VARCHAR(255) NOT NULL,
  tipo VARCHAR(120) NOT NULL,
  tamanho INT UNSIGNED NOT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_arquivos_solicitacao
    FOREIGN KEY (solicitacao_id) REFERENCES solicitacoes(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS convites_cliente (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  token CHAR(64) NOT NULL UNIQUE,
  nome VARCHAR(120) NOT NULL,
  empresa VARCHAR(160) NULL,
  email VARCHAR(160) NULL,
  telefone VARCHAR(30) NULL,
  projeto_nome VARCHAR(160) NULL,
  projeto_dominio VARCHAR(180) NULL,
  projeto_descricao TEXT NULL,
  paginas_json TEXT NULL,
  status ENUM('pendente','usado','expirado') NOT NULL DEFAULT 'pendente',
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expira_em DATETIME NOT NULL,
  usado_em DATETIME NULL,
  INDEX idx_convites_token (token),
  INDEX idx_convites_email (email),
  INDEX idx_convites_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

-- Crie o primeiro admin trocando a senha depois de gerar um hash com password_hash.
-- Exemplo rapido em PHP: echo password_hash('sua-senha-forte', PASSWORD_DEFAULT);
-- INSERT INTO admins (nome, email, senha)
-- VALUES ('Richard', 'seu-email@dominio.com', '$2y$10$COLE_AQUI_UM_HASH_VALIDO');
