-- Mater — vínculo dos cadastros com usuários do Clerk
-- O vínculo é criado no primeiro login, pelo e-mail verificado do usuário.

ALTER TABLE administrador ADD COLUMN clerk_user_id varchar(64);
ALTER TABLE professor ADD COLUMN clerk_user_id varchar(64);
ALTER TABLE aluno ADD COLUMN clerk_user_id varchar(64);

CREATE UNIQUE INDEX administrador_clerk_user_id ON administrador (clerk_user_id);
CREATE UNIQUE INDEX professor_clerk_user_id ON professor (clerk_user_id);
CREATE UNIQUE INDEX aluno_clerk_user_id ON aluno (clerk_user_id);
