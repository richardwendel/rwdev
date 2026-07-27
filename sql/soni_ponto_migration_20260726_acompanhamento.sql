-- SONI PONTO: migration incremental, sem alteração de registros existentes.
CREATE TABLE IF NOT EXISTS `ponto_configuracoes` (
 `id` int unsigned NOT NULL AUTO_INCREMENT, `vigencia_inicio` date NOT NULL, `vigencia_fim` date DEFAULT NULL,
 `minutos_jornada` smallint unsigned DEFAULT NULL COMMENT 'NULL = jornada não configurada',
 `hora_entrada_prevista` time DEFAULT NULL, `domingos_trabalho_seguidos` tinyint unsigned NOT NULL DEFAULT 2,
 `domingos_folga_seguidos` tinyint unsigned NOT NULL DEFAULT 1, `integracao_remunerada` tinyint(1) NOT NULL DEFAULT 1,
 `feriado_folgado_remunerado` tinyint(1) NOT NULL DEFAULT 1,
 `feriado_trabalhado_adicional_percentual` decimal(5,2) NOT NULL DEFAULT 100.00,
 `feriado_trabalhado_gera_folga` tinyint(1) NOT NULL DEFAULT 1, `observacoes` text DEFAULT NULL,
 `criado_em` datetime NOT NULL DEFAULT current_timestamp(), `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
 PRIMARY KEY (`id`), KEY `idx_ponto_config_vigencia` (`vigencia_inicio`,`vigencia_fim`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `ponto_rhid_conferencias` (
 `id` bigint unsigned NOT NULL AUTO_INCREMENT, `ponto_id` int unsigned NOT NULL,
 `entrada` time DEFAULT NULL, `cafe_saida` time DEFAULT NULL, `cafe_retorno` time DEFAULT NULL,
 `almoco_saida` time DEFAULT NULL, `almoco_retorno` time DEFAULT NULL, `saida` time DEFAULT NULL,
 `conferido_em` datetime DEFAULT NULL, `situacao` varchar(20) NOT NULL DEFAULT 'nao_conferido',
 `diferencas` text DEFAULT NULL, `observacao` text DEFAULT NULL, `responsavel` varchar(160) DEFAULT NULL,
 `criado_em` datetime NOT NULL DEFAULT current_timestamp(), `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
 PRIMARY KEY (`id`), UNIQUE KEY `uk_ponto_rhid_ponto` (`ponto_id`),
 CONSTRAINT `fk_ponto_rhid_ponto` FOREIGN KEY (`ponto_id`) REFERENCES `pontos_trabalho` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `ponto_direitos` (
 `id` bigint unsigned NOT NULL AUTO_INCREMENT, `ponto_origem_id` int unsigned DEFAULT NULL,
 `tipo` varchar(30) NOT NULL, `situacao` varchar(20) NOT NULL DEFAULT 'pendente',
 `quantidade` decimal(6,2) NOT NULL DEFAULT 1.00, `data_aquisicao` date NOT NULL,
 `data_prevista` date DEFAULT NULL, `data_utilizacao` date DEFAULT NULL, `observacao` text DEFAULT NULL,
 `justificativa_alteracao` text DEFAULT NULL, `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
 `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
 PRIMARY KEY (`id`), KEY `idx_ponto_direitos_tipo_situacao` (`tipo`,`situacao`),
 CONSTRAINT `fk_ponto_direito_origem` FOREIGN KEY (`ponto_origem_id`) REFERENCES `pontos_trabalho` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `ponto_ocorrencias` (
 `id` bigint unsigned NOT NULL AUTO_INCREMENT, `ponto_id` int unsigned DEFAULT NULL, `data` date NOT NULL,
 `tipo` varchar(40) NOT NULL, `descricao` text NOT NULL, `situacao` varchar(20) NOT NULL DEFAULT 'aberta',
 `resolucao` text DEFAULT NULL, `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
 `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
 PRIMARY KEY (`id`), KEY `idx_ponto_ocorrencias_data` (`data`),
 CONSTRAINT `fk_ponto_ocorrencia_ponto` FOREIGN KEY (`ponto_id`) REFERENCES `pontos_trabalho` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `ponto_competencias` (
 `id` int unsigned NOT NULL AUTO_INCREMENT, `ano` smallint unsigned NOT NULL, `mes` tinyint unsigned NOT NULL,
 `situacao` varchar(15) NOT NULL DEFAULT 'aberta', `fechado_em` datetime DEFAULT NULL, `fechado_por` varchar(160) DEFAULT NULL,
 `reaberto_em` datetime DEFAULT NULL, `reaberto_por` varchar(160) DEFAULT NULL, `justificativa_reabertura` text DEFAULT NULL,
 `criado_em` datetime NOT NULL DEFAULT current_timestamp(), `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
 PRIMARY KEY (`id`), UNIQUE KEY `uk_ponto_competencia` (`ano`,`mes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `ponto_historico` (
 `id` bigint unsigned NOT NULL AUTO_INCREMENT, `entidade` varchar(50) NOT NULL, `entidade_id` bigint unsigned DEFAULT NULL,
 `acao` varchar(40) NOT NULL, `valor_anterior` json DEFAULT NULL, `valor_novo` json DEFAULT NULL,
 `justificativa` text DEFAULT NULL, `usuario` varchar(160) DEFAULT NULL, `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
 PRIMARY KEY (`id`), KEY `idx_ponto_historico_entidade` (`entidade`,`entidade_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE `pontos_trabalho`
 ADD COLUMN IF NOT EXISTS `transporte_previsto` decimal(10,2) NOT NULL DEFAULT 0.00 AFTER `transporte_observacao`,
 ADD COLUMN IF NOT EXISTS `transporte_recebido` decimal(10,2) NOT NULL DEFAULT 0.00 AFTER `transporte_previsto`;
-- Rollback, apenas antes de haver dados novos: remover as duas colunas e as seis tabelas acima em ordem inversa.
