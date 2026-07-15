-- RWDEV Sprint 1.7 - Central de Auditoria Administrativa
-- Incremental seguro para MySQL/MariaDB/phpMyAdmin.
-- Nao apaga dados, nao altera senhas, nao altera perfis e nao altera permissoes existentes.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `auditoria_admin` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` int(10) UNSIGNED DEFAULT NULL,
  `admin_nome_snapshot` varchar(120) DEFAULT NULL,
  `admin_email_snapshot` varchar(160) DEFAULT NULL,
  `admin_perfil_snapshot` varchar(40) DEFAULT NULL,
  `empresa_id` int(10) UNSIGNED DEFAULT NULL,
  `modulo` varchar(80) NOT NULL,
  `acao` varchar(80) NOT NULL,
  `entidade` varchar(120) NOT NULL,
  `registro_id` bigint(20) UNSIGNED DEFAULT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `dados_anteriores_json` longtext DEFAULT NULL,
  `dados_posteriores_json` longtext DEFAULT NULL,
  `campos_alterados_json` longtext DEFAULT NULL,
  `resultado` enum('sucesso','erro','negado') NOT NULL DEFAULT 'sucesso',
  `mensagem_resultado` varchar(255) DEFAULT NULL,
  `rota` varchar(255) DEFAULT NULL,
  `metodo_http` varchar(10) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `request_id` varchar(80) DEFAULT NULL,
  `sessao_id_hash` char(64) DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_auditoria_criado_em` (`criado_em`),
  KEY `idx_auditoria_admin` (`admin_id`),
  KEY `idx_auditoria_modulo` (`modulo`),
  KEY `idx_auditoria_acao` (`acao`),
  KEY `idx_auditoria_resultado` (`resultado`),
  KEY `idx_auditoria_entidade_registro` (`entidade`, `registro_id`),
  CONSTRAINT `fk_auditoria_admin_admin`
    FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
