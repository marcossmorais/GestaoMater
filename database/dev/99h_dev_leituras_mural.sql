-- Dev: avisos e confirmações de leitura fictícios, para a atividade dos alunos na home do professor.
INSERT INTO aviso_turma (aviso, fk_id_turma_disciplina, fk_id_professor, data_postagem)
SELECT 'Revisem a lista de exercícios para a próxima avaliação.', td.id_turma_disciplina, td.fk_id_professor, CURRENT_TIMESTAMP - INTERVAL '3 days'
FROM turma_disciplina td
INNER JOIN disciplina d ON (td.fk_id_disciplina = d.id_disciplina)
WHERE d.nome_disciplina = 'Matemática'
AND NOT EXISTS (SELECT 1 FROM aviso_turma a WHERE a.fk_id_turma_disciplina = td.id_turma_disciplina);

INSERT INTO aviso_leitura (fk_id_aviso_turma, fk_id_matricula, lido_em)
SELECT a.id_aviso_turma, m.id_matricula, COALESCE(a.data_postagem, CURRENT_TIMESTAMP) + ((m.id_matricula % 47) || ' hours')::interval
FROM aviso_turma a
INNER JOIN turma_disciplina td ON (a.fk_id_turma_disciplina = td.id_turma_disciplina)
INNER JOIN matricula m ON (m.fk_id_turma_matricula = td.fk_id_turma AND m.fk_id_situacao_aluno = 1)
WHERE (m.id_matricula + a.id_aviso_turma) % 5 <> 0
ON CONFLICT DO NOTHING;
