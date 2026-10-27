# Auditoria visual — Portais Docente e Aluno

Data: 27/10/2026 · Fase 7 do [PLANO_EXECUCAO.md](PLANO_EXECUCAO.md) · branch `fix/visual-final`.

## Método
- Todas as rotas de `/portal-docente/*` e todas as abas do `/portal-aluno/home`, em **375px** e **1366px**, tema claro e escuro.
- Varredura automática por página (iframe com a largura alvo): rolagem horizontal, alvos de toque < 40px, texto < 11px, texto gigante, imagem sem `alt`, campo sem rótulo.
- Revisão manual por prints (cards, alinhamento, modo escuro) e console do navegador.
- Gravidade: **P0** quebra uso · **P1** confunde/feio em tela principal · **P2** polimento.

## Resultado

| ID | Tela | Viewport | Problema | Grav. | Situação |
|---|---|---|---|---|---|
| V01 | Todas (docente e aluno) | 375 | 70 regras do CSS antigo com fonte em `vw`: botões com **4px** no celular e textos de 20px+ em telas grandes | P0 | Corrigido: `clamp(13px, Xvw, 20px)` no CSS e no SCSS |
| V02 | Barra inferior (todas) | 375 | Contêiner do toast invisível por cima da barra **engolia os toques** nos ícones da direita | P0 | Corrigido: toast oculto sem `pointer-events` |
| V03 | Aluno · Agenda/Horários | 375 | Página com 624px de largura (abas de dia empurravam o layout) | P1 | Corrigido: seção contida, abas rolam dentro do bloco |
| V04 | Docente · barra inferior | 375 | 8 ícones apertados (alvos de 34–39px) | P1 | Corrigido: Home, Chamada, Diário, Notas e **Mais** (Turmas, Mural, Horários, Perfil, Sair) |
| V05 | Docente · Home | 1366 / escuro | Atalho "Chamada rápida" empilhado e sem cor no modo escuro | P1 | Corrigido |
| V06 | Docente · Home | 1366 | Buraco à direita da agenda depois de remover "Atividades recentes" | P1 | Corrigido: painel único ocupa a largura toda |
| V07 | Docente · todas | — | Script do gráfico rodando em telas sem gráfico (erros no console) | P1 | Corrigido |
| V08 | Aluno · Boletim (legado) | — | "Baixar boletim" quebrava com pop-up bloqueado | P1 | Corrigido: aviso ao usuário; boletim com validação é o caminho principal |
| V09 | Docente/aluno | 375 | Botões, selects e links de cabeçalho com 18–38px de altura | P1 | Corrigido: mínimo de 44px no celular |
| V10 | Aluno · Mural | 375 | Selos com 10,5px | P2 | Corrigido: selos ≥ 12px |
| V11 | Aluno · Mural | 1366 | Mural mais largo que o feed logo abaixo | P2 | Corrigido: alinhado ao feed |
| V12 | Todos | — | Sem foco visível por teclado; animações ignoravam `prefers-reduced-motion` | P2 | Corrigido |
| V13 | Campos de formulário | 375 (iOS) | Campos < 16px causam zoom automático no iPhone | P2 | Corrigido: 16px no celular |
| V14 | Aluno · "Atividades agendadas" (legado) | 1366 | Cards antigos com estilo diferente dos novos | P2 | Pendente |
| V15 | Declaração | 375 | Documento A4 fixo rola para o lado no celular | P2 | Aceito (documento para impressão) |
| V16 | Seed de dev | — | Avisos de exemplo com hora em UTC (3h adiantados) | P2 | Pendente (só dados fictícios) |

## Depois das correções (375px, docente)
| Tela | Rolagem lateral | Alvos < 40px | Texto < 11px |
|---|---|---|---|
| Antes (média das 8 telas) | 0 | 8–15 por tela | até 16 por tela (inclui 4px) |
| Depois | 0 | 0–1 por tela | 0 |

Aluno: 0px de rolagem lateral em todas as abas (antes: Agenda com 249px a mais).
