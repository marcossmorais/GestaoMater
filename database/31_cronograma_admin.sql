-- Mater — Migração 31: cronograma escolar editável pelo admin (#21)
-- atualizado_em: muda a cada edição, para o admin saber se o evento mudou depois do último aviso.
-- notificado_em: quando o evento foi publicado no mural pela última vez ("Notificar no mural").

ALTER TABLE evento_escolar ADD COLUMN IF NOT EXISTS atualizado_em timestamp(0) NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE evento_escolar ADD COLUMN IF NOT EXISTS notificado_em timestamp(0);

CREATE INDEX IF NOT EXISTS evento_escolar_fim_idx ON evento_escolar ((COALESCE(data_fim, data_inicio)));
