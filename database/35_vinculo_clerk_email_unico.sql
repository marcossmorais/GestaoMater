-- Mater — Migração 35: Vínculo Clerk atrelado ao e-mail atual e unicidade de e-mail por portal
-- 1. Trocar ou limpar o e-mail de um cadastro invalida o clerk_user_id anterior (ex-usuário não mantém acesso).
-- 2. Impede e-mail duplicado dentro do mesmo portal (evita bloqueio no primeiro login e apropriação de e-mail).

UPDATE administrador SET email_administrador = NULL WHERE email_administrador IS NOT NULL AND BTRIM(email_administrador) = '';
UPDATE professor SET email_professor = NULL WHERE email_professor IS NOT NULL AND BTRIM(email_professor) = '';
UPDATE aluno SET email_aluno = NULL WHERE email_aluno IS NOT NULL AND BTRIM(email_aluno) = '';

-- Se um banco já existente tiver e-mail repetido, mantém apenas no cadastro mais antigo antes de criar o índice único
UPDATE administrador a SET email_administrador = NULL, clerk_user_id = NULL
 WHERE email_administrador IS NOT NULL
   AND EXISTS (SELECT 1 FROM administrador b
                WHERE LOWER(BTRIM(b.email_administrador)) = LOWER(BTRIM(a.email_administrador))
                  AND b.id_administrador < a.id_administrador);

UPDATE professor p SET email_professor = NULL, clerk_user_id = NULL
 WHERE email_professor IS NOT NULL
   AND EXISTS (SELECT 1 FROM professor q
                WHERE LOWER(BTRIM(q.email_professor)) = LOWER(BTRIM(p.email_professor))
                  AND q.id_professor < p.id_professor);

UPDATE aluno a SET email_aluno = NULL, clerk_user_id = NULL
 WHERE email_aluno IS NOT NULL
   AND EXISTS (SELECT 1 FROM aluno b
                WHERE LOWER(BTRIM(b.email_aluno)) = LOWER(BTRIM(a.email_aluno))
                  AND b.id_aluno < a.id_aluno);

CREATE UNIQUE INDEX IF NOT EXISTS administrador_email_unico_idx
  ON administrador (LOWER(BTRIM(email_administrador)))
  WHERE email_administrador IS NOT NULL AND BTRIM(email_administrador) <> '';

CREATE UNIQUE INDEX IF NOT EXISTS professor_email_unico_idx
  ON professor (LOWER(BTRIM(email_professor)))
  WHERE email_professor IS NOT NULL AND BTRIM(email_professor) <> '';

CREATE UNIQUE INDEX IF NOT EXISTS aluno_email_unico_idx
  ON aluno (LOWER(BTRIM(email_aluno)))
  WHERE email_aluno IS NOT NULL AND BTRIM(email_aluno) <> '';

-- Ao alterar ou apagar o e-mail do cadastro, desfaz o vínculo com a conta Clerk anterior
CREATE OR REPLACE FUNCTION limpar_vinculo_clerk_ao_mudar_email() RETURNS trigger AS $$
DECLARE
  col text := TG_ARGV[0];
  antigo text := LOWER(BTRIM(COALESCE(to_jsonb(OLD) ->> col, '')));
  novo text := LOWER(BTRIM(COALESCE(to_jsonb(NEW) ->> col, '')));
BEGIN
  IF antigo IS DISTINCT FROM novo THEN
    NEW.clerk_user_id := NULL;
  END IF;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS administrador_limpar_clerk ON administrador;
CREATE TRIGGER administrador_limpar_clerk BEFORE UPDATE ON administrador
  FOR EACH ROW EXECUTE FUNCTION limpar_vinculo_clerk_ao_mudar_email('email_administrador');

DROP TRIGGER IF EXISTS professor_limpar_clerk ON professor;
CREATE TRIGGER professor_limpar_clerk BEFORE UPDATE ON professor
  FOR EACH ROW EXECUTE FUNCTION limpar_vinculo_clerk_ao_mudar_email('email_professor');

DROP TRIGGER IF EXISTS aluno_limpar_clerk ON aluno;
CREATE TRIGGER aluno_limpar_clerk BEFORE UPDATE ON aluno
  FOR EACH ROW EXECUTE FUNCTION limpar_vinculo_clerk_ao_mudar_email('email_aluno');
