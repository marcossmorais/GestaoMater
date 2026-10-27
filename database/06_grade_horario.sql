-- Mater — Migração 06: Grade de horários semanal
CREATE TABLE IF NOT EXISTS horario_aula (
  id_horario_aula SERIAL PRIMARY KEY,
  ordem_aula INTEGER NOT NULL,
  hora_inicio TIME NOT NULL,
  hora_fim TIME NOT NULL
);

INSERT INTO horario_aula (id_horario_aula, ordem_aula, hora_inicio, hora_fim) VALUES
  (1, 1, '07:00', '07:50'),
  (2, 2, '07:50', '08:40'),
  (3, 3, '08:40', '09:30'),
  (4, 4, '09:50', '10:40'),
  (5, 5, '10:40', '11:30'),
  (6, 1, '13:00', '13:50'),
  (7, 2, '13:50', '14:40'),
  (8, 3, '14:40', '15:30'),
  (9, 4, '15:50', '16:40'),
  (10, 5, '16:40', '17:30')
ON CONFLICT DO NOTHING;
SELECT setval(pg_get_serial_sequence('horario_aula', 'id_horario_aula'), (SELECT MAX(id_horario_aula) FROM horario_aula));

CREATE TABLE IF NOT EXISTS grade_horario (
  id_grade_horario SERIAL PRIMARY KEY,
  fk_id_turma INTEGER NOT NULL REFERENCES turma(id_turma) ON UPDATE CASCADE ON DELETE CASCADE,
  fk_id_turma_disciplina INTEGER NOT NULL REFERENCES turma_disciplina(id_turma_disciplina) ON UPDATE CASCADE ON DELETE CASCADE,
  dia_semana INTEGER NOT NULL CHECK (dia_semana BETWEEN 1 AND 7),
  fk_id_horario_aula INTEGER NOT NULL REFERENCES horario_aula(id_horario_aula) ON UPDATE CASCADE ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_grade_horario_turma ON grade_horario (fk_id_turma);
CREATE INDEX IF NOT EXISTS idx_grade_horario_td ON grade_horario (fk_id_turma_disciplina);
