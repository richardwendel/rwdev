CREATE TABLE IF NOT EXISTS `documentos_trabalho_categorias` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome` varchar(120) NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_documentos_trabalho_categorias_nome` (`nome`),
  KEY `idx_documentos_trabalho_categorias_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `documentos_trabalho` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `titulo` varchar(180) NOT NULL,
  `categoria` varchar(120) NOT NULL,
  `empresa` varchar(160) DEFAULT NULL,
  `cargo` varchar(120) DEFAULT NULL,
  `data_documento` date DEFAULT NULL,
  `data_validade` date DEFAULT NULL,
  `arquivo` varchar(255) NOT NULL,
  `observacoes` text DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `ponto_id` int(10) UNSIGNED DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_documentos_trabalho_categoria` (`categoria`),
  KEY `idx_documentos_trabalho_empresa` (`empresa`),
  KEY `idx_documentos_trabalho_data` (`data_documento`),
  KEY `idx_documentos_trabalho_ponto` (`ponto_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `documentos_trabalho_categorias` (`nome`, `ativo`) VALUES
('Contrato de Trabalho', 1),
('LGPD', 1),
('Banco de Horas', 1),
('Prorrogação de Horas', 1),
('Vale Transporte', 1),
('Holerite', 1),
('Espelho de Ponto', 1),
('Férias', 1),
('Exame Médico', 1),
('Outros', 1)
ON DUPLICATE KEY UPDATE
  `ativo` = VALUES(`ativo`);
