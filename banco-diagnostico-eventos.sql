CREATE TABLE IF NOT EXISTS diagnostico_eventos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  event_type VARCHAR(40) NOT NULL,
  page VARCHAR(120) NOT NULL,
  referer VARCHAR(255) NULL,
  ip_hash CHAR(64) NOT NULL,
  user_agent_hash CHAR(64) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_diagnostico_event_type_created_at (event_type, created_at),
  INDEX idx_diagnostico_referer (referer),
  INDEX idx_diagnostico_unicos_dia (event_type, page, ip_hash, user_agent_hash, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
