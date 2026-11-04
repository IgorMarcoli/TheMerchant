# Backlog de interface do TheMerchant

> Gerado por `scripts/audit.mjs` em 2026-11-04 09:29. Não edite à mão: o status de cada item vem de uma verificação no código.
> Para mudar um item ou acrescentar outro, edite o catálogo `ITEMS` no script.

**10 de 47 itens feitos.** 37 em aberto.

| Prioridade | Feitos | Em aberto |
| :--- | ---: | ---: |
| Crítica | 10 | 1 |
| Alta | 0 | 13 |
| Média | 0 | 21 |
| Baixa | 0 | 2 |

## Em aberto

### Crítica

- [ ] **A11Y-03 · Erros de validação junto ao campo e ligados por aria-describedby** — 7 ocorrências
  - Por quê: Mensagem só no topo, ou sem id, não é lida quando o usuário volta ao campo inválido. (regra `error-placement`)
  - Como corrigir: Use <x-field-error name="campo" id="campo-error" /> e no input @error('campo') aria-invalid="true" aria-describedby="campo-error" @enderror.
  - Onde: `resources/views/admin/categories/index.blade.php:41`, `resources/views/admin/categories/index.blade.php:56`, `resources/views/admin/categories/index.blade.php:67`, `resources/views/listings/show.blade.php:169`, `resources/views/listings/show.blade.php:174`, `resources/views/admin/users/index.blade.php:7`, `resources/views/seller/application.blade.php:7`

### Alta

- [ ] **BUG-06 · Páginas 500 e 503 próprias, sem depender do layout** — faltam 500, 503 autônomas
  - Por quê: Se o erro vier do próprio layout (cabeçalho, sessão, banco), uma página 500 que o estende também falha. (regra `consistency`)
  - Como corrigir: Crie errors/500.blade.php e errors/503.blade.php como HTML autônomo (estilo inline, sem @extends).
  - Onde: `resources/views/errors/500.blade.php`, `resources/views/errors/503.blade.php`
- [ ] **BUG-07 · 404 de endereço inexistente mostra o usuário logado** — sem Route::fallback
  - Por quê: Sem rota correspondente o Laravel não inicia a sessão, e o cabeçalho aparece como se o usuário tivesse saído. (regra `consistency`)
  - Como corrigir: Adicione Route::fallback(fn () => abort(404)) no fim de routes/web.php (grupo web).
  - Onde: `routes/web.php`
- [ ] **BUG-09 · Status com rótulos em português, não o valor cru do banco** — 15 ocorrências
  - Por quê: Valores como em_andamento ou aguardando_pagamento aparecem com sublinhado e em caixa alta; cada tela formata de um jeito. (regra `consistency`)
  - Como corrigir: Crie um componente <x-status-badge :status="..." /> com um mapa de rótulos e cores e use-o em pedidos, vendas e admin.
  - Onde: `resources/views/admin/reports/index.blade.php:35`, `resources/views/admin/reports/index.blade.php:82`, `resources/views/admin/reports/index.blade.php:82`, `resources/views/checkout/cancel.blade.php:14`, `resources/views/checkout/cancel.blade.php:24`, `resources/views/checkout/success.blade.php:42`, `resources/views/listings/show.blade.php:95`, `resources/views/orders/show.blade.php:20` e mais 7
- [ ] **BUG-10 · Linha do tempo do pedido: etapa 1 não fica "Pendente" depois de pago** — texto fixo "Pendente"
  - Por quê: O subtítulo da etapa "Pedido Criado" é fixo e contradiz o pagamento confirmado. (regra `consistency`)
  - Como corrigir: Troque o texto fixo por um condicional ("Registrado" quando houver pagamento).
  - Onde: `resources/views/orders/show.blade.php:71`
- [ ] **A11Y-06 · Áreas de toque com pelo menos 44×44px** — 9 alvos pequenos
  - Por quê: O padrão do projeto (docs/PADRAO_VISUAL.md) pede 44px; botões de 40px e links de voltar de 16px de altura são difíceis de tocar. (regra `touch-target-size`)
  - Como corrigir: Use min-h-11 min-w-11 (ou p-3) nos botões de ícone e nos links "← Voltar".
  - Onde: `resources/views/listings/show.blade.php:42`, `resources/views/listings/show.blade.php:43`, `resources/views/admin/categories/index.blade.php:11`, `resources/views/cart/index.blade.php:131`, `resources/views/listings/show.blade.php:16`, `resources/views/orders/show.blade.php:9`, `resources/views/seller/listings/create.blade.php:7`, `resources/views/seller/listings/edit.blade.php:7` e mais 1
