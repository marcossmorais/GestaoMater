-- Mater — Migração 33: séries por etapa (Fund. 1, Fund. 2 e Ensino Médio).
--
-- Antes havia só 1, 2 e 3, usadas pelo Médio e pelo Fund. 1, e o sistema escrevia "ª" depois da sigla em toda tela.
-- Agora cada etapa tem as suas séries e a sigla já traz o ordinal:
--   Fund. 1 (iniciais): 1º a 5º ano · Fund. 2 (finais): 6º a 9º ano · Médio: 1ª a 3ª série
-- Turma de "6º A · EF2" e "3ª A · EM". Na virada do ano, o aluno avança dentro da etapa; quem termina a última série
-- da etapa sai como concluinte e a coordenação o remaneja à mão depois da rematrícula (processo externo).

ALTER TABLE serie ADD COLUMN IF NOT EXISTS etapa varchar(10) NOT NULL DEFAULT 'medio';

DO $$
BEGIN
  IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'serie_etapa_check') THEN
    ALTER TABLE serie ADD CONSTRAINT serie_etapa_check CHECK (etapa IN ('iniciais', 'finais', 'medio'));
  END IF;
END $$;

-- séries do Médio: "1" vira "1ª" (nome "1ª série")
UPDATE serie SET sigla = sigla || 'ª', nome = sigla || 'ª série'
WHERE etapa = 'medio' AND sigla ~ '^[0-9]+$';

-- séries do Fundamental
INSERT INTO serie (nome, sigla, etapa)
SELECT v.n || 'º ano', v.n || 'º', v.etapa
FROM (VALUES (1, 'iniciais'), (2, 'iniciais'), (3, 'iniciais'), (4, 'iniciais'), (5, 'iniciais'),
             (6, 'finais'), (7, 'finais'), (8, 'finais'), (9, 'finais')) AS v (n, etapa)
WHERE NOT EXISTS (SELECT 1 FROM serie s WHERE s.sigla = v.n || 'º');

CREATE UNIQUE INDEX IF NOT EXISTS serie_sigla_idx ON serie (sigla);

-- turma de Fund. 1 ou Fund. 2 que usava série do Médio passa para o ano de mesmo número
UPDATE turma t
SET fk_id_serie = f.id_serie
FROM curso c, serie s, serie f
WHERE c.id_curso = t.fk_id_curso
  AND s.id_serie = t.fk_id_serie
  AND c.etapa IN ('iniciais', 'finais')
  AND s.etapa = 'medio'
  AND f.sigla = regexp_replace(s.sigla, '\D', '', 'g') || 'º';
