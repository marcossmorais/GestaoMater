-- Mater — dados fictícios (só desenvolvimento): faixas de horário do Fund. 1 e a grade só com os especialistas.
-- Entrada igual à do Ensino Médio; recreio e saída próprios (horários inventados até a escola enviar os reais).
-- A grade da turma de Fund. 1 fica só com as aulas dos especialistas (o resto do dia é do titular), em horários
-- em que o especialista está livre nas outras turmas.

INSERT INTO horario_aula (ordem_aula, hora_inicio, hora_fim, fk_id_turno, etapa)
SELECT v.ordem, v.inicio::time, v.fim::time, turno.id_turno, 'iniciais'
FROM (VALUES
  ('Matutino', 1, '07:00', '07:50'), ('Matutino', 2, '07:50', '08:40'),
  ('Matutino', 3, '09:00', '09:50'), ('Matutino', 4, '09:50', '10:40'),
  ('Vespertino', 1, '13:00', '13:50'), ('Vespertino', 2, '13:50', '14:40'),
  ('Vespertino', 3, '15:00', '15:50'), ('Vespertino', 4, '15:50', '16:40')
) AS v (turno, ordem, inicio, fim)
INNER JOIN turno ON (turno.nome_turno = v.turno)
WHERE NOT EXISTS (
  SELECT 1 FROM horario_aula ha WHERE ha.fk_id_turno = turno.id_turno AND ha.etapa = 'iniciais' AND ha.ordem_aula = v.ordem
);

DO $$
DECLARE
  v_turma record;
  v_aula record;
  v_dia integer;
  v_faixa record;
  v_falta integer;
BEGIN
  FOR v_turma IN
    SELECT turma.id_turma, turma.fk_id_turno, turma.fk_id_professor_titular
    FROM turma INNER JOIN curso ON (curso.id_curso = turma.fk_id_curso)
    WHERE curso.etapa = 'iniciais'
  LOOP
    DELETE FROM grade_horario WHERE fk_id_turma = v_turma.id_turma;

    -- aulas por semana de cada especialista
    FOR v_aula IN
      SELECT td.id_turma_disciplina, td.fk_id_professor,
             CASE d.nome_disciplina WHEN 'Educação Física' THEN 2 WHEN 'Língua Inglesa' THEN 2 ELSE 1 END AS vezes
      FROM turma_disciplina td INNER JOIN disciplina d ON (d.id_disciplina = td.fk_id_disciplina)
      WHERE td.fk_id_turma = v_turma.id_turma AND td.fk_id_professor IS DISTINCT FROM v_turma.fk_id_professor_titular
      ORDER BY d.nome_disciplina
    LOOP
      v_falta := v_aula.vezes;
      -- um dia por aula, sem repetir o dia; a primeira faixa livre para a turma e para o professor
      FOR v_dia IN SELECT generate_series(2, 6) LOOP
        EXIT WHEN v_falta = 0;
        CONTINUE WHEN EXISTS (SELECT 1 FROM grade_horario g WHERE g.fk_id_turma = v_turma.id_turma AND g.dia_semana = v_dia AND g.fk_id_turma_disciplina = v_aula.id_turma_disciplina);
        SELECT ha.* INTO v_faixa
        FROM horario_aula ha
        WHERE ha.fk_id_turno = v_turma.fk_id_turno AND ha.etapa = 'iniciais'
        AND NOT EXISTS (SELECT 1 FROM grade_horario g WHERE g.fk_id_turma = v_turma.id_turma AND g.dia_semana = v_dia AND g.fk_id_horario_aula = ha.id_horario_aula)
        AND NOT EXISTS (
          SELECT 1 FROM grade_horario g
          INNER JOIN turma_disciplina td2 ON (td2.id_turma_disciplina = g.fk_id_turma_disciplina AND td2.fk_id_professor = v_aula.fk_id_professor)
          INNER JOIN horario_aula h2 ON (h2.id_horario_aula = g.fk_id_horario_aula)
          WHERE g.dia_semana = v_dia AND h2.hora_inicio < ha.hora_fim AND h2.hora_fim > ha.hora_inicio
        )
        ORDER BY (ha.ordem_aula + v_dia) % 4, ha.ordem_aula
        LIMIT 1;
        CONTINUE WHEN NOT FOUND;
        INSERT INTO grade_horario (fk_id_turma, fk_id_turma_disciplina, dia_semana, fk_id_horario_aula)
        VALUES (v_turma.id_turma, v_aula.id_turma_disciplina, v_dia, v_faixa.id_horario_aula);
        v_falta := v_falta - 1;
      END LOOP;
    END LOOP;
  END LOOP;
END $$;
