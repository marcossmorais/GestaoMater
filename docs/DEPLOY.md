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

## Atualizar
```bash
cd /opt/mater && git pull
docker compose -f docker-compose.prod.yml up -d --build
docker compose -f docker-compose.prod.yml exec app php database/migrate.php        # só as pendentes
```
Recriar o container é o que aplica PHP/imagem novos (o processo antigo continua na memória até lá). Conferir a versão servida, não só o CLI.

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
