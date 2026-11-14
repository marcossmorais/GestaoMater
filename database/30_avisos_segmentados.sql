-- Mater — Migração 30: avisos gerais por público (#22)
-- publico: quem recebe o aviso do admin.
--   alunos       → portal do aluno, de toda a escola ou das turmas escolhidas
--   responsaveis → portal do aluno também (a família entra com o login do aluno), com selo "Para a família"
--   professores  → portal docente, todos os professores ativos
-- aviso_geral_turma: turmas de destino (nenhuma = toda a escola). Substitui aviso_geral.fk_id_turma.
-- aviso_geral_leitura_professor: confirmação de leitura do professor.
-- Até 3 fixados por público: professores somam no máximo 3; cada turma vê no máximo 3 fixados (alunos + responsáveis).

ALTER TABLE aviso_geral ADD COLUMN IF NOT EXISTS publico varchar(12) NOT NULL DEFAULT 'alunos'
  CHECK (publico IN ('alunos', 'responsaveis', 'professores'));

CREATE TABLE IF NOT EXISTS aviso_geral_turma (
  fk_id_aviso_geral integer NOT NULL REFERENCES aviso_geral (id_aviso_geral) ON UPDATE CASCADE ON DELETE CASCADE,
  fk_id_turma integer NOT NULL REFERENCES turma (id_turma) ON UPDATE CASCADE ON DELETE CASCADE,
  PRIMARY KEY (fk_id_aviso_geral, fk_id_turma)
);

CREATE INDEX IF NOT EXISTS aviso_geral_turma_turma_idx ON aviso_geral_turma (fk_id_turma);

DO $$
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'aviso_geral' AND column_name = 'fk_id_turma') THEN
    INSERT INTO aviso_geral_turma (fk_id_aviso_geral, fk_id_turma)
    SELECT id_aviso_geral, fk_id_turma FROM aviso_geral WHERE fk_id_turma IS NOT NULL
    ON CONFLICT DO NOTHING;

    ALTER TABLE aviso_geral DROP COLUMN fk_id_turma;
  END IF;
END $$;

CREATE INDEX IF NOT EXISTS aviso_geral_publico_idx ON aviso_geral (publico, data_postagem DESC);

CREATE TABLE IF NOT EXISTS aviso_geral_leitura_professor (
  fk_id_aviso_geral integer NOT NULL REFERENCES aviso_geral (id_aviso_geral) ON UPDATE CASCADE ON DELETE CASCADE,
  fk_id_professor integer NOT NULL REFERENCES professor (id_professor) ON UPDATE CASCADE ON DELETE CASCADE,
  lido_em timestamp(0) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (fk_id_aviso_geral, fk_id_professor)
);

-- Confere as regras no fim da transação, depois que as turmas do aviso já foram gravadas.
CREATE OR REPLACE FUNCTION aviso_geral_conferir(aviso_id integer) RETURNS void AS $$
DECLARE
  aviso record;
  excesso integer;
BEGIN
  SELECT id_aviso_geral, publico, fixado INTO aviso FROM aviso_geral WHERE id_aviso_geral = aviso_id;
  IF NOT FOUND THEN RETURN; END IF;

  IF aviso.publico = 'professores' AND EXISTS (SELECT 1 FROM aviso_geral_turma WHERE fk_id_aviso_geral = aviso_id) THEN
    RAISE EXCEPTION 'professores-sem-turma' USING ERRCODE = 'check_violation';
  END IF;

  IF NOT aviso.fixado THEN RETURN; END IF;

  -- serializa os pedidos de fixar para não passar de 3 em pedidos simultâneos
  PERFORM pg_advisory_xact_lock(hashtext('aviso_geral_fixado'));

  IF aviso.publico = 'professores' THEN
    IF (SELECT COUNT(*) FROM aviso_geral WHERE publico = 'professores' AND fixado) > 3 THEN
      RAISE EXCEPTION 'limite-fixados' USING ERRCODE = 'check_violation';
    END IF;
    RETURN;
  END IF;

  -- turmas que veem este aviso (todas, se for para a escola inteira) e quantos fixados cada uma passa a ver
  SELECT COUNT(*) INTO excesso
  FROM turma t
  WHERE (NOT EXISTS (SELECT 1 FROM aviso_geral_turma WHERE fk_id_aviso_geral = aviso_id)
         OR EXISTS (SELECT 1 FROM aviso_geral_turma WHERE fk_id_aviso_geral = aviso_id AND fk_id_turma = t.id_turma))
    AND (SELECT COUNT(*) FROM aviso_geral g
         WHERE g.fixado AND g.publico IN ('alunos', 'responsaveis')
           AND (NOT EXISTS (SELECT 1 FROM aviso_geral_turma x WHERE x.fk_id_aviso_geral = g.id_aviso_geral)
                OR EXISTS (SELECT 1 FROM aviso_geral_turma x WHERE x.fk_id_aviso_geral = g.id_aviso_geral AND x.fk_id_turma = t.id_turma))) > 3;

  IF excesso > 0 THEN
    RAISE EXCEPTION 'limite-fixados' USING ERRCODE = 'check_violation';
  END IF;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION aviso_geral_conferir_gatilho() RETURNS trigger AS $$
BEGIN
  IF TG_TABLE_NAME = 'aviso_geral' THEN
    PERFORM aviso_geral_conferir(NEW.id_aviso_geral);
  ELSE
    PERFORM aviso_geral_conferir(NEW.fk_id_aviso_geral);
  END IF;
  RETURN NULL;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS aviso_geral_conferir ON aviso_geral;
CREATE CONSTRAINT TRIGGER aviso_geral_conferir AFTER INSERT OR UPDATE ON aviso_geral
  DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION aviso_geral_conferir_gatilho();

DROP TRIGGER IF EXISTS aviso_geral_turma_conferir ON aviso_geral_turma;
CREATE CONSTRAINT TRIGGER aviso_geral_turma_conferir AFTER INSERT OR UPDATE ON aviso_geral_turma
  DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION aviso_geral_conferir_gatilho();
