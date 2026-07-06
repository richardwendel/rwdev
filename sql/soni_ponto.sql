CREATE TABLE IF NOT EXISTS `lojas_trabalho` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo_loja` varchar(20) NOT NULL,
  `nome` varchar(120) NOT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `cidade` varchar(120) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lojas_trabalho_codigo` (`codigo_loja`),
  KEY `idx_lojas_trabalho_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `trajetos_trabalho` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `loja_id` int(10) UNSIGNED NOT NULL,
  `nome_trajeto` varchar(140) NOT NULL,
  `tipo_transporte` varchar(80) DEFAULT NULL,
  `valor_ida` decimal(10,2) NOT NULL DEFAULT 0.00,
  `valor_volta` decimal(10,2) NOT NULL DEFAULT 0.00,
  `valor_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tempo_medio` varchar(40) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_trajetos_trabalho_loja` (`loja_id`),
  KEY `idx_trajetos_trabalho_ativo` (`ativo`),
  CONSTRAINT `fk_trajetos_trabalho_loja` FOREIGN KEY (`loja_id`) REFERENCES `lojas_trabalho` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pontos_trabalho` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `data` date NOT NULL,
  `dia_semana` varchar(30) NOT NULL,
  `loja_id` int(10) UNSIGNED NOT NULL,
  `entrada` time DEFAULT NULL,
  `cafe_saida` time DEFAULT NULL,
  `cafe_retorno` time DEFAULT NULL,
  `almoco_saida` time DEFAULT NULL,
  `almoco_retorno` time DEFAULT NULL,
  `saida` time DEFAULT NULL,
  `transporte_observacao` text DEFAULT NULL,
  `gasto_transporte` decimal(10,2) NOT NULL DEFAULT 0.00,
  `bilhetes_perdidos` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `valor_bilhetes_perdidos` decimal(10,2) NOT NULL DEFAULT 0.00,
  `observacoes` text DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pontos_trabalho_data` (`data`),
  KEY `idx_pontos_trabalho_loja_data` (`loja_id`, `data`),
  CONSTRAINT `fk_pontos_trabalho_loja` FOREIGN KEY (`loja_id`) REFERENCES `lojas_trabalho` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `lojas_trabalho` (`codigo_loja`, `nome`, `cidade`, `ativo`) VALUES
('04', 'Loja 04', '', 1),
('06', 'Loja 06', '', 1),
('07', 'Loja 07', '', 1)
ON DUPLICATE KEY UPDATE
  `nome` = VALUES(`nome`),
  `ativo` = VALUES(`ativo`);

INSERT INTO `pontos_trabalho`
(`data`, `dia_semana`, `loja_id`, `entrada`, `cafe_saida`, `cafe_retorno`, `almoco_saida`, `almoco_retorno`, `saida`)
SELECT '2026-07-05', 'Domingo', l.id, '06:41:00', '08:21:00', '08:30:00', '13:19:00', '14:18:00', '15:41:57'
FROM `lojas_trabalho` l WHERE l.codigo_loja = '04'
AND NOT EXISTS (SELECT 1 FROM `pontos_trabalho` p WHERE p.data = '2026-07-05' AND p.loja_id = l.id);

INSERT INTO `pontos_trabalho`
(`data`, `dia_semana`, `loja_id`, `entrada`, `cafe_saida`, `cafe_retorno`, `almoco_saida`, `almoco_retorno`, `saida`)
SELECT '2026-07-04', 'Sábado', l.id, '05:52:00', '07:41:00', '07:55:00', '11:48:00', '12:48:00', '14:54:00'
FROM `lojas_trabalho` l WHERE l.codigo_loja = '06'
AND NOT EXISTS (SELECT 1 FROM `pontos_trabalho` p WHERE p.data = '2026-07-04' AND p.loja_id = l.id);

INSERT INTO `pontos_trabalho`
(`data`, `dia_semana`, `loja_id`, `entrada`, `cafe_saida`, `cafe_retorno`, `almoco_saida`, `almoco_retorno`, `saida`)
SELECT '2026-07-03', 'Sexta-feira', l.id, '06:02:00', '08:34:00', '08:49:00', '11:43:00', '12:43:00', '14:34:00'
FROM `lojas_trabalho` l WHERE l.codigo_loja = '06'
AND NOT EXISTS (SELECT 1 FROM `pontos_trabalho` p WHERE p.data = '2026-07-03' AND p.loja_id = l.id);

INSERT INTO `pontos_trabalho`
(`data`, `dia_semana`, `loja_id`, `entrada`, `cafe_saida`, `cafe_retorno`, `almoco_saida`, `almoco_retorno`, `saida`)
SELECT '2026-07-02', 'Quinta-feira', l.id, '05:51:00', '07:54:00', '08:10:00', '12:34:00', '13:30:00', '14:50:00'
FROM `lojas_trabalho` l WHERE l.codigo_loja = '06'
AND NOT EXISTS (SELECT 1 FROM `pontos_trabalho` p WHERE p.data = '2026-07-02' AND p.loja_id = l.id);
