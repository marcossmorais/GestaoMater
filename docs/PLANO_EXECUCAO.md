# Plano de execução — Portais Docente e Aluno

Base: [PROPOSTA_ESCOPO.md](PROPOSTA_ESCOPO.md) (checklist) · Regras: [../AGENTS.md](../AGENTS.md).
Prazo da proposta: 7–14 dias. Admin fora desta fase.

## Regras de execução
- Uma branch por fase (`feat/…`), commits atômicos, PR para `main`. Sem push sem pedido.
- Cada fase fecha com: smoke test 0 erros · permissão checada no servidor · auditoria gravada (se sensível) · checklist visual da fase (§ Auditoria visual) · `PROPOSTA_ESCOPO.md` atualizado.
- Migração = novo `database/NN_nome.sql` (próximo: `10_`), montado no `docker-compose.yml`.
- Fases 2–5 dependem da 1. Fase 6 pode andar em paralelo à 4. A Fase 0 (auditoria visual) roda antes e depois de tudo.

## Fase 0 — Auditoria visual base (antes de codar)
Objetivo: lista priorizada de bugs/inconsistências do estado atual, para corrigir junto de cada fase.
Saída: `docs/AUDITORIA_VISUAL.md` (tabela: ID · tela · viewport · problema · gravidade · print · fase que corrige).

Método:
1. Subir app (`docker compose up -d`), logar nos 2 portais com usuários de `USUARIOS_TESTE.md`.
2. Percorrer **toda rota** de `/portal-docente/*` e `/portal-aluno/*` (lista em `App/Route.php`).
3. Para cada tela: viewports **375×812**, **768×1024**, **1366×768**, **1920×1080**; tema claro e noturno; estados vazio, carregando, erro e cheio (300 alunos).
4. Capturar print + console (erros JS/404 de asset) + rede.

Checklist por tela:
- **Tipografia**: fonte grande/pequena demais, títulos sem hierarquia, texto < 14px no mobile, quebra de linha feia, truncamento sem tooltip, mistura de famílias.
- **Cards**: alturas, raios, sombras, paddings e bordas diferentes entre telas; ícones/avatares sem tamanho padrão; grade que quebra em largura intermediária.
- **Animação**: salto de layout (CLS), piscar ao carregar, transição travada, hover sem equivalente no toque, animação que ignora `prefers-reduced-motion`, spinner infinito.
- **Mobile**: rolagem horizontal, alvo de toque < 44px, barra inferior cobrindo conteúdo, teclado cobrindo campo, tabela que não vira card.
- **Contraste/tema**: texto ilegível no noturno, contraste < 4.5:1, estados de foco invisíveis, cores fora da paleta (`#1868E8`, `#0838B8`, `#0878C8`, `#F8A808`).
- **Estados**: vazio sem mensagem, erro sem retry, botão sem feedback de loading, ação destrutiva sem confirmação.
- **Consistência**: botões (primário/secundário), badges, modais, tabelas, formulários e ícones iguais em todas as telas.
- **Acessibilidade**: foco por teclado, `alt`, labels de input, ordem de tab, `lang`.
- **Perf percebida**: imagens sem dimensão, fontes sem `font-display`, assets 404, JS bloqueante.

Gravidade: **P0** quebra uso · **P1** confunde/feio em tela principal · **P2** polimento.

## Fase 1 — Limpeza e auditoria (base)
Branch `feat/auditoria-e-limpeza`.
- **1.1 Remover** rematrícula (`/portal-aluno/rematricular`, `StudentRematrug`, views) e portaria/autorizados do que docente/aluno usam. Antes: `grep` de uso + decisão do que fica no banco (não dropar tabela com dados sem OK).
- **1.2 Auditoria**: migração `10_auditoria.sql` — tabela `auditoria` (id, ator_tipo, ator_id, acao, entidade, entidade_id, antes jsonb, depois jsonb, ip, criado_em); sem UPDATE/DELETE (trigger bloqueia). Helper `App\Tools\Audit::log()`.
- **1.3** Ligar auditoria em: salvar chamada, lançar/alterar nota, abono de falta, download/impressão de declaração e boletim.
- **Pronto quando**: alterar nota gera linha com valor antigo/novo; tentativa de UPDATE na tabela falha; rotas removidas retornam 404.

