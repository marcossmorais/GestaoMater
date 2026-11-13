-- Mater — Migração 25: o professor titular (Fund. 1) só pode ter uma turma por turno no mesmo ano letivo.
--
-- Uma turma de manhã e outra de tarde é permitido; duas no mesmo turno, não (estaria em duas salas ao mesmo tempo).
-- A tela do admin já avisa antes (App/Models/GeneralManagement/Classe.php::setHomeroomTeacher); o índice garante.

CREATE UNIQUE INDEX IF NOT EXISTS turma_titular_turno_unico
  ON turma (fk_id_professor_titular, fk_id_turno, fk_id_periodo_letivo)
  WHERE fk_id_professor_titular IS NOT NULL;
