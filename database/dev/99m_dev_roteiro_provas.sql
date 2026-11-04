-- Mater — dados fictícios (só desenvolvimento): roteiro nas avaliações, para a aba Roteiro de provas do aluno
-- A 3ª avaliação fica sem roteiro para mostrar o "A divulgar".

UPDATE avaliacoes a
SET roteiro_assuntos = CASE a.descricao_avaliacao
        WHEN '1ª avaliação' THEN 'Unidades 1 e 2' || E'\n' || 'Exercícios de fixação'
        ELSE 'Unidades 3 e 4' || E'\n' || 'Revisão do bimestre'
    END,
    roteiro_material = 'Livro de ' || d.nome_disciplina || ', caderno e listas de exercícios'
FROM turma_disciplina td
INNER JOIN disciplina d ON (td.fk_id_disciplina = d.id_disciplina)
WHERE a.fk_id_turma_disciplina_avaliacao = td.id_turma_disciplina
  AND a.descricao_avaliacao IN ('1ª avaliação', '2ª avaliação')
  AND a.roteiro_assuntos IS NULL;

UPDATE aviso_geral SET aviso = 'Calendário de provas do bimestre disponível na aba Roteiro de provas.' WHERE aviso LIKE '%aba Cronograma%';
