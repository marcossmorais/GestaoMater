-- Dev: uma turma de Anos Iniciais para testar o professor polivalente.
-- Cria o curso e move a última turma para ele (todos os professores dessa turma passam a atuar em todas as disciplinas).
INSERT INTO curso (id_curso, nome_curso, sigla, fk_id_modalidade_curso, etapa)
SELECT 2, 'Fundamental I', 'EF1', fk_id_modalidade_curso, 'iniciais' FROM curso WHERE id_curso = 1
ON CONFLICT DO NOTHING;

UPDATE turma SET fk_id_curso = 2 WHERE id_turma = (SELECT MAX(id_turma) FROM turma);
