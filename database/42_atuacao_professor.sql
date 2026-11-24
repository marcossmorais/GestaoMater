-- Mater — Migração 42: atuação e matérias do professor, informadas já no cadastro.
--
-- professor.atuacao: 'polivalente' (anos iniciais, titular de turma do Fund. 1) ou 'especialista' (Fund. 2, Médio
-- ou disciplina específica, como Inglês ou Educação Física no Fund. 1). NULL = não informada (cadastros antigos).
--
-- professor_disciplina: as matérias que o professor leciona, mesmo antes de ele ter turma. O vínculo que dá
-- permissão continua sendo turma_disciplina; esta tabela só registra a formação/atuação. Todo vínculo em
-- turma_disciplina (cadastro, perfil da turma, virada de ano) entra aqui também, inclusive disciplina criada depois;
-- perder a turma não tira a matéria do professor. Os vínculos que já existem entram na criação.

ALTER TABLE professor ADD COLUMN IF NOT EXISTS atuacao varchar(12);

DO $$
BEGIN
  IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'professor_atuacao_check') THEN
    ALTER TABLE professor ADD CONSTRAINT professor_atuacao_check CHECK (atuacao IN ('polivalente', 'especialista'));
  END IF;
END $$;

CREATE TABLE IF NOT EXISTS professor_disciplina (
  fk_id_professor integer NOT NULL REFERENCES professor (id_professor) ON UPDATE CASCADE ON DELETE CASCADE,
  fk_id_disciplina integer NOT NULL REFERENCES disciplina (id_disciplina) ON UPDATE CASCADE ON DELETE CASCADE,
  PRIMARY KEY (fk_id_professor, fk_id_disciplina)
);

CREATE INDEX IF NOT EXISTS professor_disciplina_disciplina_idx ON professor_disciplina (fk_id_disciplina);

DROP TRIGGER IF EXISTS auditoria_professor_disciplina ON professor_disciplina;
CREATE TRIGGER auditoria_professor_disciplina AFTER INSERT OR UPDATE OR DELETE ON professor_disciplina
  FOR EACH ROW EXECUTE FUNCTION auditoria_registrar('fk_id_professor');

CREATE OR REPLACE FUNCTION professor_disciplina_do_vinculo() RETURNS trigger AS $$
BEGIN
  INSERT INTO professor_disciplina (fk_id_professor, fk_id_disciplina)
  VALUES (NEW.fk_id_professor, NEW.fk_id_disciplina)
  ON CONFLICT DO NOTHING;
  RETURN NULL;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS professor_disciplina_do_vinculo ON turma_disciplina;
CREATE TRIGGER professor_disciplina_do_vinculo AFTER INSERT OR UPDATE OF fk_id_professor, fk_id_disciplina ON turma_disciplina
  FOR EACH ROW EXECUTE FUNCTION professor_disciplina_do_vinculo();

INSERT INTO professor_disciplina (fk_id_professor, fk_id_disciplina)
SELECT DISTINCT fk_id_professor, fk_id_disciplina FROM turma_disciplina
ON CONFLICT DO NOTHING;
