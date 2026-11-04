---
name: themerchant-ui-backlog
description: Backlog vivo de tudo o que falta ajustar na interface do TheMerchant (usabilidade, acessibilidade, design, responsividade, desempenho do front), com status calculado automaticamente a partir do código. Use sempre que for mexer em views Blade, CSS, layout, componentes ou formulários do TheMerchant, e sempre que o usuário perguntar o que falta na interface, pedir "o próximo ajuste de UI", "melhorar o design", "corrigir acessibilidade", "deixar o site mais bonito/usável", revisar telas antes da apresentação (issue #25) ou quiser saber o progresso dos ajustes visuais, mesmo que não cite o backlog.
---

# Backlog de interface do TheMerchant

Esta skill guarda a lista completa de ajustes que saiu da análise de UI/UX das 41 telas (feita com a skill ui-ux-pro-max em 04/11/2026: código, navegador em 375px e desktop, medições de contraste, fonte e área de toque). Cada item tem uma **verificação automática** em `scripts/audit.mjs`, que lê o código e decide se o item está feito. Por isso a lista se atualiza sozinha: ninguém marca nada à mão.

- `BACKLOG.md`: a lista gerada, agrupada por prioridade, com o porquê, como corrigir e onde está cada ocorrência (`arquivo:linha`).
- `scripts/audit.mjs`: o catálogo de itens (`ITEMS`) e as verificações.
- `status.json`: o último status de cada item, usado para avisar o que mudou.

## Como a lista se mantém atualizada

1. **Hook do Claude Code**: depois de cada `Edit`/`Write` em views, CSS, JS, rotas web ou providers, o hook em `.claude/settings.json` roda o script. Quando um item muda de status, o hook avisa na conversa (ex.: `A11Y-07 pendente → feito`).
2. **Ao usar esta skill**: rode o script antes de ler o backlog. Assim, alterações feitas fora do Claude (por você ou pelo Igor no editor) também entram, porque o status vem do código e não de uma marcação.

```bash
node .claude/skills/themerchant-ui-backlog/scripts/audit.mjs
```

`--check` só imprime o resumo no terminal, sem gravar.

## Fluxo de trabalho

1. Rode o script e leia `BACKLOG.md`.
2. Escolha pela prioridade (Crítica → Alta → Média → Baixa) e agrupe itens que mexem nos mesmos arquivos, para uma única revisão visual.
3. Siga os padrões do projeto antes de inventar algo novo:
   - `AGENTS.md` e `docs/PADRAO_VISUAL.md` (layout mestre, tokens do `theme.css`, painéis `bg-slate-900`, alvos de 44px);
   - componentes existentes: `x-page-heading`, `x-empty-state`, `x-icon` (Lucide), `x-field-error`, `x-badge`, `errors.layout`;
   - para decisões de design mais finas, a skill `ui-ux-pro-max` (busque a regra citada no item, ex.: `"error placement" --domain ux`).
4. Depois de corrigir:
   - rode o script de novo e confirme que o item virou "feito";
   - rode os testes: `php artisan test` (no PowerShell, o PHP do Herd não está no Git Bash) e `npm test`;
   - confira no navegador em 375px e no desktop (configuração `themerchant` do `.claude/launch.json`).
5. Diga ao usuário quais itens mudaram de status e o que ainda falta na mesma prioridade.

Itens que mudam o visual do site inteiro precisam do aval do usuário antes de começar, porque afetam todas as telas e o trabalho do Igor: **PERF-01** (migrar o Tailwind do CDN para o Vite), **NAV-01** (novo cabeçalho no celular) e **UI-02** (nova escala de verdes). Proponha a abordagem, mostre o impacto e espere a resposta.

## Mantendo o catálogo

- **Não edite `BACKLOG.md` à mão**: ele é reescrito a cada execução.
- **Item feito continua no catálogo**: ele vira proteção contra regressão. Se alguém desfizer a correção, o item volta para "em aberto".
- **Verificação errada se corrige no script**: se um item aponta algo que não é problema (falso positivo) ou deixa passar um problema real, ajuste o `check` desse item e rode de novo. Não ignore o item nem o remova para "zerar" a lista.
- **Problema novo vira item novo**: acrescente em `ITEMS` com `id` sequencial da área (`BUG`, `A11Y`, `PERF`, `NAV`, `UI`), `prioridade`, `regra` (id da regra da ui-ux-pro-max), `porque`, `como` e um `check` que leia o código. A verificação deve ser determinística: buscar padrões, contar ocorrências, conferir se um arquivo existe. Antes de confiar nela, teste nos dois sentidos (deve acusar o problema no código atual e passar depois da correção).
- Use os helpers do script: `find` (padrão em arquivos, ignora comentários), `tagPattern` + `{ mask: true }` (tags HTML com Blade nos atributos), `codeLines`, `lineOf`, `byHits`.

## Git

O backlog e o script ficam em `.claude/skills/`. Para a lista valer para a equipe toda, eles precisam ser commitados. Siga a skill `themerchant-git-workflow` em qualquer commit, push ou PR.
