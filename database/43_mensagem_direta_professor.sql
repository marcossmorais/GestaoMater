-- Mater — Migração 43: mensagem direta da coordenação para um professor.
-- aviso_geral.fk_id_professor_destino: com valor, o aviso dos professores vai só para aquele professor
-- (NULL = todos os professores ativos, como antes). Só vale para publico = 'professores'.
-- Mensagem direta não é fixada: o limite de 3 fixados continua sendo do mural de todos os professores.

ALTER TABLE aviso_geral ADD COLUMN IF NOT EXISTS fk_id_professor_destino integer
  REFERENCES professor (id_professor) ON UPDATE CASCADE ON DELETE CASCADE;

CREATE INDEX IF NOT EXISTS aviso_geral_professor_destino_idx ON aviso_geral (fk_id_professor_destino)
  WHERE fk_id_professor_destino IS NOT NULL;

DO $$
BEGIN
  IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'aviso_geral_direto_professores') THEN
    ALTER TABLE aviso_geral ADD CONSTRAINT aviso_geral_direto_professores
      CHECK (fk_id_professor_destino IS NULL OR publico = 'professores');
  END IF;

  IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'aviso_geral_direto_sem_fixar') THEN
    ALTER TABLE aviso_geral ADD CONSTRAINT aviso_geral_direto_sem_fixar
      CHECK (fk_id_professor_destino IS NULL OR NOT fixado);
  END IF;
END $$;
