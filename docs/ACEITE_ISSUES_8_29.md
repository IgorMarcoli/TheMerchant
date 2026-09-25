# Aceite — issues #8 e #29 (25/09/2026)

## Resultado da inspeção

- [#8](https://github.com/IgorMarcoli/TheMerchant/issues/8): parcialmente implementada.
  Já havia formulário GET, filtros básicos, grade e paginação. Faltavam busca
  reativa, sliders, tags, limpeza, reputação, feedback e painel recolhível.
- [#29](https://github.com/IgorMarcoli/TheMerchant/issues/29): especificação parcial.
  specs.md mencionava capacidade/reserva, mas não havia contrato completo com
  exemplos/rastreabilidade. requirements.md vinculava avaliação ao pedido inteiro.

## #8 — comportamento entregue

| Critério | Implementação |
| --- | --- |
| Aplicar e limpar filtros | Formulário GET com busca, tipo, jogo, categoria, preços, nota e ordenação; tags removíveis e limpar todos |
| Painel mobile | details/summary nativo, grade de uma coluna, controles com 44px mínimos; Enter/Espaço abrem, Escape fecha e devolve foco |
| Busca por digitação | Alpine.js, debounce de 350 ms, AbortController e geração de requisição invalidada imediatamente ao editar |
| Carregamento/erro | status durante fetch, alerta com retry, preservação dos últimos resultados e URL em erro |
| Total e paginação | Contador total, paginação Tailwind escura com aria-current, links GET preservando filtros |
| URL/histórico | pushState após sucesso, popstate restaura campos e resultados, alterações de filtro removem page |
| Preços | Dois sliders de R$ 0 a R$ 1.000 inicialmente, passo de R$ 0,01; campos numéricos aceitam valores maiores e expandem o teto dos sliders; vazio significa sem limite; mínimo não pode superar máximo |
| Reputação | Nota mínima de 1 a 5 exclui sem avaliações; ordem reputacao coloca sem avaliações ao final; desempate por id decrescente; estado sem avaliações explícito |

Contrato de consulta compartilhado com #7: `GET /anuncios`, parâmetros
`busca`, `tipo` (cosmetic/service), `jogo`, `categoria`, `preco_min`,
`preco_max`, `nota_min`, `ordem` (recentes/menor_preco/maior_preco/reputacao)
e `page`. CatalogRequest valida limites e tipos; CatalogService aplica a
consulta para navegação normal e reativa. Preços são inclusivos; 12 itens/página.
Reputação usa os agregados de seller_profiles (total_reviews/reputation_score).
Sem avaliações significa total_reviews = 0, não nota zero.

No checkout atual do repositório, apesar de #7 estar fechada, a consulta estava
no controller e não implementava reputação. Esta entrega centraliza essa consulta
e cobre a integração exigida por #8; não certifica o benchmark de desempenho de #7.

Os assets continuam usando o mecanismo já adotado pelo layout (Alpine/Tailwind
CDN), com script local public/js/catalog-filters.js; não requer novo build Vite.
Sem JavaScript, o formulário GET, limpeza e paginação permanecem utilizáveis.

## #29 — comportamento definido

[Contrato de domínio v1](CONTRATO_DOMINIO.md): tipos, elegibilidade, capacidade,
reserva de 15 minutos, concorrência, expiração, pagamentos tardios, snapshots,
horário/fuso, sessões, conclusão e avaliação por item. Contém 14 exemplos
D01–D14 com entradas/resultados e tabela de responsabilidade por issue.
requirements.md e specs.md apontam para a definição e alinham avaliação por item.

Nenhuma migration ou implementação de checkout/estoque foi adicionada por #29:
seu aceite é documental, como determinado na própria issue. Os cenários são
especificações para testes futuros das issues consumidoras, não testes de domínio
já executados nesta entrega.

## Verificações

- `php artisan test`: 80 testes aprovados, 431 assertions, SQLite isolado.
- `node --test tests/js/catalog-filters.test.mjs`: 5 testes aprovados,
  cobrindo debounce, corrida de respostas, erro/retry, limpeza/tags e histórico.
- `php vendor/bin/pint --dirty --preset psr12`: conformidade dos PHP alterados.
- Navegador local com banco SQLite exclusivo e 15 anúncios fictícios:
  busca automática reduziu 15 para 1 resultado e atualizou URL; carregamento,
  limpeza, painel recolhível e slider com teclado conferidos.
- Inspeção visual desktop e viewport 390 × 844: painel em uma coluna,
  campos com área de interação mínima de 44px. Enter/Escape conferidos.
  Interação por clique em viewport mobile; toque em aparelho físico não testado.
- Migrations executadas com sucesso no banco isolado de verificação, sem
  alterar banco configurado no .env.

## Arquivos

Criados:
- app/Http/Requests/CatalogRequest.php
- app/Services/CatalogService.php
- public/js/catalog-filters.js
- resources/views/listings/partials/results.blade.php
- resources/views/vendor/pagination/catalog.blade.php
- tests/Feature/CatalogTest.php
- tests/js/catalog-filters.test.mjs
- docs/CONTRATO_DOMINIO.md
- docs/ACEITE_ISSUES_8_29.md

Alterados:
- app/Http/Controllers/ListingPublicController.php
- resources/views/listings/index.blade.php
- requirements.md
- specs.md
