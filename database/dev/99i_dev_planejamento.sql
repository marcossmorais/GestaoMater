-- Mater — dados fictícios (só desenvolvimento): tipos das avaliações e alguns planos de aula futuros

INSERT INTO avaliacao_tipo (fk_id_avaliacao, tipo)
SELECT id_avaliacao,
       CASE WHEN recuperacao THEN 'prova'
            WHEN id_avaliacao % 3 = 0 THEN 'trabalho'
            WHEN id_avaliacao % 3 = 1 THEN 'prova'
            ELSE 'atividade' END
FROM avaliacoes
ON CONFLICT DO NOTHING;

INSERT INTO plano_aula (fk_id_turma_disciplina, fk_id_professor, data_aula, titulo, objetivos, desenvolvimento, recursos, avaliacao, tarefa)
SELECT td.id_turma_disciplina, td.fk_id_professor, CURRENT_DATE + dias.n,
       'Revisão orientada ' || dias.n,
       'Retomar os conteúdos do bimestre e tirar dúvidas.',
       'Abertura: pergunta sobre a aula anterior. Desenvolvimento: exercícios em dupla. Fechamento: correção coletiva.',
       'Livro didático e quadro',
       'Participação e exercícios',
       'Lista de exercícios'
FROM turma_disciplina td
CROSS JOIN (VALUES (2), (7)) AS dias (n)
WHERE td.fk_id_professor IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM plano_aula);
