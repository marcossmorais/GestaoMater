-- Dev: completa o 1º bimestre com a 3ª avaliação (regra do MATER: 3 avaliações de 0 a 10 por bimestre).
-- A nota fictícia acompanha o desempenho do aluno nas outras duas, com uma variação determinística.
INSERT INTO avaliacoes (descricao_avaliacao, valor_avaliacao, data_realizada, fk_id_unidade_avaliacao, fk_id_turma_disciplina_avaliacao)
SELECT 'Atividade 1º bimestre', 10, DATE '2026-04-15', 1, td.id_turma_disciplina
FROM turma_disciplina td
WHERE EXISTS (SELECT 1 FROM avaliacoes a WHERE a.fk_id_turma_disciplina_avaliacao = td.id_turma_disciplina AND a.fk_id_unidade_avaliacao = 1)
AND (SELECT COUNT(*) FROM avaliacoes a WHERE a.fk_id_turma_disciplina_avaliacao = td.id_turma_disciplina AND a.fk_id_unidade_avaliacao = 1 AND NOT a.recuperacao) < 3;

INSERT INTO nota_avaliacao (valor_nota, fk_id_avaliacao, fk_id_matricula_aluno)
SELECT LEAST(10, GREATEST(0, ROUND((AVG(n.valor_nota / a.valor_avaliacao * 10) + ((m.id_matricula + nova.id_avaliacao) % 5 - 2) * 0.5)::numeric * 2) / 2)),
       nova.id_avaliacao, m.id_matricula
FROM avaliacoes nova
JOIN avaliacoes a ON (a.fk_id_turma_disciplina_avaliacao = nova.fk_id_turma_disciplina_avaliacao AND a.fk_id_unidade_avaliacao = 1 AND a.id_avaliacao <> nova.id_avaliacao)
JOIN nota_avaliacao n ON (n.fk_id_avaliacao = a.id_avaliacao)
JOIN matricula m ON (m.id_matricula = n.fk_id_matricula_aluno)
WHERE nova.descricao_avaliacao = 'Atividade 1º bimestre'
GROUP BY nova.id_avaliacao, m.id_matricula
ON CONFLICT DO NOTHING;
