# Mater — Sistema de Gestão Escolar

Aplicação web de gestão escolar com 3 portais:

- **Admin** (gestores): cursos, turmas, disciplinas, docentes, alunos, períodos letivos, rematrícula
- **Docente**: avaliações, notas, faltas, avisos da turma
- **Aluno**: boletim, notas, faltas, avisos, rematrícula

Stack: PHP 8.4 (MVC próprio em `vendor/MF`), PostgreSQL (compatível com Supabase), Clerk para autenticação, Bootstrap + jQuery.

## Rodando localmente (Docker)

```bash
cp .env.example .env
docker compose up -d --build
```

- App: http://localhost:8000
- Banco: Postgres em `localhost:5433` (usuário/senha/banco em `.env`, padrão `mater`/`mater`/`mater`)

Na primeira subida o banco é criado a partir de `database/` e recebe uma escola fictícia de teste (`database/dev/99_dev_seed.sql`): 9 turmas, 12 disciplinas, 14 professores e 300 alunos com notas e faltas da 1ª unidade. Usuários para login em [docs/USUARIOS_TESTE.md](docs/USUARIOS_TESTE.md). Para regerar os dados: `docker run --rm -v "$PWD":/w -w /w python:3.12-alpine python -I database/dev/generate_dev_seed.py`.

Para recriar o banco do zero:

```bash
docker compose down -v && docker compose up -d
```

### Migrações em banco existente

O Postgres do Docker só roda `database/` na criação do volume. Para aplicar migrações novas num banco que já existe (o de produção, ou o seu de dev depois de um `git pull`), use o executor. Ele registra o que já rodou em `schema_migrations`, aplica só as pendentes (cada uma numa transação) e nunca roda `database/dev/`:

```bash
MSYS_NO_PATHCONV=1 docker exec mater-app php /var/www/html/database/migrate.php --status   # o que falta
MSYS_NO_PATHCONV=1 docker exec mater-app php /var/www/html/database/migrate.php            # aplica
```

Banco criado pelo Docker ainda não tem registro: na primeira vez, informe até qual número ele já está (normalmente o último arquivo montado no `docker-compose.yml`), ex.: `--baseline=22`. Sem isso o executor se recusa a rodar, para não recriar tabelas. Banco vazio (produção) não precisa de baseline: recebe de `01` em diante.

## Rodando no macOS sem Docker

Mais leve que o Docker no Mac: PHP 8.4 e Postgres do Homebrew, com o servidor embutido do PHP.

```bash
brew install php@8.4 postgresql@16 && brew services start postgresql@16
psql -d postgres -c "CREATE ROLE mater LOGIN PASSWORD 'mater'" -c "CREATE DATABASE mater OWNER mater"
for f in database/0*.sql database/dev/99_dev_seed.sql; do PGPASSWORD=mater psql -h 127.0.0.1 -U mater -d mater -q -f $f; done
sed 's/^DB_HOST=db/DB_HOST=127.0.0.1/' .env.example > .env
./dev/start.sh
```

App em http://localhost:8000. Outra porta: `PORT=8080 ./dev/start.sh`.

### Login sem Clerk (apenas desenvolvimento)

Com `CLERK_*` vazios no `.env`, vale o login antigo por nome completo + código de acesso (ex.: admin `Usuário Padrão` / `867532`). Nomes e códigos de professores e alunos em [docs/USUARIOS_TESTE.md](docs/USUARIOS_TESTE.md).

## Autenticação com Clerk

1. Crie uma aplicação em https://dashboard.clerk.com (login por e-mail).
2. Copie as chaves em **API Keys** para o `.env`:
   ```
   CLERK_PUBLISHABLE_KEY=pk_test_...
   CLERK_SECRET_KEY=sk_test_...
   ```
3. `docker compose up -d` para aplicar.

Como funciona:

- As telas de login dos 3 portais passam a usar o componente do Clerk; o login por nome + código é desligado.
- Após entrar, o token de sessão é validado no servidor (assinatura, expiração e origem) e o **e-mail verificado** do usuário no Clerk é comparado com o e-mail do cadastro no portal escolhido (`email_administrador`, `email_professor` ou `email_aluno`).
- No primeiro acesso o cadastro é vinculado ao usuário do Clerk (`clerk_user_id`); depois disso o vínculo vale mesmo se o e-mail mudar.
- Quem não tem cadastro com aquele e-mail não entra. Portanto **todo admin, docente e aluno precisa ter e-mail preenchido no cadastro**.
- Em produção, defina `APP_URL` com o domínio real (ou `CLERK_AUTHORIZED_PARTIES` com uma lista separada por vírgulas).

## Banco no Supabase

1. Crie um projeto em https://supabase.com.
2. Com os dados de conexão do passo 3 no `.env`, rode `php database/migrate.php`: aplica todas as migrações em ordem (de `01` em diante).
   Não rode `database/dev/` (dados fictícios).
3. Em **Project Settings → Database → Connection string**, pegue os dados do *Session pooler* e configure:
   ```
   DB_HOST=aws-0-<regiao>.pooler.supabase.com
   DB_PORT=5432
   DB_NAME=postgres
   DB_USER=postgres.<ref-do-projeto>
   DB_PASSWORD=<senha do banco>
   DB_SSLMODE=require
   ```

## Testes

Smoke test que loga nos 3 portais e chama todas as rotas procurando erros de PHP/SQL (use só no banco local). Precisa do login por código, então rode o app com `CLERK_*` vazios (`CLERK_PUBLISHABLE_KEY= CLERK_SECRET_KEY= docker compose up -d app`). O `--writes` altera os dados de teste; recrie o banco depois.

```bash
docker exec mater-app sh /var/www/html/docker/smoke-test.sh
docker exec mater-app sh /var/www/html/docker/smoke-test.sh --writes
```

Rotas conhecidas que apontam para métodos inexistentes (herdadas do projeto original): `/admin/gestao/turma/dados`, `/admin/gestao/turma/lista-cursos`, `/admin/gestao/turma/lista-salas`, `/admin/gestao/turma/turmas-disponiveis`, `/admin/gestao/disciplina/disponiveis` e as exclusões de curso, turma e período letivo.

## Identidade visual

Logo original e variações em `brand/` e `public/assets/img/logo-components/`. Paleta: azul royal `#1868E8`, azul profundo `#0838B8`, azul oceano `#0878C8`, amarelo-ouro `#F8A808` (ver `public/assets/sass/abstracts/_colors.scss`).

## Créditos

Baseado no projeto de TCC de João Pedro Rodrigues Souza e Matheus de Souza Barbosa (CETEPI-I, Paulo Afonso-BA, 2021).
