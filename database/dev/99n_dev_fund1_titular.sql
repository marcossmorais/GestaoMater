-- Mater — dados fictícios (só desenvolvimento): o 3º ano de Fund. 1 (99d_dev_anos_iniciais.sql) vira uma turma realista.
-- Titular (o professor de Língua Portuguesa da turma) com Português, Matemática, História, Geografia e Ciências;
-- especialistas em Inglês, Educação Física e Artes. Sai o que é do Ensino Médio (Física, Química, Biologia,
-- Filosofia, Sociologia). A chamada passa a ser a diária (database/24_fund1_titular_chamada_diaria.sql),
-- montada a partir das chamadas de Português já existentes; as chamadas e faltas por disciplina da turma saem.

INSERT INTO disciplina (nome_disciplina, fk_id_modalidade_disciplina, sigla_disciplina)
SELECT 'Ciências', (SELECT MIN(fk_id_modalidade_disciplina) FROM disciplina), 'CIE'
WHERE NOT EXISTS (SELECT 1 FROM disciplina WHERE nome_disciplina = 'Ciências');

DO $$
DECLARE
  v_turma integer := (SELECT MAX(turma.id_turma) FROM turma INNER JOIN curso ON (curso.id_curso = turma.fk_id_curso) WHERE curso.etapa = 'iniciais');
  v_titular integer;
  v_portugues integer;
  v_removidas integer[];
  v_alunos integer;
BEGIN
  IF v_turma IS NULL THEN RETURN; END IF;

  SELECT td.fk_id_professor, td.id_turma_disciplina INTO v_titular, v_portugues
  FROM turma_disciplina td INNER JOIN disciplina d ON (d.id_disciplina = td.fk_id_disciplina)
  WHERE td.fk_id_turma = v_turma AND d.nome_disciplina = 'Língua Portuguesa';

  IF v_titular IS NULL THEN RETURN; END IF;

  -- disciplinas do Ensino Médio não existem no Fund. 1
  SELECT array_agg(td.id_turma_disciplina) INTO v_removidas
  FROM turma_disciplina td INNER JOIN disciplina d ON (d.id_disciplina = td.fk_id_disciplina)
  WHERE td.fk_id_turma = v_turma AND d.nome_disciplina IN ('Física', 'Química', 'Biologia', 'Filosofia', 'Sociologia');

  IF v_removidas IS NOT NULL THEN
    DELETE FROM aviso_turma WHERE fk_id_turma_disciplina = ANY (v_removidas);
    UPDATE aula SET fk_id_turma_disciplina_ministrada = NULL WHERE fk_id_turma_disciplina_ministrada = ANY (v_removidas);
    DELETE FROM turma_disciplina WHERE id_turma_disciplina = ANY (v_removidas);
  END IF;

  INSERT INTO turma_disciplina (fk_id_disciplina, fk_id_turma, fk_id_professor)
  SELECT d.id_disciplina, v_turma, v_titular FROM disciplina d
  WHERE d.nome_disciplina = 'Ciências'
  AND NOT EXISTS (SELECT 1 FROM turma_disciplina td WHERE td.fk_id_turma = v_turma AND td.fk_id_disciplina = d.id_disciplina);

  -- o titular dá as disciplinas de base; Inglês, Ed. Física e Artes ficam com os especialistas
  UPDATE turma_disciplina td SET fk_id_professor = v_titular
  FROM disciplina d
  WHERE d.id_disciplina = td.fk_id_disciplina AND td.fk_id_turma = v_turma
  AND d.nome_disciplina IN ('Língua Portuguesa', 'Matemática', 'História', 'Geografia', 'Ciências');

  UPDATE turma SET fk_id_professor_titular = v_titular WHERE id_turma = v_turma;

  -- chamada do dia: os dias e ausentes das chamadas de Português já lançadas
  v_alunos := (SELECT COUNT(*) FROM matricula WHERE fk_id_turma_matricula = v_turma AND fk_id_situacao_aluno = 1);

  INSERT INTO chamada_diaria (fk_id_turma, fk_id_unidade, fk_id_professor, data_aula, total_presentes, total_ausentes, matriculas_ausentes, criado_em, atualizado_em)
  SELECT v_turma, MIN(c.fk_id_unidade), v_titular, c.data_aula,
         v_alunos - cardinality(ausentes.lista), cardinality(ausentes.lista), ausentes.lista,
         MIN(c.criado_em), MIN(c.criado_em)
  FROM chamada c
  CROSS JOIN LATERAL (
    SELECT COALESCE(array_agg(DISTINCT a ORDER BY a), '{}') AS lista
    FROM chamada c2, unnest(c2.matriculas_ausentes) a
    WHERE c2.fk_id_turma_disciplina = v_portugues AND c2.data_aula = c.data_aula
  ) ausentes
  WHERE c.fk_id_turma_disciplina = v_portugues
  GROUP BY c.data_aula, ausentes.lista
  ON CONFLICT ON CONSTRAINT chamada_diaria_turma_data_key DO NOTHING;

  -- no Fund. 1 não há falta por disciplina
  DELETE FROM chamada WHERE fk_id_turma_disciplina IN (SELECT id_turma_disciplina FROM turma_disciplina WHERE fk_id_turma = v_turma);
  DELETE FROM falta_aluno WHERE fk_id_turma_disciplina_falta IN (SELECT id_turma_disciplina FROM turma_disciplina WHERE fk_id_turma = v_turma);
END $$;
