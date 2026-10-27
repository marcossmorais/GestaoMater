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
- [x] Ensino Médio: edição restrita à própria disciplina · [ ] tela de visão geral da turma (`TeacherAccess::canViewClass` pronto; tela com notas na Fase 4)
- [x] **Diário** (`/portal-docente/diario`, `database/12_diario_aula.sql`): habilidade BNCC (texto + código validado) + observação, vinculado à chamada do dia
- [x] **Atividades da aula**: teórica, documentário, questionário, revisão, prática, avaliação, outro
- [x] **Troca de professor/matéria** na aula: substituto e/ou matéria dada, motivo obrigatório, auditado
- [ ] **Lançamento de notas** facilitado (mobile)
- [ ] **Roteiro de provas**: publicar conteúdo de avaliação + desempenho por aluno
- [ ] **Quadro de avisos da turma**, fixar até 3
- [ ] Ocorrências disciplinares (lançar) com controle de ciência

## 3. Portal Aluno / Responsável
- [ ] **Mural em destaque** na entrada (até 3 fixados + demais)
- [x] Boletim · [ ] desempenho/evolução por matéria
- [ ] Frequência: faltas e abonos transparentes
- [ ] **Roteiro de provas** + **cronograma** (anual/mensal/semanal/diário)
- [ ] **Simulador de notas** (quanto tirar para atingir a média)
- [x] Ocorrências com ciência
- [x] Declarações (matrícula, frequência) · [ ] **assinatura digital** com validação (QR/código) + log de download
- [ ] Boletim com assinatura digital

## 4. Ordem sugerida
1. Remoções + auditoria (base de tudo)
2. RBAC polivalente/EM
3. Diário BNCC + atividades + troca de professor
4. Mural/avisos (docente publica, aluno vê) + roteiro de provas
5. Simulador, desempenho, cronograma
6. Assinatura digital de documentos
