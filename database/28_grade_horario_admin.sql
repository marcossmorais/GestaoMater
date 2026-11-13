-- Mater — Migração 28: grade de horários montada pelo admin (perfil da turma → Horário).
--
-- horario_aula.fk_id_turno: cada faixa pertence a um turno; a grade da turma só usa as faixas do turno dela.
-- As faixas da migração 06 são ligadas pelo horário de início (antes das 12h = Matutino, antes das 18h = Vespertino).
-- grade_horario: uma disciplina por turma, dia e faixa.

ALTER TABLE horario_aula ADD COLUMN IF NOT EXISTS fk_id_turno integer
  REFERENCES turno (id_turno) ON UPDATE CASCADE;

UPDATE horario_aula SET fk_id_turno = (
  SELECT id_turno FROM turno WHERE nome_turno = CASE
    WHEN horario_aula.hora_inicio < '12:00' THEN 'Matutino'
    WHEN horario_aula.hora_inicio < '18:00' THEN 'Vespertino'
    ELSE 'Noturno' END
)
WHERE fk_id_turno IS NULL;

CREATE UNIQUE INDEX IF NOT EXISTS grade_horario_turma_dia_faixa_unico
  ON grade_horario (fk_id_turma, dia_semana, fk_id_horario_aula);
