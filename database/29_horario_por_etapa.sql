-- Mater — Migração 29: faixas de horário por etapa (Fund. 1 tem recreio e saída próprios).
--
-- horario_aula.etapa: NULL = faixa padrão do turno (Fund. 2 e Ensino Médio). Faixas com etapa = 'iniciais'
-- valem para as turmas do Fund. 1 daquele turno; se a etapa não tiver faixas próprias no turno, usa as padrão
-- (App/Models/GeneralManagement/ClassSchedule.php::slotsForClass).
-- Os horários reais do Fund. 1 entram quando a escola enviar (nada de horário inventado em produção);
-- no banco de desenvolvimento, database/dev/99o_dev_fund1_horarios.sql cria faixas fictícias.

ALTER TABLE horario_aula ADD COLUMN IF NOT EXISTS etapa varchar(20);

CREATE INDEX IF NOT EXISTS horario_aula_turno_etapa_idx ON horario_aula (fk_id_turno, etapa);
