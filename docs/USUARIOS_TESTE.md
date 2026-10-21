# Usuários de teste — Mater

> Dados **fictícios**, gerados por `database/dev/generate_dev_seed.py` e carregados só no banco local (Docker).
> Escola: 1 curso (Ensino Médio), 9 turmas (1ª a 3ª série, A/B/C), 12 disciplinas, 14 professores e 300 alunos,
> com notas e faltas lançadas na 1ª unidade.

## Como entrar

**Com o Clerk ativo** (chaves no `.env`):

1. Abra o portal (`/admin`, `/portal-docente` ou `/portal-aluno`).
2. No primeiro acesso clique em **Sign up** e use o e-mail da tabela. Senha: a que quiser (sugestão `Mater@Teste2026`).
3. O Clerk pede um código de verificação: use **`424242`**. E-mails com `+clerk_test` não recebem e-mail de verdade.
4. Nos próximos acessos, só **Sign in** com e-mail e senha.

**Sem o Clerk** (`CLERK_*` vazios no `.env`): login por **nome completo + código de acesso**.

O usuário precisa entrar pelo portal certo: e-mail de professor só funciona em `/portal-docente`, de aluno em `/portal-aluno`.

## Admin

| Nome | E-mail (Clerk) | Código (sem Clerk) |
| --- | --- | --- |
| Usuário Padrão | `admin+clerk_test@example.com` | 867532 |

## Professores (14)

| Nome | Disciplina | E-mail (Clerk) | Código (sem Clerk) |
| --- | --- | --- | --- |
| Pedro Gonçalves Alves | Língua Portuguesa | `pedro.alves01+clerk_test@example.com` | 334130 |
| Nicolas Ramos Rocha | Língua Portuguesa | `nicolas.rocha02+clerk_test@example.com` | 320436 |
| Helena Andrade Freitas | Matemática | `helena.freitas03+clerk_test@example.com` | 542727 |
| Bruno Almeida Lima | Matemática | `bruno.lima04+clerk_test@example.com` | 957873 |
| Giovanna Barbosa Araújo | História | `giovanna.araujo05+clerk_test@example.com` | 938653 |
| Samuel Ramos Freitas | Geografia | `samuel.freitas06+clerk_test@example.com` | 636102 |
| Laura Fernandes Ramos | Física | `laura.ramos07+clerk_test@example.com` | 488108 |
| Leonardo Teixeira Fernandes | Química | `leonardo.fernandes08+clerk_test@example.com` | 800949 |
| Laura Marques Costa | Biologia | `laura.costa09+clerk_test@example.com` | 241920 |
| Miguel Gonçalves Cardoso | Língua Inglesa | `miguel.cardoso10+clerk_test@example.com` | 443681 |
| Camila Almeida Rodrigues | Educação Física | `camila.rodrigues11+clerk_test@example.com` | 673942 |
| Davi Ribeiro Moreira | Artes | `davi.moreira12+clerk_test@example.com` | 386058 |
| Gabriela Cardoso Mendes | Filosofia | `gabriela.mendes13+clerk_test@example.com` | 225939 |
| Sarah Costa Nascimento | Sociologia | `sarah.nascimento14+clerk_test@example.com` | 689302 |

## Alunos (amostra — 1 por turma; os 300 estão no banco)

| Nome | Turma | E-mail (Clerk) | Código (sem Clerk) |
| --- | --- | --- | --- |
| Heloísa Alves Souza | 1ª A - Matutino | `heloisa.souza001+clerk_test@example.com` | 895987 |
| Arthur Alves Lopes | 1ª B - Matutino | `arthur.lopes002+clerk_test@example.com` | 124093 |
| Eduardo Andrade Gonçalves | 1ª C - Vespertino | `eduardo.goncalves003+clerk_test@example.com` | 814503 |
| Cecília Araújo Souza | 2ª A - Matutino | `cecilia.souza004+clerk_test@example.com` | 856734 |
| Giovanna Almeida Lima | 2ª B - Matutino | `giovanna.lima005+clerk_test@example.com` | 292379 |
| Nicolas Ribeiro Araújo | 2ª C - Vespertino | `nicolas.araujo006+clerk_test@example.com` | 139855 |
| Felipe Fernandes Dias | 3ª A - Matutino | `felipe.dias007+clerk_test@example.com` | 310742 |
| Gael Barbosa Andrade | 3ª B - Matutino | `gael.andrade008+clerk_test@example.com` | 673282 |
| Isabela Araújo Soares | 3ª C - Vespertino | `isabela.soares009+clerk_test@example.com` | 425498 |

Para ver todos os alunos:

```bash
docker exec mater-db psql -U mater -d mater -c "SELECT nome_aluno, email_aluno, codigo_acesso FROM aluno ORDER BY nome_aluno"
```
