-- Mater — Migração 36: o nível "Co-administrador" do admin passa a se chamar "Coordenador".
-- Só o nome muda: o id 2 continua com as mesmas permissões (gestão do dia a dia, sem cadastrar admins,
-- sem configurações gerais e sem auditoria). Só troca se o nome ainda for o do seed.

UPDATE hierarquia_funcao
SET hierarquia_funcao = 'Coordenador'
WHERE id_hierarquia_funcao = 2 AND hierarquia_funcao = 'Co-administrador';
