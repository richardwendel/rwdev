-- RWDEV Sprint 1.6 - Administradores, permissoes e convites administrativos
-- Execute antes de publicar o codigo novo.
-- Seguro para phpMyAdmin/Hostinger: nao apaga usuarios, nao reseta senhas e nao promove por ID presumido.

SET NAMES utf8mb4;

-- PREENCHA antes de executar.
-- Use o e-mail real e confirmado do superadministrador principal da RWDEV.
-- Exemplo:
-- SET @rwdev_superadmin_email := 'ricardo@dominio-confirmado.com.br';
SET @rwdev_superadmin_email := 'rwdevtech@gmail.com';

-- Estrutura atual confirmada no dump do projeto:
-- admins possui: id, nome, email, senha, criado_em.
-- Nao usamos DEFAULT 'superadministrador' para evitar promocao indevida.
ALTER TABLE `admins`
  ADD COLUMN IF NOT EXISTS `perfil` enum('superadministrador','administrador_modulo','visualizador') DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `ativo` tinyint(1) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `ultimo_acesso` datetime DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `atualizado_em` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  ADD COLUMN IF NOT EXISTS `criado_por` int(10) UNSIGNED DEFAULT NULL;

-- Promove somente o administrador explicitamente confirmado pelo e-mail preenchido acima.
-- Se o placeholder nao for alterado, nenhum usuario sera promovido.
UPDATE `admins`
SET `perfil` = 'superadministrador',
    `ativo` = 1
WHERE `email` = @rwdev_superadmin_email
  AND @rwdev_superadmin_email <> 'rwdevtech@gmail.com';

SELECT
  CASE
    WHEN @rwdev_superadmin_email = 'PREENCHA_O_EMAIL_CONFIRMADO_ANTES_DE_EXECUTAR'
      THEN 'ATENCAO: preencha @rwdev_superadmin_email antes de executar em producao.'
    ELSE CONCAT('Superadministrador confirmado por e-mail: ', @rwdev_superadmin_email)
  END AS `rwdev_admin_migration_aviso`;

SET @idx_admins_perfil_exists := (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'admins'
    AND INDEX_NAME = 'idx_admins_perfil'
);
SET @sql_idx_admins_perfil := IF(@idx_admins_perfil_exists = 0,
  'ALTER TABLE `admins` ADD INDEX `idx_admins_perfil` (`perfil`)',
  'SELECT 1'
);
PREPARE stmt_idx_admins_perfil FROM @sql_idx_admins_perfil;
EXECUTE stmt_idx_admins_perfil;
DEALLOCATE PREPARE stmt_idx_admins_perfil;

SET @idx_admins_ativo_exists := (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'admins'
    AND INDEX_NAME = 'idx_admins_ativo'
);
SET @sql_idx_admins_ativo := IF(@idx_admins_ativo_exists = 0,
  'ALTER TABLE `admins` ADD INDEX `idx_admins_ativo` (`ativo`)',
  'SELECT 1'
);
PREPARE stmt_idx_admins_ativo FROM @sql_idx_admins_ativo;
EXECUTE stmt_idx_admins_ativo;
DEALLOCATE PREPARE stmt_idx_admins_ativo;

SET @idx_admins_criado_por_exists := (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'admins'
    AND INDEX_NAME = 'idx_admins_criado_por'
);
SET @sql_idx_admins_criado_por := IF(@idx_admins_criado_por_exists = 0,
  'ALTER TABLE `admins` ADD INDEX `idx_admins_criado_por` (`criado_por`)',
  'SELECT 1'
);
PREPARE stmt_idx_admins_criado_por FROM @sql_idx_admins_criado_por;
EXECUTE stmt_idx_admins_criado_por;
DEALLOCATE PREPARE stmt_idx_admins_criado_por;

