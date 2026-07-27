-- SONI PONTO - transportes configuráveis, trechos e reembolsos.
-- Incremental para MariaDB 11.8.3. Não altera pontos ou trajetos existentes.

ALTER TABLE `trajetos_trabalho`
  ADD COLUMN IF NOT EXISTS `padrao_loja` tinyint(1) NOT NULL DEFAULT 0 AFTER `tempo_medio`,
  ADD INDEX IF NOT EXISTS `idx_trajetos_loja_padrao` (`loja_id`, `padrao_loja`, `ativo`);

CREATE TABLE IF NOT EXISTS `trajeto_trechos_trabalho` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `trajeto_id` int unsigned NOT NULL,
  `direcao` varchar(10) NOT NULL,
  `ordem_trecho` smallint unsigned NOT NULL,
  `tipo_transporte` varchar(80) NOT NULL,
  `descricao` varchar(180) DEFAULT NULL,
  `tarifa_unitaria` decimal(10,2) NOT NULL,
  `quantidade` decimal(8,2) NOT NULL DEFAULT 1.00,
  `subtotal` decimal(10,2) NOT NULL,
  `vigencia_inicio` date NOT NULL,
  `vigencia_fim` date DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_trajeto_trecho_ordem_vigencia` (`trajeto_id`,`direcao`,`ordem_trecho`,`vigencia_inicio`),
  KEY `idx_trajeto_trecho_vigencia` (`trajeto_id`,`direcao`,`ativo`,`vigencia_inicio`,`vigencia_fim`),
  CONSTRAINT `fk_trajeto_trecho_trajeto` FOREIGN KEY (`trajeto_id`) REFERENCES `trajetos_trabalho` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ponto_reembolsos_transporte` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ponto_id` int unsigned NOT NULL,
  `loja_id` int unsigned NOT NULL,
  `trajeto_id` int unsigned DEFAULT NULL,
  `valor_previsto` decimal(10,2) NOT NULL DEFAULT 0.00,
  `valor_recebido` decimal(10,2) NOT NULL DEFAULT 0.00,
  `valor_gasto` decimal(10,2) NOT NULL DEFAULT 0.00,
  `diferenca_calculada` decimal(10,2) NOT NULL DEFAULT 0.00,
  `valor_solicitado` decimal(10,2) NOT NULL DEFAULT 0.00,
  `valor_aprovado` decimal(10,2) NOT NULL DEFAULT 0.00,
  `valor_reembolsado` decimal(10,2) NOT NULL DEFAULT 0.00,
  `situacao` varchar(25) NOT NULL DEFAULT 'calculado',
  `data_solicitacao` datetime DEFAULT NULL,
  `data_aprovacao` datetime DEFAULT NULL,
  `data_pagamento` datetime DEFAULT NULL,
  `forma_pagamento` varchar(80) DEFAULT NULL,
  `observacao` text DEFAULT NULL,
  `justificativa_alteracao` text DEFAULT NULL,
  `comprovante` varchar(255) DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ponto_reembolso_ponto` (`ponto_id`),
  KEY `idx_ponto_reembolso_filtros` (`situacao`,`loja_id`,`criado_em`),
  CONSTRAINT `fk_ponto_reembolso_ponto` FOREIGN KEY (`ponto_id`) REFERENCES `pontos_trabalho` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_ponto_reembolso_loja` FOREIGN KEY (`loja_id`) REFERENCES `lojas_trabalho` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_ponto_reembolso_trajeto` FOREIGN KEY (`trajeto_id`) REFERENCES `trajetos_trabalho` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `ponto_rhid_conferencias`
  ADD COLUMN IF NOT EXISTS `loja_rhid_id` int unsigned DEFAULT NULL AFTER `ponto_id`,
  ADD COLUMN IF NOT EXISTS `relogio_rhid` varchar(120) DEFAULT NULL AFTER `loja_rhid_id`,
  ADD COLUMN IF NOT EXISTS `situacao_loja` varchar(20) NOT NULL DEFAULT 'nao_informado' AFTER `relogio_rhid`,
  ADD COLUMN IF NOT EXISTS `observacao_loja` text DEFAULT NULL AFTER `situacao_loja`,
  ADD COLUMN IF NOT EXISTS `evidencia_loja` varchar(255) DEFAULT NULL AFTER `observacao_loja`,
  ADD INDEX IF NOT EXISTS `idx_ponto_rhid_loja` (`loja_rhid_id`);

SET @fk_rhid_loja_existe := (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'ponto_rhid_conferencias'
    AND CONSTRAINT_NAME = 'fk_ponto_rhid_loja'
);
SET @sql_fk_rhid_loja := IF(
  @fk_rhid_loja_existe = 0,
  'ALTER TABLE `ponto_rhid_conferencias` ADD CONSTRAINT `fk_ponto_rhid_loja` FOREIGN KEY (`loja_rhid_id`) REFERENCES `lojas_trabalho` (`id`) ON DELETE RESTRICT',
  'SELECT ''fk_ponto_rhid_loja já existe'' AS resultado'
);
PREPARE stmt_fk_rhid_loja FROM @sql_fk_rhid_loja;
EXECUTE stmt_fk_rhid_loja;
DEALLOCATE PREPARE stmt_fk_rhid_loja;
