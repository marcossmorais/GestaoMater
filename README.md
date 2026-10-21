# Mater — Sistema de Gestão Escolar

Aplicação web de gestão escolar com 3 portais:

- **Admin** (gestores): cursos, turmas, disciplinas, docentes, alunos, períodos letivos, rematrícula
- **Docente**: avaliações, notas, faltas, avisos da turma
- **Aluno**: boletim, notas, faltas, avisos, rematrícula

Stack atual: PHP 8.2 (MVC próprio em `vendor/MF`), MariaDB/MySQL, Bootstrap + jQuery.

## Rodando localmente (Docker)

```bash
cp .env.example .env
docker compose up -d --build
```

- App: http://localhost:8000
- Banco: `localhost:3307` (usuário/senha/banco em `.env`, padrão `mater`/`mater`/`mater`)

Na primeira subida o banco é criado automaticamente a partir de `database_creation_script.sql` e `initial_data_insertion_script.sql`.

Acesso inicial ao portal do admin (`/admin`):

| Usuário | Código de acesso |
| ------ | ------ |
| Usuário Padrão | 867532 |

Para recriar o banco do zero:

```bash
docker compose down -v && docker compose up -d
```

## Roadmap

- [ ] Autenticação com Clerk (substituir login por nome + código de acesso)
- [ ] Migração do banco para Supabase (Postgres)

## Créditos

Baseado no projeto de TCC de João Pedro Rodrigues Souza e Matheus de Souza Barbosa (CETEPI-I, Paulo Afonso-BA, 2021).
