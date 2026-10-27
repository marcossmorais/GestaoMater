-- Mater — Migração 10: Trilha de auditoria (proposta v2.0, §05)
-- Log imutável de quem fez o quê, quando, com valor antigo e novo.
-- O ator vem da sessão PHP via set_config('mater.ator_*') (ver App/Tools/Audit.php).

CREATE TABLE IF NOT EXISTS auditoria (
  id_auditoria bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  ator_tipo varchar(20) NOT NULL DEFAULT 'sistema',
  ator_id integer,
  ator_nome varchar(150),
  acao varchar(40) NOT NULL,
  entidade varchar(60) NOT NULL,
  entidade_id varchar(40),
  antes jsonb,
  depois jsonb,
  ip inet,
  criado_em timestamptz NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS auditoria_entidade_idx ON auditoria (entidade, entidade_id);
CREATE INDEX IF NOT EXISTS auditoria_ator_idx ON auditoria (ator_tipo, ator_id);
CREATE INDEX IF NOT EXISTS auditoria_criado_em_idx ON auditoria (criado_em DESC);

-- Imutável: bloqueia UPDATE, DELETE e TRUNCATE
CREATE OR REPLACE FUNCTION auditoria_bloquear_alteracao() RETURNS trigger AS $$
BEGIN
  RAISE EXCEPTION 'auditoria é somente inserção (% bloqueado)', TG_OP;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS auditoria_imutavel ON auditoria;
CREATE TRIGGER auditoria_imutavel BEFORE UPDATE OR DELETE ON auditoria
  FOR EACH ROW EXECUTE FUNCTION auditoria_bloquear_alteracao();

DROP TRIGGER IF EXISTS auditoria_sem_truncate ON auditoria;
CREATE TRIGGER auditoria_sem_truncate BEFORE TRUNCATE ON auditoria
  FOR EACH STATEMENT EXECUTE FUNCTION auditoria_bloquear_alteracao();

-- Registro genérico por linha. TG_ARGV[0] = coluna da chave primária.
CREATE OR REPLACE FUNCTION auditoria_registrar() RETURNS trigger AS $$
DECLARE
  pk text := TG_ARGV[0];
BEGIN
  IF TG_OP = 'UPDATE' AND to_jsonb(OLD) = to_jsonb(NEW) THEN
    RETURN NULL;
  END IF;

  INSERT INTO auditoria (ator_tipo, ator_id, ator_nome, acao, entidade, entidade_id, antes, depois, ip)
  VALUES (
    COALESCE(NULLIF(current_setting('mater.ator_tipo', true), ''), 'sistema'),
    NULLIF(current_setting('mater.ator_id', true), '')::integer,
    NULLIF(current_setting('mater.ator_nome', true), ''),
    TG_OP,
    TG_TABLE_NAME,
    CASE WHEN TG_OP = 'DELETE' THEN to_jsonb(OLD) ->> pk ELSE to_jsonb(NEW) ->> pk END,
    CASE WHEN TG_OP IN ('UPDATE', 'DELETE') THEN to_jsonb(OLD) END,
    CASE WHEN TG_OP IN ('INSERT', 'UPDATE') THEN to_jsonb(NEW) END,
    NULLIF(current_setting('mater.ip', true), '')::inet
  );

  RETURN NULL;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS auditoria_nota_avaliacao ON nota_avaliacao;
CREATE TRIGGER auditoria_nota_avaliacao AFTER INSERT OR UPDATE OR DELETE ON nota_avaliacao
  FOR EACH ROW EXECUTE FUNCTION auditoria_registrar('id_nota');

DROP TRIGGER IF EXISTS auditoria_falta_aluno ON falta_aluno;
CREATE TRIGGER auditoria_falta_aluno AFTER INSERT OR UPDATE OR DELETE ON falta_aluno
  FOR EACH ROW EXECUTE FUNCTION auditoria_registrar('id_falta');

DROP TRIGGER IF EXISTS auditoria_chamada ON chamada;
CREATE TRIGGER auditoria_chamada AFTER INSERT OR UPDATE OR DELETE ON chamada
  FOR EACH ROW EXECUTE FUNCTION auditoria_registrar('id_chamada');

DROP TRIGGER IF EXISTS auditoria_atestado_medico ON atestado_medico;
CREATE TRIGGER auditoria_atestado_medico AFTER INSERT OR UPDATE OR DELETE ON atestado_medico
  FOR EACH ROW EXECUTE FUNCTION auditoria_registrar('id_atestado');

DROP TRIGGER IF EXISTS auditoria_ocorrencia_disciplinar ON ocorrencia_disciplinar;
CREATE TRIGGER auditoria_ocorrencia_disciplinar AFTER INSERT OR UPDATE OR DELETE ON ocorrencia_disciplinar
  FOR EACH ROW EXECUTE FUNCTION auditoria_registrar('id_ocorrencia');
