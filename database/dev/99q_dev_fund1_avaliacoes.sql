-- Mater — dados fictícios (só desenvolvimento): Fund. 1 na regra de AVs por disciplina (migração 32).
-- 1) Disciplinas de 2 AVs (Artes, Ed. Física, Inglês…) ficam com 2 avaliações regulares por bimestre:
--    a 3ª do seed antigo (e as notas dela) sai.
-- 2) As turmas do Fund. 1 ganham Filosofia e Língua Espanhola, com o professor que já dá a disciplina
--    (ou Inglês, para Espanhol) no Ensino Médio; ainda sem avaliações.
-- 3) Os tipos das AVs antigas viram os instrumentos do Fund. 1 (prova → prova escrita etc.).
-- Em produção, Língua Espanhola é cadastrada pelo admin (Gestão > Disciplinas); aqui o seed cria.

INSERT INTO disciplina (nome_disciplina, sigla_disciplina, fk_id_modalidade_disciplina)
SELECT 'Língua Espanhola', 'ESP', (SELECT MIN(fk_id_modalidade_disciplina) FROM disciplina)
WHERE NOT EXISTS (SELECT 1 FROM disciplina WHERE LOWER(nome_disciplina) = 'língua espanhola');

DELETE FROM avaliacoes a
USING (
  SELECT av.id_avaliacao,
         ROW_NUMBER() OVER (PARTITION BY av.fk_id_turma_disciplina_avaliacao, av.fk_id_unidade_avaliacao
                            ORDER BY av.data_realizada, av.id_avaliacao) AS ordem,
         avaliacoes_exigidas(av.fk_id_turma_disciplina_avaliacao) AS exigidas
  FROM avaliacoes av
  INNER JOIN turma_disciplina td ON (td.id_turma_disciplina = av.fk_id_turma_disciplina_avaliacao)
  INNER JOIN turma t ON (t.id_turma = td.fk_id_turma)
  INNER JOIN curso c ON (c.id_curso = t.fk_id_curso)
  WHERE c.etapa = 'iniciais' AND NOT av.recuperacao
) o
WHERE a.id_avaliacao = o.id_avaliacao AND o.ordem > o.exigidas;

INSERT INTO turma_disciplina (fk_id_disciplina, fk_id_turma, fk_id_professor)
SELECT d.id_disciplina, t.id_turma, prof.id_professor
FROM turma t
INNER JOIN curso c ON (c.id_curso = t.fk_id_curso AND c.etapa = 'iniciais')
CROSS JOIN (VALUES ('FIL', 'FIL'), ('ESP', 'ING')) AS v (sigla, sigla_professor)
INNER JOIN disciplina d ON (d.sigla_disciplina = v.sigla)
INNER JOIN LATERAL (
  SELECT td.fk_id_professor AS id_professor
  FROM turma_disciplina td INNER JOIN disciplina dp ON (dp.id_disciplina = td.fk_id_disciplina)
  WHERE dp.sigla_disciplina = v.sigla_professor
  ORDER BY td.id_turma_disciplina LIMIT 1
) prof ON true
WHERE NOT EXISTS (SELECT 1 FROM turma_disciplina x WHERE x.fk_id_turma = t.id_turma AND x.fk_id_disciplina = d.id_disciplina);

-- 3) AVs do Fund. 1 criadas pelo seed antigo com os tipos do Médio passam para o instrumento equivalente da lista do Fund. 1
WITH mapa (de, para) AS (VALUES
  ('prova', 'prova_escrita'), ('teste', 'simulado'), ('atividade', 'atividade_sala'), ('tarefa', 'lista_exercicios'),
  ('trabalho', 'trabalho_pesquisa'), ('apresentacao', 'seminario'), ('projeto', 'projeto_pedagogico'), ('pesquisa', 'trabalho_pesquisa'),
  ('redacao', 'producao_textual'), ('pratica', 'atividade_pratica'), ('participacao', 'atividade_sala'), ('outro', 'atividade_sala')
), alvo AS (
  SELECT DISTINCT at.fk_id_avaliacao, m.para
  FROM avaliacao_tipo at
  INNER JOIN mapa m ON (m.de = at.tipo)
  INNER JOIN avaliacoes a ON (a.id_avaliacao = at.fk_id_avaliacao)
  INNER JOIN turma_disciplina td ON (td.id_turma_disciplina = a.fk_id_turma_disciplina_avaliacao)
  INNER JOIN turma t ON (t.id_turma = td.fk_id_turma)
  INNER JOIN curso c ON (c.id_curso = t.fk_id_curso AND c.etapa = 'iniciais')
), apaga AS (
  DELETE FROM avaliacao_tipo at USING alvo WHERE at.fk_id_avaliacao = alvo.fk_id_avaliacao AND at.tipo IN (SELECT de FROM mapa)
)
INSERT INTO avaliacao_tipo (fk_id_avaliacao, tipo)
SELECT fk_id_avaliacao, para FROM alvo
ON CONFLICT DO NOTHING;