- [ ] **A11Y-07 · Envios críticos mostram carregamento e bloqueiam clique duplo** — 2 formulários
  - Por quê: "Confirmar pedido e pagar" e "Adicionar ao carrinho" não dão retorno ao clicar e aceitam um segundo clique. (regra `loading-buttons`)
  - Como corrigir: Envolva o form em x-data="{ enviando: false }", use @submit="enviando = true", :disabled="enviando", :aria-busy e troque o texto do botão.
  - Onde: `resources/views/checkout/index.blade.php:55`, `resources/views/listings/show.blade.php:85`
- [ ] **A11Y-08 · Campos de formulário com 16px no celular** — 31 pontos
  - Por quê: Abaixo de 16px o iPhone dá zoom ao tocar no campo. A regra base está em 14px e vários campos usam text-xs (12px). (regra `readable-font-size`)
  - Como corrigir: Em public/css/storefront.css, dentro de @media (max-width:767px), force font-size:16px !important em input, select e textarea do .tm-main.
  - Onde: `public/css/storefront.css:1245`, `resources/views/admin/categories/index.blade.php:38`, `resources/views/admin/categories/index.blade.php:48`, `resources/views/admin/categories/index.blade.php:63`, `resources/views/admin/categories/index.blade.php:125`, `resources/views/admin/categories/index.blade.php:132`, `resources/views/admin/categories/index.blade.php:138`, `resources/views/admin/reports/index.blade.php:14` e mais 23
- [ ] **PERF-01 · Tailwind compilado pelo Vite, sem cdn.tailwindcss.com** — layout ainda usa o CDN
  - Por quê: O CDN compila o CSS no navegador a cada carga (aviso no console: não é para produção) e causa pulo de layout. (regra `third-party-scripts`)
  - Como corrigir: Use @vite(['resources/css/app.css', 'resources/js/app.js']) no layout, mova os tokens para tailwind.config.js (já existe) e remova o script do CDN.
  - Onde: `resources/views/layouts/app.blade.php:15`
- [ ] **PERF-02 · Alpine com versão fixa (ou empacotado pelo Vite)** — 2 scripts sem versão
  - Por quê: @3.x.x pega sempre a última versão: uma atualização pode mudar o comportamento sem nenhum commit. (regra `third-party-scripts`)
  - Como corrigir: Ao migrar para o Vite, inicie o Alpine (e o plugin Focus) em resources/js/app.js e remova os CDNs; antes disso, fixe a versão exata nas URLs.
  - Onde: `resources/views/layouts/app.blade.php:17`, `resources/views/layouts/app.blade.php:18`
- [ ] **NAV-01 · Cabeçalho compacto no celular** — navegação sempre aberta
  - Por quê: Logado, o cabeçalho ocupa ~345 de 812px (42% da primeira tela) com quatro faixas de navegação. (regra `nav-hierarchy`)
  - Como corrigir: No celular, recolha a subnavegação e a área do usuário num menu com botão (aria-expanded) ou numa faixa com rolagem horizontal.
  - Onde: `resources/views/components/storefront-header.blade.php:19`
- [ ] **NAV-02 · Uma busca só na página do catálogo** — duas buscas na tela
  - Por quê: O catálogo mostra a busca do cabeçalho e a do filtro ao mesmo tempo, com o mesmo nome de campo. (regra `consistency`)
  - Como corrigir: Esconda a busca do cabeçalho em listings.index (@unless(request()->routeIs('listings.index'))) ou remova a do filtro.
  - Onde: `resources/views/listings/index.blade.php:20`
- [ ] **NAV-03 · Catálogo com 2 colunas no celular, como a home** — grade com 1 coluna
  - Por quê: Com 1 coluna o primeiro anúncio só aparece depois de mais de uma tela de rolagem. (regra `mobile-first`)
  - Como corrigir: Troque grid-cols-1 por grid-cols-2 na grade de resultados.
  - Onde: `resources/views/listings/partials/results.blade.php:2`
