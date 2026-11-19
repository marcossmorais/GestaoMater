-- Mater — Migração 34: limite de tentativas (validação pública de documentos e login por código).
--
-- Uma linha por chave (ex.: "validar|<hash do IP>"): quando a janela começou e quantas tentativas houve nela.
-- O IP não é guardado: a chave usa o hash dele (LGPD). Linhas velhas são apagadas pelo próprio
-- App/Tools/RateLimit.php de vez em quando.

CREATE TABLE IF NOT EXISTS limite_tentativa (
  chave varchar(120) PRIMARY KEY,
  inicio timestamptz NOT NULL DEFAULT now(),
  total integer NOT NULL DEFAULT 0
);

CREATE INDEX IF NOT EXISTS limite_tentativa_inicio_idx ON limite_tentativa (inicio);
