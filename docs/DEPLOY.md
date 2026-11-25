# Deploy em produção (VPS)

Pilha: `docker-compose.prod.yml` = Postgres + app (PHP/Apache, código dentro da imagem) + Caddy (HTTPS automático).
Desenvolvimento continua no `docker-compose.yml`.

## Antes
- VPS com Docker e o plugin compose; firewall só com 22, 80 e 443.
- DNS: registro `A` do domínio do portal apontando para o IP da VPS (o Caddy só emite o certificado depois disso).
- Clerk: instância **Production** com o mesmo domínio; chaves `pk_live_…` / `sk_live_…`.

## Primeira vez
```bash
git clone https://github.com/marcossmorais/GestaoMater.git /opt/mater && cd /opt/mater
cp .env.production.example .env && chmod 600 .env   # preencher: domínio, senhas, Clerk, APP_SIGNING_KEY
docker compose -f docker-compose.prod.yml up -d --build
docker compose -f docker-compose.prod.yml exec app php database/migrate.php        # cria o banco: 01 em diante
docker compose -f docker-compose.prod.yml exec app php database/create-admin.php --nome="Nome Completo" --email=pessoa@dominio
```
Depois: entrar em `https://<domínio>/admin` pelo Clerk com esse e-mail e, em **Configurações**, corrigir CPF, nascimento, telefone e endereço (vêm do seed fictício). Os próximos admins, professores e alunos são cadastrados pela tela.

O banco de produção não recebe `database/dev/` (dados fictícios), e a imagem não leva as fotos de perfil fictícias versionadas (só a `foto-vazia.jpg`, pelo `.dockerignore`). O admin do `02_seed.sql` fica sem e-mail (migração 23) até virar o admin real pelo `create-admin.php`.

Se o site responder **503 "Sistema temporariamente indisponível"**, a configuração de produção está incompleta: o motivo aparece em `docker compose -f docker-compose.prod.yml logs app | grep mater:` (Clerk desligado, `APP_SIGNING_KEY` curta ou a de desenvolvimento, `APP_URL` sem https). Corrija o `.env` e rode `up -d` de novo.

## Atualizar
```bash
cd /opt/mater && git pull
docker compose -f docker-compose.prod.yml up -d --build
docker compose -f docker-compose.prod.yml exec app php database/migrate.php        # só as pendentes
```
Recriar o container é o que aplica PHP/imagem novos (o processo antigo continua na memória até lá). Conferir a versão servida, não só o CLI.

## Usuário do app no banco (`mater_app`)
O app entra no banco com o `mater_app` (migração 40), que só lê e grava dados: não cria nem altera tabela, não desliga gatilho e não altera nem apaga a auditoria. O `DB_USER` (dono, superusuário no container) fica só para as migrações.

Ligar uma vez, depois do deploy que trouxer a migração 40:
```bash
cd /opt/mater
echo "DB_APP_USER=mater_app" >> .env
echo "DB_APP_PASSWORD=$(openssl rand -hex 24)" >> .env
docker compose -f docker-compose.prod.yml up -d                                  # app recebe as variáveis
docker compose -f docker-compose.prod.yml exec app php database/migrate.php      # liga o login do mater_app com essa senha
docker compose -f docker-compose.prod.yml restart app                            # conexões novas já como mater_app
```
Conferir: `docker compose -f docker-compose.prod.yml exec db psql -U mater -d mater -c "select usename, count(*) from pg_stat_activity where datname = 'mater' group by 1"` mostra o `mater_app`. Trocar a senha: mude `DB_APP_PASSWORD` no `.env` e repita os três comandos. Com `DB_APP_USER` vazio, o app volta a usar o `DB_USER`.

## O que guardar (backup)
Tudo o que o sistema recebe fica em volumes do Docker, fora da imagem:

| Volume | Conteúdo |
|---|---|
| `mater_pg-data` | banco (usar `pg_dump`, não copiar a pasta com o banco rodando) |
| `mater_storage` | anexos de atestado (dado de saúde) |
| `mater_photos-admin`, `mater_photos-student`, `mater_photos-teacher` | fotos de perfil |
| `mater_caddy-data` | certificados HTTPS (refeitos sozinhos se perder) |

`docker compose -f docker-compose.prod.yml down -v` **apaga** esses volumes. Para parar sem perder nada: `down` sem `-v`.

## Útil
```bash
docker compose -f docker-compose.prod.yml ps
docker compose -f docker-compose.prod.yml logs -f --tail=100 app
docker compose -f docker-compose.prod.yml exec app php database/migrate.php --status
```

## Login que não entra
- **"O e-mail X não está cadastrado neste portal"**: a conta do Clerk usa outro e-mail. Corrija o e-mail no cadastro (admin, professor ou aluno) ou entre com a conta certa. O e-mail precisa estar verificado no Clerk.
- **Conta do Clerk apagada e criada de novo, ou troca da instância de teste para a de produção**: o e-mail verificado religa o cadastro sozinho no próximo login (fica `VINCULO_TROCADO` na auditoria).
- **"Não foi possível validar o login"**: o motivo vai para o log do app (linha `Clerk: …`). Os mais comuns são `APP_URL` diferente do endereço aberto no navegador (o token só vale para essa origem) e chave do Clerk errada.

Conferir os cadastros e quem já entrou (só leitura):
```bash
docker compose -f docker-compose.prod.yml logs --tail=300 app | grep "Clerk:"
docker compose -f docker-compose.prod.yml exec db sh -c 'psql -U "$POSTGRES_USER" -d "$POSTGRES_DB" -c "SELECT id_administrador, nome_administrador, email_administrador, clerk_user_id IS NOT NULL AS ja_entrou, fk_id_situacao_conta_administrador AS situacao FROM administrador ORDER BY 1"'
```