- [ ] **UI-01 · Ícones SVG (x-icon) no lugar de emojis** — 50 emojis
  - Por quê: Emojis mudam de desenho conforme o sistema e não seguem o estilo Lucide do resto do site. (regra `no-emoji-icons`)
  - Como corrigir: Acrescente os ícones que faltam em components/icon.blade.php (Lucide) e troque cada emoji por <x-icon name="..." />.
  - Onde: `resources/views/admin/categories/index.blade.php:20`, `resources/views/admin/categories/index.blade.php:30`, `resources/views/admin/categories/index.blade.php:92`, `resources/views/admin/categories/index.blade.php:157`, `resources/views/admin/dashboard.blade.php:15`, `resources/views/admin/dashboard.blade.php:18`, `resources/views/admin/dashboard.blade.php:21`, `resources/views/admin/dashboard.blade.php:33` e mais 42

### Média

- [ ] **A11Y-09 · Nenhum texto abaixo de 12px** — 119 ocorrências
  - Por quê: Home e catálogo têm dezenas de textos de 7 a 11px (rótulos de preço, tags, eyebrows), difíceis de ler no celular. (regra `font-scale`)
  - Como corrigir: Defina a escala 12/14/16/18/24/32: legendas e eyebrows com 12px no mínimo; troque text-[9px]/[10px]/[11px] por text-xs.
  - Onde: `resources/views/admin/categories/index.blade.php:42`, `resources/views/admin/categories/index.blade.php:57`, `resources/views/admin/categories/index.blade.php:68`, `resources/views/admin/categories/index.blade.php:103`, `resources/views/admin/categories/index.blade.php:111`, `resources/views/admin/categories/index.blade.php:164`, `resources/views/admin/categories/index.blade.php:179`, `resources/views/admin/categories/index.blade.php:182` e mais 111
- [ ] **A11Y-10 · Texto com contraste mínimo de 4,5:1** — 2 ocorrências
  - Por quê: slate-600 sobre o fundo escuro dá 3,4:1 (ex.: separador do rodapé); o resto da paleta passa. (regra `color-contrast`)
  - Como corrigir: Use slate-400 ou slate-500 para texto secundário; reserve 600/700 para bordas.
  - Onde: `resources/views/admin/categories/index.blade.php:12`, `public/css/theme.css:107`
- [ ] **A11Y-11 · Links que abrem nova aba avisam o usuário** — 2 links
  - Por quê: target="_blank" sem aviso tira o usuário do contexto sem explicação. (regra `keyboard-nav`)
  - Como corrigir: Acrescente <span class="sr-only">(abre em nova aba)</span> e um ícone de link externo, ou abra na mesma aba.
  - Onde: `resources/views/chat/index.blade.php:123`, `resources/views/orders/show.blade.php:207`
- [ ] **A11Y-12 · Confirmações em modal acessível, não confirm() do navegador** — 2 ocorrências
  - Por quê: O confirm() nativo destoa do visual e já existe um modal estilizado (esvaziar carrinho) como modelo. (regra `confirmation-dialogs`)
  - Como corrigir: Reaproveite o padrão do modal do carrinho (x-data + x-trap) para arquivar anúncio e excluir categoria.
  - Onde: `resources/views/admin/categories/index.blade.php:217`, `resources/views/seller/listings/index.blade.php:67`
- [ ] **A11Y-13 · Perfil começa pelo título da página** — 2 blocos antes do h1
  - Por quê: O aviso de e-mail e o link de vendedor aparecem soltos antes do <h1>, sem hierarquia. (regra `heading-hierarchy`)
  - Como corrigir: Mova o aviso de e-mail e o link "Gerenciar meu perfil de vendedor" para dentro do conteúdo, depois do x-page-heading.
  - Onde: `resources/views/profile/edit.blade.php:13`, `resources/views/profile/edit.blade.php:15`
- [ ] **PERF-03 · Configuração de cores em um só lugar** — config duplicada no layout
  - Por quê: O layout repete a configuração do tailwind.config.js inline; mudar uma não muda a outra. (regra `color-semantic`)
  - Como corrigir: Resolvido junto com PERF-01: apague o bloco tailwind.config = {...} do layout.
  - Onde: `resources/views/layouts/app.blade.php:21`
- [ ] **PERF-04 · Contador de mensagens não lidas fora da view do cabeçalho** — consulta na view
  - Por quê: O cabeçalho chama o ChatService direto da view em toda página autenticada, sem cache. (regra `main-thread-budget`)
  - Como corrigir: Crie um View Composer (app/View/Composers) que compartilhe $unreadChatCount com cache curto.
  - Onde: `resources/views/components/storefront-header.blade.php:31`
