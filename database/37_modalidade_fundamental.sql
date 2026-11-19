-- Mater — Migração 37: modalidade do segmento passa a ser "Ensino Fundamental" ou "Ensino Médio".
-- A opção herdada "Ensino Profissional, médio integrado" (curso técnico, que o MATER não oferece) vira
-- "Ensino Fundamental", e cada segmento fica com a modalidade da sua etapa (Fund. 1 e 2 → Fundamental, Médio → Médio).
-- A modalidade aparece no boletim impresso ("Nível / Modalidade de ensino").

UPDATE modalidade_curso
SET modalidade_curso = 'Ensino Fundamental', modalidade_curso_sigla = 'EF'
WHERE id_modalidade_curso = 2 AND modalidade_curso = 'Ensino Profissional, médio integrado';

UPDATE curso c
SET fk_id_modalidade_curso = m.id_modalidade_curso
FROM modalidade_curso m
WHERE m.modalidade_curso = CASE WHEN c.etapa IN ('iniciais', 'finais') THEN 'Ensino Fundamental' ELSE 'Ensino Médio' END
  AND c.fk_id_modalidade_curso IS DISTINCT FROM m.id_modalidade_curso;
