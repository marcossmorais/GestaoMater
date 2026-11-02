# Escopo da proposta v2.0 — Portais Docente e Aluno

Fonte: *Proposta Comercial — Gestão Pedagógica MATER* (22/10/2026, válida até 31/12/2026).
Admin (importador com diff, mural/cronograma, documentos em lote, painel de logs): exigências da direção na seção 5, uma issue por item.
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
- [x] **Calendário e planejamento** (`database/19_planejamento_tipos_avaliacao.sql`): diário por mês ou semana com o que foi dado em cada dia; plano de aula para datas futuras (título, BNCC, objetivos, desenvolvimento, atividades, recursos, como avaliar, tarefa), que pode ser copiado para outra turma ou data e preenche o registro da aula
- [x] **Portal do aluno, ajustes**: aba Horários separada da Agenda; mural com card lateral de notas e de faltas e observações; boletim com linha expansível (AV1, AV2, AV3, recuperação e nota de cada bimestre); comparação com a turma em gráfico vertical; cards lisos, com tracejado só em estado vazio
- [x] **Portal do aluno, abas**: Desempenho só com a comparação com a turma (o detalhe fica no Boletim); Agenda removida; **Horários** em grade semanal com o quadro do admin, para imprimir; **Roteiro de provas** em folha A4 por bimestre (data, matéria, assuntos e material por avaliação), com filtro por avaliação e impressão
- [x] **Frequência do aluno**: presença por matéria, dias de aula perdidos e envio de atestado ou justificativa; o admin aceita (abona as faltas com data) ou recusa em `/admin/pedagogico`. Anexos fora de `public/`, entregues só com permissão
- [x] **Boletim do aluno**: nota de cada avaliação e simuladores por matéria. Aba Turma com colegas e professores (e-mail)
- [x] **Admin no padrão dos portais**: mesmo menu lateral e escala de texto e botões
- [x] **Mural com área geral e tipos de aviso**: a coordenação publica em `/admin/mural`, para a escola ou uma turma, acima dos avisos separados por matéria. Cor e ícone por tipo (aviso, prova, tarefa, material, evento, urgente); fixados em destaque
- [x] **Portal do aluno**: comparação com a média da turma dentro de Desempenho; aba Turma só com nome e foto dos colegas; mural sem a coluna lateral de avaliações
- [x] **Planejamento e Horário na mesma tela** (`/portal-docente/diario`; `/portal-docente/horario` só redireciona): calendário com tudo visível em cada dia e cores por situação, lista do dia com uma ação clara por aula e tema direto na lista
- [x] **Roteiro de Provas em aba própria** (`/portal-docente/roteiros`): bimestre, avaliação, tipos, assuntos e material; ao salvar, o admin recebe em `/admin/pedagogico` e o aluno vê no mural
- [x] **Índice pedagógico do aluno** (0 a 100, só para professores): notas 40%, frequência 30%, comportamento 30%, no resumo e nas observações. Observações por mês e dia
- [x] **Solicitação de ocorrência**: o professor pede pelo perfil do aluno; o admin aceita (vira ocorrência) ou recusa em `/admin/pedagogico`
- [x] **Turmas e chamada na mesma tela**: abre direto na chamada de uma turma, com filtro na barra superior e as abas Chamada, Alunos e Notas da turma (`/portal-docente/chamada` só redireciona). Notas da turma em grade legível: cabeçalho e nomes fixos, média da turma, contagem de notas abaixo da média, busca e filtro. Carômetro: fotos dos alunos, faltas do bimestre, observação sem sair da tela e clique para o perfil. Perfil em abas (resumo, notas, observações), com a nota de cada avaliação por bimestre em todas as disciplinas
- [x] **Observação rápida marcável** (uma ou mais opções + texto) · **Quem mais está faltando** na home · **Tipos de avaliação** (prova, trabalho apresentado etc., um ou mais) no lançamento de notas, que o aluno vê no roteiro de provas e no cronograma
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
- [x] **Visual educacional** (`edu.css`): professor lúdico na medida, aluno lúdico completo, cor e ícone por matéria, fonte Nunito; página inicial nova em `/` e login com a cor do portal
- [x] **Home do docente com gráficos** (situação dos alunos, faixas de nota, média, atenção e faltas por turma) e atividade dos alunos (leitura dos avisos)
- [x] **Boletim do aluno na regra do MATER** (bimestres de 0 a 10, média do ano); **simulador do bimestre** por avaliação; **aba Turma** só com a média da turma, sem dados de colegas
- [x] **Roteiro de estudo estruturado** (assuntos, material, observações; `database/18_roteiro_estruturado.sql`); juntar no Admin: issue #13
- [x] **Horário** em semana/dia e **perfil** refeitos; diário e observações separados por dia
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

## 5. Portal Admin (exigências da direção)
- [x] **Observabilidade 360°** (#18): `/admin/observabilidade` por turma, disciplina e professor, com filtro de período, bimestre e turno; detalhe da turma com alunos em risco (nota abaixo da média ou frequência abaixo da mínima) abrindo o perfil do aluno; chamadas registradas × aulas previstas pela grade, notas pendentes, avaliações a criar e substituições por professor
- [x] **Importação CSV/JSON** (#19): `/admin/importacao` para alunos (com matrícula na turma), professores e turmas; prévia linha a linha (erro, repetido no arquivo, CPF já cadastrado) antes de gravar; gravação tudo ou nada com resumo na auditoria; planilha-modelo e CSV dos códigos de acesso gerados. Importação SQL não incluída (risco de segurança)
- [x] **Virada de ano** (#19): copia turmas com disciplinas e professores para o novo período e rematricula em lote (aprovado sobe, reprovado repete, última série conclui), com prévia e confirmação única
- [ ] **Tela de auditoria** e registro de acessos (#20)
- [ ] **Cronograma escolar** editável com Notificar no Mural (#21)
- [ ] **Avisos segmentados** para professores, turmas e responsáveis (#22)
- [ ] **Ocorrências**: painel de ciência pendente (#23)
- [ ] **Documentação**: modelo editável, lote e log de downloads (#24)
- [ ] **Gestão de exceções**: troca de professor ou matéria na aula (#25)
