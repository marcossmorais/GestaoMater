-- Mater — Migração 39: auditoria nas tabelas sensíveis que ainda não tinham gatilho.
--   media_disciplina  médias finais (boletim)
--   matricula         situação da matrícula (aprovado, reprovado, transferido) e troca de turma
--   aluno, professor, administrador
--                     cadastro e conta: e-mail (é por ele que o login do Clerk se vincula), situação, hierarquia
--   turma_disciplina  qual professor edita qual disciplina da turma (é a permissão do docente)
--   periodo_letivo    datas e situação do ano letivo
-- A função passa a aceitar colunas que não entram no JSON (2º argumento em diante): o código de acesso é segredo
-- de login e não fica guardado na auditoria.

CREATE OR REPLACE FUNCTION auditoria_registrar() RETURNS trigger AS $$
DECLARE
  pk text := TG_ARGV[0];
  ocultar text[] := TG_ARGV[1:TG_NARGS - 1];
  antes jsonb;
  depois jsonb;
BEGIN
  IF TG_OP IN ('UPDATE', 'DELETE') THEN antes := to_jsonb(OLD) - ocultar; END IF;
  IF TG_OP IN ('INSERT', 'UPDATE') THEN depois := to_jsonb(NEW) - ocultar; END IF;

  -- nada mudou (ou só mudou o que não se registra)
  IF TG_OP = 'UPDATE' AND antes = depois THEN
    RETURN NULL;
  END IF;

  INSERT INTO auditoria (ator_tipo, ator_id, ator_nome, acao, entidade, entidade_id, antes, depois, ip)
  VALUES (
    COALESCE(NULLIF(current_setting('mater.ator_tipo', true), ''), 'sistema'),
    NULLIF(current_setting('mater.ator_id', true), '')::integer,
    NULLIF(current_setting('mater.ator_nome', true), ''),
    TG_OP,
    TG_TABLE_NAME,
    COALESCE(depois, antes) ->> pk,
    antes,
    depois,
    NULLIF(current_setting('mater.ip', true), '')::inet
  );

  RETURN NULL;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS auditoria_media_disciplina ON media_disciplina;
CREATE TRIGGER auditoria_media_disciplina AFTER INSERT OR UPDATE OR DELETE ON media_disciplina
  FOR EACH ROW EXECUTE FUNCTION auditoria_registrar('id_media_disciplina');

DROP TRIGGER IF EXISTS auditoria_matricula ON matricula;
CREATE TRIGGER auditoria_matricula AFTER INSERT OR UPDATE OR DELETE ON matricula
  FOR EACH ROW EXECUTE FUNCTION auditoria_registrar('id_matricula');

DROP TRIGGER IF EXISTS auditoria_aluno ON aluno;
CREATE TRIGGER auditoria_aluno AFTER INSERT OR UPDATE OR DELETE ON aluno
  FOR EACH ROW EXECUTE FUNCTION auditoria_registrar('id_aluno', 'codigo_acesso');

DROP TRIGGER IF EXISTS auditoria_professor ON professor;
CREATE TRIGGER auditoria_professor AFTER INSERT OR UPDATE OR DELETE ON professor
  FOR EACH ROW EXECUTE FUNCTION auditoria_registrar('id_professor', 'codigo_acesso');

DROP TRIGGER IF EXISTS auditoria_administrador ON administrador;
CREATE TRIGGER auditoria_administrador AFTER INSERT OR UPDATE OR DELETE ON administrador
  FOR EACH ROW EXECUTE FUNCTION auditoria_registrar('id_administrador', 'codigo_acesso');

DROP TRIGGER IF EXISTS auditoria_turma_disciplina ON turma_disciplina;
CREATE TRIGGER auditoria_turma_disciplina AFTER INSERT OR UPDATE OR DELETE ON turma_disciplina
  FOR EACH ROW EXECUTE FUNCTION auditoria_registrar('id_turma_disciplina');

DROP TRIGGER IF EXISTS auditoria_periodo_letivo ON periodo_letivo;
CREATE TRIGGER auditoria_periodo_letivo AFTER INSERT OR UPDATE OR DELETE ON periodo_letivo
  FOR EACH ROW EXECUTE FUNCTION auditoria_registrar('id_ano_letivo');