- [ ] **PERF-05 · Imagens com dimensões e carregamento preguiçoso** — 4 imagens
  - Por quê: Imagem sem width/height causa pulo de layout; fora da primeira tela deve usar loading="lazy". (regra `image-optimization`)
  - Como corrigir: Informe width e height e use loading="lazy" (ou fetchpriority="high" na imagem principal).
  - Onde: `resources/views/cart/index.blade.php:71`, `resources/views/checkout/success.blade.php:55`, `resources/views/seller/listings/form.blade.php:105`, `resources/views/seller/listings/index.blade.php:31`
- [ ] **NAV-04 · Nota do vendedor dentro do card do anúncio** — nota fora do card
  - Por quê: No catálogo a nota fica solta embaixo do card, fora da área clicável e do visual da home. (regra `consistency`)
  - Como corrigir: Passe a reputação para o componente storefront-listing e remova o parágrafo do partial.
  - Onde: `resources/views/listings/partials/results.blade.php:8`
- [ ] **NAV-05 · Contêiner das páginas sem padding duplicado** — 1 página
  - Por quê: O .tm-main já tem padding; páginas que somam px-/py- no contêiner ficam com margens diferentes das outras. (regra `consistency`)
  - Como corrigir: Use só a largura (mx-auto max-w-*) no contêiner da página e deixe o padding com o .tm-main.
  - Onde: `resources/views/cart/index.blade.php:6`
- [ ] **NAV-06 · Rodapé de marketing fora da área administrativa** — banner em todas as telas
  - Por quê: O banner "Seu próximo upgrade está aqui" aparece nas telas de admin, que não são de venda. (regra `nav-hierarchy`)
  - Como corrigir: No marketplace-footer, mostre o banner só quando não for request()->routeIs('admin.*').
  - Onde: `resources/views/components/marketplace-footer.blade.php:3`
- [ ] **NAV-07 · Checkout mostra em que etapa a compra está** — sem indicador de etapas
  - Por quê: Carrinho → revisão → pagamento não tem indicação de progresso. (regra `progressive-disclosure`)
  - Como corrigir: Adicione um indicador de etapas (lista com aria-current="step") no topo do checkout e do carrinho.
  - Onde: `resources/views/checkout/index.blade.php`
- [ ] **NAV-08 · "Sair" como ação secundária no cabeçalho** — logout com estilo primário
  - Por quê: O botão verde principal do cabeçalho é o de sair, enquanto "Minha conta" fica em segundo plano. (regra `destructive-nav-separation`)
  - Como corrigir: Deixe "Sair" como link discreto (ou dentro do menu da conta) e não use .tm-button nele.
  - Onde: `resources/views/components/storefront-header.blade.php:13`
- [ ] **NAV-09 · Enter no chat não envia mensagem em celular** — Enter sempre envia
  - Por quê: No teclado do celular, Enter é usado para quebrar linha; enviar no Enter manda mensagens pela metade. (regra `input-type-keyboard`)
  - Como corrigir: Envie no Enter só quando matchMedia('(pointer: fine)').matches; no toque, use o botão Enviar.
  - Onde: `resources/views/chat/index.blade.php:185`
- [ ] **UI-02 · Escala de verdes (brand) em ordem, do mais claro ao mais escuro** — 1 tom fora de ordem
  - Por quê: brand-300 é igual a brand-500 e brand-400 igual a brand-600; por isso o hover clareia em uma tela e escurece em outra. (regra `color-semantic`)
  - Como corrigir: Gere uma escala 50→950 com luminância decrescente em public/css/theme.css e revise os pares bg/hover.
  - Onde: `public/css/theme.css:9`
- [ ] **UI-03 · Botões principais com o mesmo verde e o mesmo hover** — 2 botões
  - Por quê: Alguns botões usam bg-brand-500 com hover mais escuro; o padrão é bg-brand-600 com hover mais claro. (regra `primary-action`)
  - Como corrigir: Padronize em bg-brand-600 hover:bg-brand-500 (ou em .tm-button) depois de corrigir UI-02.
  - Onde: `resources/views/cart/index.blade.php:48`, `resources/views/cart/index.blade.php:155`
