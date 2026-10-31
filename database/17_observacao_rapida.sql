-- Mater — Migração 17: observações rápidas do docente sobre o aluno (botões de um toque na página do aluno)
-- Reaproveita observacao_aluno; grava quem escreveu e passa a ser auditada (dado sensível de menor).

ALTER TABLE observacao_aluno ADD COLUMN IF NOT EXISTS fk_id_professor integer REFERENCES professor (id_professor) ON UPDATE CASCADE ON DELETE SET NULL;
CREATE INDEX IF NOT EXISTS observacao_aluno_matricula_idx ON observacao_aluno (fk_id_matricula_observacao, data_postagem DESC);

DROP TRIGGER IF EXISTS auditoria_observacao_aluno ON observacao_aluno;
CREATE TRIGGER auditoria_observacao_aluno AFTER INSERT OR UPDATE OR DELETE ON observacao_aluno
  FOR EACH ROW EXECUTE FUNCTION auditoria_registrar('id_observacao');
