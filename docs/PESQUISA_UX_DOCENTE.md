# Pesquisa de mercado — UX do portal do docente

> Out/2026. O que produtos de gestão pedagógica fazem para facilitar a vida do professor, e o que faz sentido no Mater.
> Escopo: só a parte pedagógica (chamada, diário, notas, acompanhamento do aluno). Nada de financeiro, matrícula ou portaria.

## O que o Mater já tem

Chamada rápida (todos presentes, toca em quem faltou) · diário com habilidade BNCC · notas com 3 avaliações + recuperação por bimestre · roteiro de provas no mural · visão da turma · horário · atestados e ocorrências · trilha de auditoria.
Esses pontos já estão no nível do mercado. A chamada "por exceção" é o padrão dos melhores apps.

## O que os outros fazem

| Produto | Destaque útil para nós |
| --- | --- |
| **Profez** (BR, diário do professor) | Registro da aula **por voz** (30 s) virando registro pedagógico; **alertas automáticos** de queda de rendimento e excesso de faltas; material pronto para conselho de classe e reunião de pais |
| **i-Diário / i-Educar** (Portabilis, software livre, BR) | App do professor com **frequência e conteúdo offline**, sincroniza depois; totalizador de frequência em %; **dispensar aluno de uma avaliação** |
| **Diário de Classe Digital BA / PA** (redes estaduais) | App com modo offline; relatório em PDF por turma e por aluno; menu de conselho de classe |
| **iDoceo** (iPad) | **Mapa de sala**: chamada tocando na foto do aluno; "todos presentes" ao abrir; ícones e cores no lançamento; exportação PDF/CSV |
| **Infinite Campus / PowerSchool** (EUA) | Tela abre **na aula de hoje**, no primeiro horário pendente; chamada pelo mapa de sala; o professor "posta" mesmo com todos presentes, para a secretaria ver quem falta concluir |
| **Canvas SpeedGrader / Google Classroom** | **Banco de comentários** reutilizáveis; atalhos de teclado (próximo aluno, nota, comentário); tudo numa tela só |
| **ClassDojo / Seesaw** | Registro rápido de comportamento (positivo e negativo) com um toque; comentário em áudio para o aluno |
| **Sistemas de alerta precoce** (PowerSchool, PureData etc.) | Modelo **ABC**: frequência, comportamento e notas cruzados para sinalizar o aluno em risco antes do fechamento |
| **Toddle** | Comentários do período montados a partir do que já foi registrado ao longo do bimestre |

Padrões que se repetem: abrir já no que o professor precisa **agora**, registrar por exceção, nunca perder o que foi digitado, avisar **antes** de o aluno ficar abaixo da média e gerar o material do conselho sem retrabalho.

## Propostas para o Mater

Ordenadas por impacto ÷ esforço. P = pequeno (até 1 dia), M = médio, G = grande.

### Agora (P)

1. **Home "Hoje"**: lista das aulas do dia, montada pelo horário já cadastrado, na ordem. Cada aula com o status (chamada ✓, diário ✓) e um botão para o próximo passo. A aula atual fica destacada. Hoje a home lista as turmas, sem ligação com o dia.
2. **Pendências na home**: chamadas sem diário, avaliações criadas sem notas lançadas, recuperações abertas. É o "o que falta eu fazer", que os sistemas americanos mostram para professor e coordenação.
3. **Notas sem perder nada**: rascunho automático no aparelho até salvar e aviso ao sair da tela. Lista na ordem do número de chamada, com a foto do aluno.
4. **"Quanto falta" na tela de notas**: ao lançar a 2ª avaliação, mostrar quanto cada aluno precisa tirar na 3ª para fechar 7. O `GradeSimulator` já faz a conta.

### Próximo (M)

5. **Alerta de aluno em risco**: card "Atenção" na home e na visão da turma quando a média parcial do bimestre está abaixo de 7 ou as faltas passam de um limite. Mostra o motivo, por exemplo "2 notas abaixo de 5" ou "4 faltas em 2 semanas". Precisa de 2 limites configuráveis pela coordenação.
6. **Observação rápida por aluno**: chips de um toque ("participou bem", "não fez a tarefa", "sem material") a partir da chamada ou do diário, além do campo livre. Alimentam o pré-conselho.
7. **Diário por voz**: botão de microfone que dita o conteúdo da aula pela API de voz do navegador. Não precisa de dependência nem de IA, e o professor revisa antes de salvar.
8. **Chamada + diário numa sequência só**: ao salvar a chamada, a mesma tela já pede o conteúdo e a habilidade BNCC daquela aula, com a última aula como sugestão.

### Depois (M/G)

