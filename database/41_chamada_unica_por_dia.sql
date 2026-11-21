-- Mater — Migração 41: uma chamada por disciplina da turma por dia (Fund. 2 e Ensino Médio).
--
-- Antes, cada envio da chamada somava as faltas de novo em falta_aluno: um toque duplo ou um reenvio depois de
-- uma queda de conexão lançava as faltas em dobro, e corrigir um aluno só pelo banco. Agora a chamada da
-- disciplina no dia é uma só: salvar de novo corrige (tira as faltas da versão anterior e lança as novas), como na
-- chamada diária do Fund. 1 (migração 24). Duas aulas da mesma disciplina no mesmo dia = "aulas seguidas" 2.
--
-- chamada.corrigido_em: quando a chamada foi corrigida pela última vez (NULL = nunca).
--
-- Chamadas repetidas que já existam na mesma disciplina e data viram uma só (a mais recente), com a soma das
-- aulas (até 6) e a união dos ausentes. As faltas já lançadas em falta_aluno não mudam.

ALTER TABLE chamada ADD COLUMN IF NOT EXISTS corrigido_em timestamp(0);

WITH grupos AS (
  SELECT fk_id_turma_disciplina, data_aula, MAX(id_chamada) AS manter,
         LEAST(SUM(quantidade_aulas), 6)::smallint AS aulas
  FROM chamada
  GROUP BY fk_id_turma_disciplina, data_aula
  HAVING COUNT(*) > 1
),
ausentes AS (
  SELECT g.manter, COALESCE(array_agg(DISTINCT m ORDER BY m) FILTER (WHERE m IS NOT NULL), '{}') AS lista
  FROM grupos g
  JOIN chamada c ON (c.fk_id_turma_disciplina = g.fk_id_turma_disciplina AND c.data_aula = g.data_aula)
  LEFT JOIN LATERAL unnest(c.matriculas_ausentes) AS m ON true
  GROUP BY g.manter
)
UPDATE chamada c
SET quantidade_aulas = g.aulas,
    matriculas_ausentes = a.lista,
    total_ausentes = cardinality(a.lista),
    total_presentes = GREATEST(c.total_presentes + c.total_ausentes - cardinality(a.lista), 0),
    corrigido_em = CURRENT_TIMESTAMP
FROM grupos g
JOIN ausentes a ON (a.manter = g.manter)
WHERE c.id_chamada = g.manter;

DELETE FROM chamada c
USING chamada mais_nova
WHERE mais_nova.fk_id_turma_disciplina = c.fk_id_turma_disciplina
  AND mais_nova.data_aula = c.data_aula
  AND mais_nova.id_chamada > c.id_chamada;

CREATE UNIQUE INDEX IF NOT EXISTS chamada_disciplina_data_key ON chamada (fk_id_turma_disciplina, data_aula);