-- FK opcional e segura para admins.criado_por.
-- Como a coluna e nova e nullable, os dados atuais sao compativeis.
-- Se a migration estiver sendo reexecutada sobre uma base alterada com criado_por invalido, a FK nao sera criada.
SET @fk_admins_criado_por_exists := (
  SELECT COUNT(*)
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'admins'
    AND CONSTRAINT_NAME = 'fk_admins_criado_por'
);
SET @admins_criado_por_invalidos := (
  SELECT COUNT(*)
  FROM `admins` a
  LEFT JOIN `admins` criador ON criador.`id` = a.`criado_por`
  WHERE a.`criado_por` IS NOT NULL
    AND criador.`id` IS NULL
);
SET @sql_fk_admins_criado_por := IF(@fk_admins_criado_por_exists = 0 AND @admins_criado_por_invalidos = 0,
  'ALTER TABLE `admins` ADD CONSTRAINT `fk_admins_criado_por` FOREIGN KEY (`criado_por`) REFERENCES `admins` (`id`) ON DELETE SET NULL',
  'SELECT ''FK fk_admins_criado_por ja existe ou ha criado_por invalido para revisar.'''
);
PREPARE stmt_fk_admins_criado_por FROM @sql_fk_admins_criado_por;
EXECUTE stmt_fk_admins_criado_por;
DEALLOCATE PREPARE stmt_fk_admins_criado_por;

CREATE TABLE IF NOT EXISTS `admin_permissoes` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` int(10) UNSIGNED NOT NULL,
  `permissao` varchar(80) NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_admin_permissao` (`admin_id`, `permissao`),
  KEY `idx_admin_permissoes_admin` (`admin_id`),
  KEY `idx_admin_permissoes_permissao` (`permissao`),
  CONSTRAINT `fk_admin_permissoes_admin`
    FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `convites_admin` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` int(10) UNSIGNED NOT NULL,
  `token_hash` char(64) NOT NULL,
  `nome` varchar(120) NOT NULL,
  `email` varchar(160) NOT NULL,
  `perfil` enum('administrador_modulo','visualizador') NOT NULL DEFAULT 'administrador_modulo',
  `permissoes_json` longtext DEFAULT NULL,
  `status` enum('pendente','usado','expirado','revogado') NOT NULL DEFAULT 'pendente',
  `criado_por` int(10) UNSIGNED DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `expira_em` datetime NOT NULL,
  `usado_em` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_convites_admin_token_hash` (`token_hash`),
  KEY `idx_convites_admin_email` (`email`),
  KEY `idx_convites_admin_status` (`status`),
  KEY `idx_convites_admin_expira` (`expira_em`),
  KEY `idx_convites_admin_admin` (`admin_id`),
  KEY `idx_convites_admin_criado_por` (`criado_por`),
  CONSTRAINT `fk_convites_admin_admin`
    FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_convites_admin_criado_por`
    FOREIGN KEY (`criado_por`) REFERENCES `admins` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ENUM atual preservado a partir do schema do projeto:
-- login_sucesso, login_falha, login_bloqueado, logout, sessao_expirada,
-- upload_sucesso, upload_bloqueado, csrf_invalido, form_recebido, csrf_ok,
-- dados_recebidos, insert_executado, insert_erro.
-- A alteracao abaixo acrescenta somente os eventos da Sprint 1.6.
SET @logs_tipo_evento_exists := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'logs_seguranca'
    AND COLUMN_NAME = 'tipo_evento'
);
SET @sql_logs_tipo_evento := IF(@logs_tipo_evento_exists = 1,
  'ALTER TABLE `logs_seguranca`
     MODIFY `tipo_evento` enum(
       ''login_sucesso'',''login_falha'',''login_bloqueado'',''logout'',''sessao_expirada'',
       ''upload_sucesso'',''upload_bloqueado'',''csrf_invalido'',''form_recebido'',''csrf_ok'',
       ''dados_recebidos'',''insert_executado'',''insert_erro'',
       ''admin_convite_criado'',''admin_convite_revogado'',''admin_conta_ativada'',
       ''admin_criado'',''admin_perfil_alterado'',''admin_permissoes_alteradas'',
       ''admin_status_alterado'',''acesso_negado''
     ) NOT NULL',
  'SELECT ''Tabela logs_seguranca.tipo_evento nao encontrada; revise antes do deploy.'''
);
PREPARE stmt_logs_tipo_evento FROM @sql_logs_tipo_evento;
EXECUTE stmt_logs_tipo_evento;
DEALLOCATE PREPARE stmt_logs_tipo_evento;
