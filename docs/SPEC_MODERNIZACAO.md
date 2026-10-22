# Especificação Técnica e Estratégica de Modernização — Mater ERP

> **Documento Executivo de Engenharia e Produto**  
> **Versão:** 1.0 (MVP de Apresentação)  
> **Data:** Outubro de 2026  
> **Status:** MVP Implementado (Branch: `feat/mvp-ux-chamada-rapida`)

---

## 1. Visão Geral e Posicionamento de Mercado

O **Mater** possui uma base de regras de negócio educacional e modelagem relacional de altíssima fidelidade à realidade escolar brasileira (turnos, períodos letivos, unidades, séries, matriz curricular, enturmação e rematrícula).

Enquanto sistemas legados no mercado nacional ainda sofrem com servidores locais, bancos MySQL antigos e autenticações obsoletas, o Mater já roda sobre **PostgreSQL 17**, conteinerização **Docker** e autenticação corporativa moderna via **Clerk**.

### A Dor do Mercado vs. A Solução Mater 2026
* **O problema dos sistemas tradicionais:** Interfaces pesadas, desenhadas apenas para desktops, repletas de formulários extensos e recarregamentos completos de página que transformam rotinas diárias (como fazer chamada) em um fardo burocrático de vários minutos.
* **A proposta Mater 2026:** Uma experiência com sensação de **aplicativo nativo (Superapp)**, fluida, orientada a toques rápidos no smartphone e sem "piscar" a tela, trazendo a agilidade das ferramentas de ponta (ex: Sophia, ClipEscola, Sponte) diretamente para o ecossistema web existente.

---

## 2. O que foi Entregue no MVP de Apresentação (Fase 1)

### 2.1. Chamada Rápida por Carômetro (Economia de 80% do tempo)
Inspirado nas melhores práticas de interfaces educacionais modernas (referência carômetro/Sophia):
1. **Regra de Ouro (Presença Positiva):** Todos os alunos vêm marcados como **"Presente"** por padrão. O docente só interage tocando nos alunos que **faltaram**.
2. **Visual em Grade com Carômetro:**
   * Foto do estudante (ou avatar com iniciais em paleta harmônica).
   * Numeração de chamada ordenada.
   * Indicador circular de faltas acumuladas na unidade com gradiente dinâmico (`verde` -> `amarelo` -> `vermelho`).
   * Selo tátil com alternância instantânea entre `Presente` e `Faltou` com micro-vibração háptica em dispositivos móveis.
3. **Barra Fixa de Ação Instantânea:**
   * Contadores em tempo real: `X presentes` e `Y ausentes`.
   * Seletor de aulas seguidas (1 a 6 aulas com multiplicador automático de faltas).
   * Seletor rápido de unidade letiva aberta.
   * Filtro de busca de alunos por digitação instantânea sem recarga.
4. **Persistência Atômica e Rastreabilidade:**
   * Tabela `chamada` no PostgreSQL para registro histórico com data, horário, professor e turma.
   * Atualização transacional em lote na tabela `falta_aluno`.
   * Aviso amigável caso a chamada daquela turma já tenha sido efetuada no mesmo dia.

### 2.2. Responsividade Mobile com Transformação "Tabela para Cards"
* Nas telas de listagem de turmas (`/portal-docente/turmas`), a tabela tradicional em desktop se converte automaticamente em **cards touch-friendly** no smartphone, eliminando rolagem horizontal e exibindo botões de ação com alvos de toque adequados (mínimo de 44px).

### 2.3. Integração Contínua com Clerk
* E-mail institucional do professor conectado de forma transparente, permitindo autenticação com um clique sem conflitos entre portais.

---

## 3. Roteiro de Modernização (Roadmap 2026/2027)

```
[Fase 1: MVP Chamada & UI Cards]  <-- ENTREGUE HOJE
       │
       ▼
[Fase 2: PWA & Boletim Interativo] (2 a 4 semanas)
       │
       ▼
[Fase 3: Notificações & WhatsApp] (1 a 2 meses)
       │
       ▼
[Fase 4: Financeiro com Pix & IA Pedagógica] (2 a 3 meses)
```

### Fase 2: PWA e Experiência do Estudante (2 a 4 semanas)
* **PWA (Progressive Web App):** Adição de manifesto web e Service Worker com suporte offline básico. O sistema passa a exibir o prompt de instalação *"Instalar App Mater"* no Chrome/Safari sem taxas de app stores.
* **Boletim Gamificado:** Substituição da tabela clássica por cards de progresso por disciplina no Portal do Aluno, com simulador interativo *"Quanto preciso na próxima unidade para passar direto"*.
* **Mural de Avisos com Confirmação de Leitura:** Métricas de visualização para coordenação e docentes.

### Fase 3: Comunicação & Secretaria Digital (1 a 2 meses)
* **Notificações via WhatsApp:** Disparo de avisos imediatos aos pais quando uma falta for registrada ou quando um comunicado for publicado pela coordenação.
* **Rematrícula 100% sem Papel:** Upload de comprovantes e documentos com câmera do celular e assinatura de contrato com token.

### Fase 4: Automação Financeira & Inteligência Artificial (2 a 3 meses)
* **Módulo Financeiro com Pix Dinâmico:** Geração de QR Code e Pix Copia e Cola para mensalidades com conciliação automática via webhook.
* **Assistente Pedagógico com IA:** Sugestão automática de planos de aula e geração de exercícios alinhados às habilidades da BNCC.

---

## 4. Arquitetura Técnica

```
┌────────────────────────────────────────────────────────┐
│                   CLIENT LAYER (UI/UX)                 │
│  - Inter Variable Typography (Self-hosted 46KB WOFF2)  │
│  - Touch-First Reactive Cards & Steppers               │
│  - Dark Mode nativo com variáveis CSS (:root / night)  │
│  - Sem dependência de frameworks pesados (Vanilla JS)  │
└───────────────────────────┬────────────────────────────┘
                            │ Fetch / JSON
┌───────────────────────────▼────────────────────────────┐
│                  APPLICATION LAYER (PHP 8.2)           │
│  - Microframework MVC em Container Docker              │
│  - Clerk Auth (JWT RS256 com validação de JWKS)        │
│  - Controller transacional com tratamento de erros     │
└───────────────────────────┬────────────────────────────┘
                            │ PDO Transacional
┌───────────────────────────▼────────────────────────────┐
│                    DATABASE (PostgreSQL 17)            │
│  - Tabela `chamada` com histórico e auditoria          │
│  - Tabela `falta_aluno` com índices compostos          │
│  - Compatibilidade com Supabase / Cloud Managed DB     │
└────────────────────────────────────────────────────────┘
```

---

## 5. Instruções para Demonstração do MVP

1. Acesse o sistema em: **`http://localhost:8000/portal-docente`** (ou faça login se necessário).
2. Na tela inicial (**Home**), observe o novo banner de destaque **"Chamada rápida"** e o botão no topo.
3. No menu lateral ou na barra inferior no mobile, clique em **"Chamada rápida"** (ou acesse `/portal-docente/chamada`).
4. Selecione uma turma (ex: **1ª A · Língua Portuguesa**).
5. Demonstre o carômetro:
   * Todos começam verdes (**Presentes**).
   * Ao tocar no aluno, ele fica vermelho (**Faltou**) e o contador na barra inferior atualiza em tempo real sem recarregar a tela.
   * Demonstre o campo de busca digitando o nome de um aluno.
   * Alterne a quantidade de aulas no seletor (+/-).
   * Clique em **"Salvar chamada"**: visualização imediata da tela de sucesso com o resumo das presenças e faltas registradas.