## Fase 2 — RBAC docente
Branch `feat/rbac-docente`.
- **2.1** Marcar tipo de turma/etapa (Anos Iniciais × EM) e vínculo professor↔disciplina↔turma (migração `11_`).
- **2.2** Policy única no servidor: `canEdit(teacher, turma, disciplina)` / `canView`. Usar em todo controller docente.
- **2.3** Anos Iniciais: checkbox de disciplinas em chamada/observação/atividade. EM: visão geral da turma, edição só da própria disciplina.
- **Pronto quando**: teste manual com 1 polivalente e 1 especialista; POST forjado em disciplina alheia → 403 + linha na auditoria.

## Fase 3 — Diário de aula
Branch `feat/diario-bncc`.
- **3.1** Tabelas `aula` (turma, disciplina, data, professor, habilidade_bncc, observacao) e `aula_atividade` (tipo: teórica, documentário, questionário, revisão, outro).
- **3.2** Tela mobile: habilidade BNCC (texto livre + código opcional), observação, chips de atividade, seleção múltipla de disciplinas (Iniciais).
- **3.3** Troca de professor/matéria na aula: registra substituto e motivo; auditado.
- **3.4** Integrar com chamada rápida (mesma aula).
- **Pronto quando**: aula salva em ≤ 3 toques após a chamada; histórico visível; troca registrada.

## Fase 4 — Mural, avisos e roteiro de provas
Branch `feat/mural-roteiro`.
- **4.1** `aviso` (escopo: docentes/turma/responsáveis, fixado, autor, validade) — docente publica para turma; fixar até 3 por turma (validar no servidor).
- **4.2** Aluno: mural em destaque na home (fixados primeiro), confirmação de leitura.
- **4.3** `roteiro_prova` (avaliação, conteúdo, data) — docente publica; aluno consulta.
- **4.4** Notas facilitadas no mobile (lançamento em lista, teclado numérico, validação de máximo).
- **Pronto quando**: 4º fixado é recusado; aluno só vê avisos da sua turma.

## Fase 5 — Desempenho do aluno
Branch `feat/aluno-desempenho`.
- **5.1** Boletim por matéria com evolução (gráfico simples) e frequência com abonos.
- **5.2** **Simulador de notas**: entrada = notas lançadas + regra de média do colégio (confirmar com a direção); saída = nota necessária por unidade. Lógica em função pura, com testes de tabela.
- **5.3** Cronograma (diário/semanal/mensal/anual) somente leitura — fonte: eventos cadastrados pelo admin (contrato de dados definido aqui; tela admin é outra fase).
- **Pronto quando**: simulador bate com cálculo manual em 5 casos; aluno só vê dados próprios.

## Fase 6 — Documentos com assinatura digital
Branch `feat/documentos-assinados`.
- **6.1** Código de validação único por documento (hash + id) e página pública `/validar/{codigo}` (sem dados sensíveis além de nome/tipo/data).
- **6.2** Declarações e boletim com selo + QR; log de download/impressão na auditoria.
- **6.3** Modelo editável fica no Admin (fora de escopo) — usar modelo padrão fixo.
- **Pronto quando**: código adulterado → inválido; cada download gera linha de auditoria.

## Fase 7 — Polimento visual e fechamento
Branch `fix/visual-final`.
- Reexecutar **Fase 0** completa; zerar P0 e P1, P2 conforme tempo.
- Padronizar: escala tipográfica única, componente de card (raio/sombra/padding), botões, badges, modal, estados vazio/erro/loading.
- Respeitar `prefers-reduced-motion`; modo noturno em todas as telas novas.
- Smoke test `--writes` em banco local; revisão de segurança (`composer audit`, PHP atualizado e container recriado).
- Atualizar README, `PROPOSTA_ESCOPO.md` e `USUARIOS_TESTE.md`.

## Riscos / decisões abertas
- Regra de média/recuperação do MATER (simulador) — perguntar à direção.
- Formato e frequência da importação do sistema local (Admin, depois) — define chaves únicas de aluno/turma.
- `public/node_modules` e `vendor/` versionados — decidir build no Docker para destrackear.
- Hospedagem e certificado externo (fora do escopo comercial) — alinhar antes do aceite.
