-- SONI PONTO - Sprint 1.5: status do dia e escala informativa.
-- Incremental seguro para MySQL/MariaDB/phpMyAdmin.
-- Antes de executar em produção: faça backup completo do banco.

ALTER TABLE `pontos_trabalho`
  ADD COLUMN IF NOT EXISTS `status_dia` varchar(40) NOT NULL DEFAULT 'trabalhado' AFTER `dia_semana`;

UPDATE `pontos_trabalho`
SET `status_dia` = 'trabalhado'
WHERE `status_dia` IS NULL OR `status_dia` = '';

ALTER TABLE `pontos_trabalho`
  MODIFY COLUMN `loja_id` int(10) UNSIGNED DEFAULT NULL;

SET @idx_status_exists := (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'pontos_trabalho'
    AND INDEX_NAME = 'idx_pontos_status_data'
);
SET @sql_idx_status := IF(@idx_status_exists = 0,
  'ALTER TABLE `pontos_trabalho` ADD INDEX `idx_pontos_status_data` (`status_dia`, `data`)',
  'SELECT 1'
);
PREPARE stmt_idx_status FROM @sql_idx_status;
EXECUTE stmt_idx_status;
DEALLOCATE PREPARE stmt_idx_status;
