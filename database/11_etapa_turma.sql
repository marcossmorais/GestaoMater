-- Mater — Migração 11: etapa de ensino do curso (RBAC docente, proposta v2.0 §04)
-- iniciais = Fundamental Anos Iniciais (professor polivalente: atua em todas as disciplinas da turma)
-- finais / medio = professor especialista (edita só a própria disciplina)

ALTER TABLE curso ADD COLUMN IF NOT EXISTS etapa varchar(10) NOT NULL DEFAULT 'medio';

DO $$
BEGIN
  IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'curso_etapa_check') THEN
    ALTER TABLE curso ADD CONSTRAINT curso_etapa_check CHECK (etapa IN ('iniciais', 'finais', 'medio'));
  END IF;
END $$;