- [ ] **UI-04 · Cores de texto, borda e fundo do CSS vindas dos tokens** — 40 declarações
  - Por quê: storefront.css mistura hex soltos (#101216, #1b1e25, #353a43...) com os tokens do theme.css. (regra `color-semantic`)
  - Como corrigir: Troque cada hex por rgb(var(--tm-...)); gradientes decorativos podem ficar.
  - Onde: `public/css/storefront.css:50`, `public/css/storefront.css:92`, `public/css/storefront.css:93`, `public/css/storefront.css:159`, `public/css/storefront.css:167`, `public/css/storefront.css:178`, `public/css/storefront.css:203`, `public/css/storefront.css:219` e mais 32
- [ ] **UI-05 · Selo de administrador sem a cor de erro** — selo vermelho
  - Por quê: O selo "Administrador" no perfil é vermelho, cor que o site usa para erro e ação destrutiva. (regra `color-semantic`)
  - Como corrigir: Use uma cor neutra ou a cor de marca no selo de papel do usuário.
  - Onde: `resources/views/profile/edit.blade.php:23`
- [ ] **UI-06 · Estados vazios com o componente x-empty-state** — 2 telas
  - Por quê: Carrinho e chat montam o estado vazio à mão, com emoji, e o do chat não tem link para o catálogo. (regra `empty-states`)
  - Como corrigir: Troque pelo <x-empty-state> com título, explicação e ação.
  - Onde: `resources/views/cart/index.blade.php:46`, `resources/views/chat/index.blade.php:81`
- [ ] **UI-07 · Campos do admin e do "Quero vender" no padrão visual** — 5 campos
  - Por quê: Nessas telas os campos não têm borda e quase somem sobre o fundo (contraste de componente abaixo de 3:1). (regra `consistency`)
  - Como corrigir: Use as mesmas classes dos outros formulários: bg-slate-950 border border-slate-700 rounded-xl px-3 py-2.
  - Onde: `resources/views/admin/users/index.blade.php:10`, `resources/views/admin/users/index.blade.php:11`, `resources/views/admin/users/index.blade.php:26`, `resources/views/admin/users/index.blade.php:36`, `resources/views/seller/application.blade.php:23`
- [ ] **UI-08 · Contas do admin em lista compacta, não um formulário inteiro por conta** — um formulário por conta
  - Por quê: Cada conta vira um cartão com selects e um botão verde; não escala com muitos usuários. (regra `consistency`)
  - Como corrigir: Use uma tabela (com rolagem própria no celular) e edição em linha ou numa página de detalhe.
  - Onde: `resources/views/admin/users/index.blade.php:18`

### Baixa

- [ ] **UI-09 · Telas do comprador sem jargão técnico** — 10 termos
  - Por quê: Termos como "webhook assíncrono" e "gateway" não fazem sentido para quem está comprando. (regra `consistency`)
  - Como corrigir: Troque por linguagem do usuário: "processador de pagamento", "confirmação automática".
  - Onde: `resources/views/checkout/cancel.blade.php:10`, `resources/views/checkout/index.blade.php:47`, `resources/views/checkout/success.blade.php:37`, `resources/views/checkout/success.blade.php:37`, `resources/views/checkout/success.blade.php:79`, `resources/views/orders/show.blade.php:54`, `resources/views/orders/show.blade.php:130`, `resources/views/orders/show.blade.php:140` e mais 2
- [ ] **UI-10 · Textos em caixa de frase, sem Iniciais Maiúsculas** — 48 textos
  - Por quê: Parte das telas usa "Finalização do Pedido", "Valor Total a Pagar"; o resto usa frase normal. (regra `consistency`)
  - Como corrigir: Deixe só a primeira palavra e nomes próprios em maiúscula.
  - Onde: `resources/views/admin/categories/index.blade.php:20`, `resources/views/admin/categories/index.blade.php:30`, `resources/views/admin/categories/index.blade.php:37`, `resources/views/admin/categories/index.blade.php:62`, `resources/views/admin/categories/index.blade.php:82`, `resources/views/admin/categories/index.blade.php:92`, `resources/views/admin/categories/index.blade.php:157`, `resources/views/admin/dashboard.blade.php:15` e mais 40

## Feitos

- [x] **BUG-01** Texto de componente sem escape duplo (ex.: "Mensagens &amp; Negociações")
- [x] **BUG-02** Nota do vendedor sem valor inventado
- [x] **BUG-03** Anúncio sem foto com arte e aviso, não uma caixa vazia
- [x] **BUG-04** Capas dos jogos com imagem reserva e alt decorativo
- [x] **BUG-05** Páginas de erro 404, 403 e 419 no visual do marketplace
- [x] **BUG-08** Paginação escura em todas as listagens
- [x] **A11Y-01** Todo <label> associado ao seu campo
- [x] **A11Y-02** Todo campo com nome acessível
- [x] **A11Y-04** Modais prendem e devolvem o foco
- [x] **A11Y-05** Emojis decorativos ocultos do leitor de tela
