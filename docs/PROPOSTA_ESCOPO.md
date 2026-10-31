# Escopo da proposta v2.0 — Portais Docente e Aluno

Fonte: *Proposta Comercial — Gestão Pedagógica MATER* (22/10/2026, válida até 31/12/2026).
Admin (importador com diff, mural/cronograma, documentos em lote, painel de logs) fica **fora desta fase**.
Legenda: `[x]` existe · `[ ]` falta · `[-]` remover.

## 0. Remoção (fora de escopo)
- [x] Rematrícula removida do portal do aluno (admin e tabelas mantidos)
- [x] Portaria removida do portal do aluno (admin e tabelas mantidos)

## 1. Transversal
- [x] **Trilha de auditoria** imutável (`database/10_auditoria.sql`, `App/Tools/Audit.php`): triggers em notas, faltas, chamadas, atestados e ocorrências; download de declarações pelo aluno
- [x] **RBAC docente** no servidor (`curso.etapa`, `App/Models/Teacher/TeacherAccess.php`): Anos Iniciais edita todas as disciplinas da turma; Finais/EM só a própria. Negado = 403 + linha `NEGADO` na auditoria
- [x] Auth Clerk · [x] Responsivo mobile · [ ] Modo noturno

## 2. Portal Docente
- [x] Chamada rápida por carômetro
- [x] Grade de horários
- [x] Anos Iniciais: seleção de disciplinas na chamada e no diário
- [x] Ensino Médio: edição restrita à própria disciplina + visão geral da turma (`/portal-docente/turma?id=`, pontos por disciplina na unidade)
- [x] Docente não acessa mais rotas `/admin` (antes podia chamar toda a gestão)
- [x] **Diário** (`/portal-docente/diario`, `database/12_diario_aula.sql`): habilidade BNCC (texto + código validado) + observação, vinculado à chamada do dia
- [x] **Atividades da aula**: teórica, documentário, questionário, revisão, prática, avaliação, outro
- [x] **Troca de professor/matéria** na aula: substituto e/ou matéria dada, motivo obrigatório, auditado
- [x] **Lançamento de notas** facilitado (`/portal-docente/notas`, por bimestre, vagas fixas "1ª, 2ª e 3ª avaliação" + "Recuperação" (liberada após as 3, só para quem fechou abaixo de 7)): lista por aluno, teclado numérico, Enter avança, validação de valor máximo, auditado
- [x] **Roteiro de provas**: cada avaliação (0 a 10) pode ter roteiro + desempenho na visão geral da turma
- [x] **Mural da turma** (`/portal-docente/mural`): publicar por disciplina, fixar até 3 por turma (trigger no banco), excluir, contagem de leituras
- [ ] Ocorrências disciplinares (lançar) com controle de ciência

## 3. Portal Aluno / Responsável
- [x] **Mural em destaque** na home (fixados primeiro) com confirmação de leitura
- [x] Boletim · [x] desempenho/evolução por matéria (pontos por unidade, aba Desempenho)
- [x] Frequência: faltas, abonos e % por disciplina (aulas dadas = chamadas registradas)
- [x] **cronograma** dia/semana/mês/ano (`evento_escolar` + avaliações da turma; cadastro de eventos fica no Admin)
- [x] **Home do docente "Hoje"**: aulas do dia pelo horário com status de chamada/diário e próximo passo; pendências (diário sem registro, notas faltando); alunos em atenção (média abaixo de 7, recuperação, 5+ faltas no bimestre)
- [x] **Ficha do aluno para o docente** (`/portal-docente/aluno`): todas as disciplinas só leitura, a do professor com cada avaliação; observações rápidas de um toque (`database/17_observacao_rapida.sql`, auditadas)
- [x] **Chamada → diário em sequência**; **busca de habilidade BNCC** por palavra ou código, filtrada pela série/disciplina (`App/Data/bncc-habilidades.tsv`, dados bncc.dev CC BY 4.0)
- [x] **Notas**: rascunho automático no aparelho, média ao vivo e "precisa de X para fechar 7"
- [x] **App instalável** (manifest + service worker que só guarda arquivos estáticos; em produção exige HTTPS)
- [x] **Simulador de notas** (`App/Tools/GradeSimulator.php`, testes em `tests/`). **Regra do MATER (confirmada):** 4 bimestres · 3 avaliações de 0 a 10 · nota do bimestre = média aritmética · abaixo de 7 tem recuperação e vale a **maior** nota (bimestre ou recuperação) · média do ano = **soma dos 4 bimestres ÷ 4**, precisa de 7 · frequência não entra na nota (`database/16_regra_bimestral.sql`)
- [x] Ocorrências com ciência
- [x] Declarações (matrícula, frequência) · [x] **assinatura eletrônica** HMAC-SHA256 com código + QR e validação pública em `/validar` (detecta adulteração e documento cancelado) + log de download. Frequência agora vem das chamadas (antes era inventada) e dados da escola vêm do `.env`
- [x] Boletim com validação (`/portal-aluno/boletim/documento`)

## 4. Ordem sugerida
1. Remoções + auditoria (base de tudo)
2. RBAC polivalente/EM
3. Diário BNCC + atividades + troca de professor
4. Mural/avisos (docente publica, aluno vê) + roteiro de provas
5. Simulador, desempenho, cronograma
6. Assinatura digital de documentos