9. **Pré-conselho de classe**: ficha por aluno gerada sozinha (notas por bimestre, faltas, ocorrências, observações rápidas) e um campo de parecer do professor. Sai em PDF para a reunião.
10. **Mapa de sala na chamada**: alternativa à lista, tocando na foto do aluno.
11. **Offline (PWA)**: chamada e diário funcionando sem internet, com fila que sincroniza depois, como no app do i-Diário. É o item de maior esforço, mas resolve sala sem Wi-Fi.

### Não recomendado agora

- Pontos e gamificação (ClassDojo): combina pouco com o Ensino Médio e gera atrito com as famílias.
- IA corrigindo prova ou escrevendo parecer: fora do escopo e sensível (LGPD, dados de menores).

## Fontes

- Profez — https://profez.com.br/diario-de-classe
- i-Diário (Portabilis) — https://github.com/portabilis/i-diario/releases · app offline: https://relatedrepos.com/gh/portabilis/i-diario-app
- Diário de Classe Digital BA — https://enova.educacao.ba.gov.br/wp-content/uploads/2025/07/manual_app_diario_classe_digital.pdf
- Diário SEDUC-PA — https://intranet.seduc.pa.gov.br/intranet/academico/manuais/ACADEMICO%20-%20DIARIO%20-%20Manual%20do%20Diario.pdf
- iDoceo — https://www.idoceo.net/index.php/en/howto/10-instructions/141-attendance-features · https://www.idoceo.net/index.php/en/instructions/seating-plan/the-seating-plan-main-features
- Infinite Campus (UI de chamada e notas) — https://infinitecampus.com/company/news/2017/infinite-campus-announces-major-ui-upgrades-and-enhancements-for-campus-instruction
- PowerTeacher (chamada pelo mapa de sala) — https://www.ednet.ns.ca/inschool/files_inschool/taking_attendance_in_powerteacher_user_guide.pdf
- Canvas SpeedGrader — https://community.canvaslms.com/t5/Canvas-Basics-Guide/What-is-SpeedGrader/ta-p/13 · https://setonhall.atlassian.net/wiki/spaces/PUBLICKBA/pages/2990243855/Tips+for+Using+SpeedGrader
- Google Grader × SpeedGrader — https://cteresources.bc.edu/documentation/assignments-and-grades/get-started-with-google-assignments/creating-grading-a-google-assignment/compare-google-grader-and-canvas-speedgrader/
- ClassDojo — https://www.techlearning.com/how-to/what-is-classdojo · Seesaw — https://www.teachstarter.com/blog/how-to-use-the-seesaw-app-in-the-classroom
- Alerta precoce — https://puredata.io/powerschool-student-at-risk-dashboard/ · https://www.solvedconsulting.com/blog/ai-early-warning-dashboards-at-risk-students
- Toddle — https://g2.com/products/toddle/reviews
- Conselho de classe digital (UFRGS/RENOTE) — https://seer.ufrgs.br/index.php/renote/article/view/85899
- Offline-first (Attendify) — https://github.com/deepu2826/Attendify

## Status (31/10/2026)

Feito: home "Hoje" + pendências + alunos em atenção · notas com rascunho, média ao vivo e "quanto falta" · ficha do aluno com observações rápidas · chamada → diário em sequência · busca BNCC · app instalável.
Fora por decisão: diário por voz (depois), pré-conselho (a ficha do aluno vai seguir o padrão da escola), mapa de sala (a chamada já tem fotos), offline completo (o app instalável cobre o uso).

## Sistema visual (portais docente e aluno)

Regras em `public/assets/css/mater.css`, seção "Visual v2".

| Decisão | Regra | Por quê |
| --- | --- | --- |
| Cantos | 16px cartões · 12px campos, botões e itens · 8px etiquetas · pílula só em selos | Escala única; elemento interno com canto menor que o externo (regra dos cantos aninhados) |
| Cor | Sólida, sem gradiente; azul só para ação e estado | Gradiente decorativo compete com o conteúdo |
| Barra lateral | Item ativo = barra branca à esquerda + texto forte; hover diferente do ativo | Dois sinais (não só cor) e ativo distinto do hover |
| Seleção | Poucas opções → botões/abas visíveis; select só para lista longa, com 44px e seta própria | GOV.UK: select é a última opção |
| Toque | Alvos de 44px; cards de turma em linha no celular | Mobile-first (AGENTS.md) |
| Painel | Cada bloco leva a uma ação (fazer chamada, lançar nota, abrir aluno) | Painel bom é acionável, não só informativo |

Fontes de design: regra dos cantos aninhados — https://hightouch-ui.vercel.hightouch.com/foundation/border-radius · escala de cantos — https://developer.incode.com/design-and-ux/shape/ · select × opções visíveis — https://designsystem.parliament.uk/forms/select/ · item ativo na navegação — https://www.saasui.design/blog/saas-sidebar-navigation-ux-patterns · app instalável — https://web.dev/articles/install-criteria
