-- Mater — dados fictícios (só desenvolvimento): liga as faltas lançadas às chamadas antigas, para a aba Frequência listar os dias perdidos

-- as chamadas antigas caíam todas no mesmo dia da semana: leva cada uma para um dia de aula da disciplina
UPDATE chamada
SET data_aula = chamada.data_aula - ((EXTRACT(ISODOW FROM chamada.data_aula)::int - (grade.dia - 1) + 7) % 7)
FROM (SELECT fk_id_turma_disciplina AS dc, MIN(dia_semana) AS dia FROM grade_horario GROUP BY 1) grade
WHERE grade.dc = chamada.fk_id_turma_disciplina
  AND chamada.data_aula < CURRENT_DATE - 14
  AND EXTRACT(ISODOW FROM chamada.data_aula)::int <> grade.dia - 1;

WITH faltas AS (
    SELECT fk_id_matricula_falta AS matricula, fk_id_turma_disciplina_falta AS dc, SUM(total_faltas) AS total
    FROM falta_aluno
    GROUP BY 1, 2
), chamadas AS (
    SELECT id_chamada, fk_id_turma_disciplina AS dc,
           ROW_NUMBER() OVER (PARTITION BY fk_id_turma_disciplina ORDER BY data_aula DESC) AS posicao,
           COUNT(*) OVER (PARTITION BY fk_id_turma_disciplina) AS quantidade
    FROM chamada
    WHERE quantidade_aulas = 2 AND data_aula < CURRENT_DATE - 14
), ausentes AS (
    SELECT chamadas.id_chamada, array_agg(faltas.matricula) AS matriculas
    FROM faltas
    INNER JOIN chamadas ON (chamadas.dc = faltas.dc AND (chamadas.posicao + faltas.matricula) % chamadas.quantidade < CEIL(faltas.total / 2.0))
    GROUP BY 1
)
UPDATE chamada
SET matriculas_ausentes = ausentes.matriculas, total_ausentes = cardinality(ausentes.matriculas)
FROM ausentes
WHERE chamada.id_chamada = ausentes.id_chamada
  AND NOT EXISTS (SELECT 1 FROM chamada c WHERE c.data_aula < CURRENT_DATE - 14 AND cardinality(c.matriculas_ausentes) > 0);
