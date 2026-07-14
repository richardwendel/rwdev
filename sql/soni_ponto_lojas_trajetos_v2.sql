-- SONI PONTO - Sprint 1: lojas oficiais e trajetos por loja
-- Incremental seguro para MySQL/MariaDB/phpMyAdmin.
-- Antes de executar em produção: faça backup completo do banco.

ALTER TABLE `lojas_trabalho`
  ADD COLUMN IF NOT EXISTS `numero_interno` varchar(20) DEFAULT NULL AFTER `codigo_loja`,
  ADD COLUMN IF NOT EXISTS `responsavel` varchar(120) DEFAULT NULL AFTER `cidade`,
  ADD COLUMN IF NOT EXISTS `telefone` varchar(40) DEFAULT NULL AFTER `responsavel`,
  ADD COLUMN IF NOT EXISTS `horario_padrao` varchar(120) DEFAULT NULL AFTER `telefone`,
  ADD COLUMN IF NOT EXISTS `cor_identificacao` varchar(20) DEFAULT NULL AFTER `horario_padrao`;

ALTER TABLE `pontos_trabalho`
  ADD COLUMN IF NOT EXISTS `trajeto_ida_id` int(10) UNSIGNED DEFAULT NULL AFTER `loja_id`,
  ADD COLUMN IF NOT EXISTS `trajeto_volta_id` int(10) UNSIGNED DEFAULT NULL AFTER `trajeto_ida_id`;

SET @idx_ida_exists := (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'pontos_trabalho'
    AND INDEX_NAME = 'idx_pontos_trajeto_ida'
);
SET @sql_idx_ida := IF(@idx_ida_exists = 0,
  'ALTER TABLE `pontos_trabalho` ADD INDEX `idx_pontos_trajeto_ida` (`trajeto_ida_id`)',
  'SELECT 1'
);
PREPARE stmt_idx_ida FROM @sql_idx_ida;
EXECUTE stmt_idx_ida;
DEALLOCATE PREPARE stmt_idx_ida;

SET @idx_volta_exists := (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'pontos_trabalho'
    AND INDEX_NAME = 'idx_pontos_trajeto_volta'
);
SET @sql_idx_volta := IF(@idx_volta_exists = 0,
  'ALTER TABLE `pontos_trabalho` ADD INDEX `idx_pontos_trajeto_volta` (`trajeto_volta_id`)',
  'SELECT 1'
);
PREPARE stmt_idx_volta FROM @sql_idx_volta;
EXECUTE stmt_idx_volta;
DEALLOCATE PREPARE stmt_idx_volta;

SET @fk_ida_exists := (
  SELECT COUNT(*)
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'pontos_trabalho'
    AND CONSTRAINT_NAME = 'fk_pontos_trajeto_ida'
);
SET @sql_fk_ida := IF(@fk_ida_exists = 0,
  'ALTER TABLE `pontos_trabalho` ADD CONSTRAINT `fk_pontos_trajeto_ida` FOREIGN KEY (`trajeto_ida_id`) REFERENCES `trajetos_trabalho` (`id`) ON DELETE SET NULL',
  'SELECT 1'
);
PREPARE stmt_fk_ida FROM @sql_fk_ida;
EXECUTE stmt_fk_ida;
DEALLOCATE PREPARE stmt_fk_ida;

SET @fk_volta_exists := (
  SELECT COUNT(*)
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'pontos_trabalho'
    AND CONSTRAINT_NAME = 'fk_pontos_trajeto_volta'
);
SET @sql_fk_volta := IF(@fk_volta_exists = 0,
  'ALTER TABLE `pontos_trabalho` ADD CONSTRAINT `fk_pontos_trajeto_volta` FOREIGN KEY (`trajeto_volta_id`) REFERENCES `trajetos_trabalho` (`id`) ON DELETE SET NULL',
  'SELECT 1'
);
PREPARE stmt_fk_volta FROM @sql_fk_volta;
EXECUTE stmt_fk_volta;
DEALLOCATE PREPARE stmt_fk_volta;

UPDATE `lojas_trabalho`
SET `codigo_loja` = 'LJ04', `numero_interno` = COALESCE(NULLIF(`numero_interno`, ''), '04')
WHERE `codigo_loja` = '04'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `id` FROM `lojas_trabalho` WHERE `codigo_loja` = 'LJ04') AS lj04);

UPDATE `lojas_trabalho`
SET `codigo_loja` = 'LJ06', `numero_interno` = COALESCE(NULLIF(`numero_interno`, ''), '06')
WHERE `codigo_loja` = '06'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `id` FROM `lojas_trabalho` WHERE `codigo_loja` = 'LJ06') AS lj06);

