# AGENTS.md — Mater · Gestão Pedagógica

Guia para agentes (e humanos). Leia antes de mexer. Escopo comercial: [docs/PROPOSTA_ESCOPO.md](docs/PROPOSTA_ESCOPO.md).

## Escopo atual
- Trabalhamos **só** nos portais **Docente** (`/portal-docente`) e **Aluno** (`/portal-aluno`).
- **Não** mexer no portal Admin (`/admin`) sem pedido explícito. Só leitura/ajuste mínimo se um fluxo docente/aluno depender dele.
- **Fora de escopo** (já existe no sistema do colégio): rematrícula, portaria/autorizados, financeiro, matrícula primária. Remover o que sobrar disso nos portais docente/aluno.

## Stack
PHP 8.4 (MVC próprio em `vendor/MF`) · PostgreSQL 17 · Apache · Docker · Clerk (auth) · Bootstrap 4 + jQuery.

## Estrutura
```
App/Controllers/{Admin,Auth,Index,StudentPortal,TeacherPortal}
App/Models/*          # um model por tabela/conceito
App/Views/*           # views PHP; layouts em App/Views/layouts
App/Auth/             # Clerk + vínculo de conta
App/Route.php         # todas as rotas
database/NN_nome.sql  # migrações numeradas, em ordem; dev/ = dados fictícios
docker/               # Dockerfile, smoke-test.sh
docs/                 # specs e usuários de teste
public/               # docroot (index.php, assets)
vendor/MF             # micro-framework do projeto (versionado)
```

## Comandos
```bash
cp .env.example .env && docker compose up -d --build   # app em :8000, db em :5433
docker compose down -v && docker compose up -d          # recria banco
MSYS_NO_PATHCONV=1 docker exec mater-app php /var/www/html/database/migrate.php   # aplica migrações pendentes em banco existente (--status, --baseline=NN)
MSYS_NO_PATHCONV=1 docker exec mater-app sh /var/www/html/docker/smoke-test.sh           # leitura
MSYS_NO_PATHCONV=1 docker exec mater-app sh /var/www/html/docker/smoke-test.sh --writes  # escrita (só dev)
MSYS_NO_PATHCONV=1 docker exec mater-app php /var/www/html/tests/GradeSimulatorTest.php  # testes unitários (tests/*Test.php)
docker run --rm -v "$PWD":/app -w /app composer:2 update   # atualizar dependências PHP
```
Smoke test precisa do login por código: suba com `CLERK_*` vazios. Antes de abrir PR: smoke e testes com **0 erros**.

## Git
- Nunca commitar direto em `main`. Branch por tarefa: `feat/…`, `fix/…`, `chore/…`, `docs/…`.
- Commits: Conventional Commits, escopo opcional: `feat(docente): chamada rápida`. Assunto ≤ 72 chars, imperativo, em português ou inglês (seguir o histórico: inglês no assunto, português no corpo se precisar).
- Commits pequenos e atômicos. Um assunto por commit. Não misturar refactor com feature.
- Nada de `--force` em branch compartilhada, nada de `--no-verify`.
- Nunca commitar: `.env`, chaves Clerk, dumps de banco reais, dados pessoais de alunos.
- PR para `main` com descrição curta: o quê, por quê, como testar. Merge só com smoke test ok.
- Não commitar/pushar sem o usuário pedir.

## Banco
- Mudança de schema = **novo arquivo** `database/NN_nome.sql` (próximo número) + montar em `docker-compose.yml`. Nunca editar migração já aplicada (o `database/migrate.php` avisa pelo checksum).
- Banco existente (produção) recebe migrações só pelo `database/migrate.php` (`schema_migrations`); o initdb do Docker só roda na criação do volume.
- Idempotente (`IF NOT EXISTS`). Compatível com Supabase.
- Toda tabela sensível nova (nota, falta, documento) deve gravar trilha de auditoria (ver proposta §05).

## Código
- Seguir o estilo do arquivo vizinho. Controllers finos, SQL nos Models, sempre PDO com parâmetros (sem concatenar input).
- Escapar toda saída em views (`htmlspecialchars`). Validar permissão **no servidor**, nunca só no front.
- RBAC: aluno vê só os próprios dados; docente Anos Iniciais = todas as disciplinas da turma; docente EM = vê tudo da turma, edita só a própria disciplina.
- Rotas `/admin` são só do admin. Docente usa `/portal-docente/*` e toda escrita passa por `App/Models/Teacher/TeacherAccess.php`; negado = 403 + `Audit::log(..., 'NEGADO', ...)`.
- Mobile-first: alvos de toque ≥ 44px; tabela vira card no celular.
- Sem dependência nova sem necessidade. Sem framework JS.

## Segurança
- Manter PHP e imagem base atualizados (`docker compose build --pull`). Rodar `composer audit` ao mexer em dependências.
- Atualizar PHP = **recriar o container**, não só trocar binário/imagem: `docker compose up -d --build --pull always`. Processo antigo continua vulnerável na memória. Sem Docker: reiniciar `apache2`/`php-fpm`. Depois, confirmar a versão **servida** (requisição HTTP com `PHP_VERSION`), não só `php -v` no CLI.
- Em produção: `docker-compose.prod.yml` ([docs/DEPLOY.md](docs/DEPLOY.md)) já liga `PHP_DISPLAY_ERRORS=Off`, OPcache sem revalidar e cookie `secure`; exige `APP_URL` real, Clerk e `APP_SIGNING_KEY`. `DB_SSLMODE=require` quando o banco é externo (Supabase); na VPS o banco fica na rede interna do Docker.
- Arquivo novo gravado pelo sistema precisa de volume no `docker-compose.prod.yml` (a imagem é recriada a cada deploy).
- `APP_SIGNING_KEY` (32+ caracteres, `openssl rand -hex 32`) assina os documentos. Nunca versionar; trocar a chave invalida todos os documentos já emitidos.
- Documento oficial só imprime dado real do banco ou do `.env` (`SCHOOL_*`). Nada de valor padrão inventado (endereço, CNPJ, frequência).
- LGPD: dados de menores são sensíveis. Não logar PII em arquivo; não usar dado real em seed.

## Definição de pronto
Rota funciona · permissão checada no servidor · auditoria gravada (se sensível) · mobile ok · smoke 0 erros · docs/PROPOSTA_ESCOPO.md atualizado.
