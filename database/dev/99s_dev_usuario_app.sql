-- Mater — só desenvolvimento: o usuário do app (migração 40) entra com a senha fixa de dev, a mesma do
-- DB_APP_PASSWORD padrão do docker-compose.yml. Em produção quem liga o login é o database/migrate.php.
ALTER ROLE mater_app WITH LOGIN PASSWORD 'mater_app_somente_dev';
