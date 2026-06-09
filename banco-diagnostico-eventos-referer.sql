ALTER TABLE diagnostico_eventos
  ADD COLUMN referer VARCHAR(255) NULL AFTER page;

CREATE INDEX idx_diagnostico_referer
  ON diagnostico_eventos (referer);