UPDATE `lojas_trabalho`
SET `codigo_loja` = 'LJ07', `numero_interno` = COALESCE(NULLIF(`numero_interno`, ''), '07')
WHERE `codigo_loja` = '07'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `id` FROM `lojas_trabalho` WHERE `codigo_loja` = 'LJ07') AS lj07);

INSERT INTO `lojas_trabalho`
(`codigo_loja`, `numero_interno`, `nome`, `endereco`, `cidade`, `observacoes`, `ativo`, `cor_identificacao`)
VALUES
('LJ01', '01', 'Jundiapeba', 'Av. Presidente Altino Arantes, 1434 - Jundiapeba - CEP 08750-500', 'Mogi das Cruzes', 'Estado: SP.', 1, '#2563eb'),
('LJ02', '02', 'César de Souza', 'Av. Ricieri José Marcatto, 1505 - Vila Suissa - CEP 08810-020', 'Mogi das Cruzes', 'Estado: SP.', 1, '#16a34a'),
('LJ03', '03', 'Santa Casa', 'Av. Antônio Marques Figueira, 1820 - Vila Figueira - CEP 08676-165', 'Suzano', 'Estado: SP. Referência: em frente à Santa Casa.', 1, '#dc2626'),
('LJ04', '04', 'Casa Branca', 'Estrada dos Fernandes, 2460 - Parque Santa Rosa', 'Suzano', 'Estado: SP.', 1, '#f59e0b'),
('LJ05', '05', 'Jardim Europa', 'Estrada Takashi Kobata, 691 - Jardim Europa - CEP 08696-040', 'Suzano', 'Estado: SP.', 1, '#0f766e'),
('LJ06', '06', 'Jardim das Oliveiras', 'Praça Damasco de Coelho Pinho, 20 - Parque Santa Amélia', 'São Paulo', 'Estado: SP.', 1, '#7c3aed'),
('LJ07', '07', 'Poá', 'Av. Nove de Julho, 354 - Centro - CEP 08550-100', 'Poá', 'Estado: SP.', 1, '#0891b2')
ON DUPLICATE KEY UPDATE
  `numero_interno` = VALUES(`numero_interno`),
  `nome` = VALUES(`nome`),
  `endereco` = VALUES(`endereco`),
  `cidade` = VALUES(`cidade`),
  `observacoes` = VALUES(`observacoes`),
  `ativo` = VALUES(`ativo`),
  `cor_identificacao` = VALUES(`cor_identificacao`);

INSERT INTO `trajetos_trabalho` (`loja_id`, `nome_trajeto`, `observacoes`, `ativo`)
SELECT l.id, 'Trajeto Econômico', 'Opção de caminho cadastrada na Sprint 1.', 1
FROM `lojas_trabalho` l
WHERE l.codigo_loja = 'LJ04'
  AND NOT EXISTS (SELECT 1 FROM `trajetos_trabalho` t WHERE t.loja_id = l.id AND t.nome_trajeto = 'Trajeto Econômico');

INSERT INTO `trajetos_trabalho` (`loja_id`, `nome_trajeto`, `observacoes`, `ativo`)
SELECT l.id, 'Trajeto Rápido', 'Opção de caminho cadastrada na Sprint 1.', 1
FROM `lojas_trabalho` l
WHERE l.codigo_loja = 'LJ04'
  AND NOT EXISTS (SELECT 1 FROM `trajetos_trabalho` t WHERE t.loja_id = l.id AND t.nome_trajeto = 'Trajeto Rápido');

INSERT INTO `trajetos_trabalho` (`loja_id`, `nome_trajeto`, `observacoes`, `ativo`)
SELECT l.id, 'Via Aracaré', 'Opção de caminho cadastrada na Sprint 1.', 1
FROM `lojas_trabalho` l
WHERE l.codigo_loja = 'LJ06'
  AND NOT EXISTS (SELECT 1 FROM `trajetos_trabalho` t WHERE t.loja_id = l.id AND t.nome_trajeto = 'Via Aracaré');

INSERT INTO `trajetos_trabalho` (`loja_id`, `nome_trajeto`, `observacoes`, `ativo`)
SELECT l.id, 'Via Aracaré', 'Opção de caminho cadastrada na Sprint 1.', 1
FROM `lojas_trabalho` l
WHERE l.codigo_loja = 'LJ07'
  AND NOT EXISTS (SELECT 1 FROM `trajetos_trabalho` t WHERE t.loja_id = l.id AND t.nome_trajeto = 'Via Aracaré');
