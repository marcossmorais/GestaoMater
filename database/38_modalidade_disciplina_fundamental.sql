-- Mater — Migração 38: modalidade da disciplina passa a ser "Ensino Fundamental" ou "Ensino Médio".
-- A opção herdada "Ensino Técnico" (o MATER não tem curso técnico) vira "Ensino Fundamental". A modalidade da
-- disciplina só serve para filtrar a lista de disciplinas no admin.
-- Disciplina dada só em turmas do Fundamental (Fund. 1 ou 2) passa para Fundamental; a dada só no Médio, para
-- Médio; a que aparece nos dois segmentos (Português, Matemática…) fica como está.

UPDATE modalidade_disciplina
SET modalidade_disciplina = 'Ensino Fundamental'
WHERE id_modalidade_disciplina = 1 AND modalidade_disciplina = 'Ensino Técnico';

UPDATE disciplina d
SET fk_id_modalidade_disciplina = m.id_modalidade_disciplina
FROM (
  SELECT td.fk_id_disciplina AS id,
         CASE WHEN bool_and(c.etapa IN ('iniciais', 'finais')) THEN 'Ensino Fundamental'
              WHEN bool_and(c.etapa = 'medio') THEN 'Ensino Médio' END AS modalidade
  FROM turma_disciplina td
  INNER JOIN turma t ON (t.id_turma = td.fk_id_turma)
  INNER JOIN curso c ON (c.id_curso = t.fk_id_curso)
  GROUP BY td.fk_id_disciplina
) uso
INNER JOIN modalidade_disciplina m ON (m.modalidade_disciplina = uso.modalidade)
WHERE d.id_disciplina = uso.id
  AND d.fk_id_modalidade_disciplina IS DISTINCT FROM m.id_modalidade_disciplina;
