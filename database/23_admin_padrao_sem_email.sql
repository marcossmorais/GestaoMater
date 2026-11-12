-- Mater — Migração 23: o admin fictício do 02_seed.sql deixa de ter e-mail.
-- Com o Clerk, o primeiro login vincula a conta pelo e-mail verificado: quem tivesse o Gmail do seed
-- entraria como Administrador em produção. Sem e-mail, ninguém vincula; o primeiro admin real é criado por
-- database/create-admin.php. Em dev o login por nome + código continua igual (não usa e-mail).
-- Idempotente: só mexe enquanto o registro ainda é o do seed e nunca foi vinculado ao Clerk.

UPDATE administrador
SET email_administrador = NULL
WHERE email_administrador = 'usuariopadrao@gmail.com'
  AND clerk_user_id IS NULL;
