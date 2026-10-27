-- Mater — Migração 13: Mural da turma e roteiro de provas (proposta v2.0 §04)
-- - aviso_turma: autor, fixado (até 3 por turma, garantido por trigger) e confirmação de leitura
-- - avaliacoes: roteiro de estudo publicado pelo professor

ALTER TABLE aviso_turma ADD COLUMN IF NOT EXISTS fixado boolean NOT NULL DEFAULT false;
ALTER TABLE aviso_turma ADD COLUMN IF NOT EXISTS fk_id_professor integer REFERENCES professor (id_professor) ON UPDATE CASCADE ON DELETE SET NULL;

CREATE INDEX IF NOT EXISTS aviso_turma_disciplina_idx ON aviso_turma (fk_id_turma_disciplina, data_postagem DESC);

-- No máximo 3 avisos fixados por turma (somando todas as disciplinas)
CREATE OR REPLACE FUNCTION aviso_turma_limite_fixados() RETURNS trigger AS $$
DECLARE
  turma_id integer;
  total integer;
BEGIN
  IF NEW.fixado IS NOT TRUE OR (TG_OP = 'UPDATE' AND OLD.fixado IS TRUE) THEN
    RETURN NEW;
  END IF;

  SELECT fk_id_turma INTO turma_id FROM turma_disciplina WHERE id_turma_disciplina = NEW.fk_id_turma_disciplina;

  -- serializa por turma para não passar de 3 em pedidos simultâneos
  PERFORM pg_advisory_xact_lock(hashtext('aviso_fixado'), turma_id);

  SELECT COUNT(*) INTO total
  FROM aviso_turma a
  INNER JOIN turma_disciplina td ON (a.fk_id_turma_disciplina = td.id_turma_disciplina)
  WHERE td.fk_id_turma = turma_id AND a.fixado AND a.id_aviso_turma IS DISTINCT FROM NEW.id_aviso_turma;

  IF total >= 3 THEN
    RAISE EXCEPTION 'limite-fixados' USING ERRCODE = 'check_violation';
  END IF;

  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS aviso_turma_limite_fixados ON aviso_turma;
CREATE TRIGGER aviso_turma_limite_fixados BEFORE INSERT OR UPDATE ON aviso_turma
  FOR EACH ROW EXECUTE FUNCTION aviso_turma_limite_fixados();

CREATE TABLE IF NOT EXISTS aviso_leitura (
  fk_id_aviso_turma integer NOT NULL REFERENCES aviso_turma (id_aviso_turma) ON UPDATE CASCADE ON DELETE CASCADE,
  fk_id_matricula integer NOT NULL REFERENCES matricula (id_matricula) ON UPDATE CASCADE ON DELETE CASCADE,
  lido_em timestamp(0) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (fk_id_aviso_turma, fk_id_matricula)
);

ALTER TABLE avaliacoes ADD COLUMN IF NOT EXISTS roteiro text;

-- Uma nota por aluno por avaliação (permite gravar em lote com ON CONFLICT)
CREATE UNIQUE INDEX IF NOT EXISTS nota_avaliacao_matricula_avaliacao_uidx ON nota_avaliacao (fk_id_matricula_aluno, fk_id_avaliacao);

DROP TRIGGER IF EXISTS auditoria_aviso_turma ON aviso_turma;
CREATE TRIGGER auditoria_aviso_turma AFTER INSERT OR UPDATE OR DELETE ON aviso_turma
  FOR EACH ROW EXECUTE FUNCTION auditoria_registrar('id_aviso_turma');

DROP TRIGGER IF EXISTS auditoria_avaliacoes ON avaliacoes;
CREATE TRIGGER auditoria_avaliacoes AFTER INSERT OR UPDATE OR DELETE ON avaliacoes
  FOR EACH ROW EXECUTE FUNCTION auditoria_registrar('id_avaliacao');
