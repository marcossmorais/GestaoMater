-- Mater — Migração 40: usuário próprio do app no banco (mater_app), com o mínimo de permissão.
-- Até aqui o app usava o mesmo usuário das migrações, dono das tabelas (no Docker, superusuário): uma falha de SQL
-- poderia desligar os gatilhos da auditoria, apagar tabelas ou rodar comandos no servidor do banco.
-- O mater_app só lê e grava dados: não cria nem altera tabela, não desliga gatilho e não altera nem apaga a auditoria.
--
-- O papel nasce sem login. Ele passa a entrar quando o database/migrate.php roda com DB_APP_USER=mater_app e
-- DB_APP_PASSWORD definidos (ALTER ROLE ... LOGIN PASSWORD); o app então usa esse usuário (App/Connection.php).
-- Migrações continuam com DB_USER (dono). Ver docs/DEPLOY.md.

DO $$
BEGIN
  IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'mater_app') THEN
    CREATE ROLE mater_app NOLOGIN NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT;
  END IF;
  EXECUTE format('GRANT CONNECT ON DATABASE %I TO mater_app', current_database());
END
$$;

GRANT USAGE ON SCHEMA public TO mater_app;
GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO mater_app;
GRANT USAGE, SELECT, UPDATE ON ALL SEQUENCES IN SCHEMA public TO mater_app;

-- tabelas que as próximas migrações criarem (pelo mesmo dono) já nascem liberadas
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO mater_app;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT USAGE, SELECT, UPDATE ON SEQUENCES TO mater_app;

-- auditoria: o app só acrescenta e consulta (o gatilho auditoria_imutavel já recusa, isto é a segunda barreira)
REVOKE UPDATE, DELETE, TRUNCATE ON auditoria FROM mater_app;

-- controle de migrações é só do dono (no banco criado pelo Docker a tabela ainda não existe; o migrate.php
-- repete o REVOKE toda vez que roda)
DO $$
BEGIN
  IF to_regclass('public.schema_migrations') IS NOT NULL THEN
    REVOKE ALL ON schema_migrations FROM mater_app;
  END IF;
END
$$;
