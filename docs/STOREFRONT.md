# Página inicial do marketplace

Implementada com Blade, layout mestre e componentes reutilizáveis. A direção visual foi consultada com a skill UI UX Pro Max (`gaming marketplace dark`, `--design-system`) e adaptada à identidade do TheMerchant: fundo carvão, verde-lima, tipografia Plus Jakarta Sans e ilustração vetorial leve, sem dependências 3D.

## Comportamento

- A rota `/` chama `StorefrontService` para carregar até seis jogos ativos e os quatro anúncios publicados mais recentes.
- Busca envia `busca` ao catálogo; cards de jogo enviam `jogo`; atalhos de categoria enviam `tipo=cosmetic` ou `tipo=service`.
- O catálogo preserva e permite alterar o filtro por tipo.
- Imagens de anúncios são exibidas quando existem no disco público. Caso contrário, a página usa um símbolo da categoria, sem representar um produto fictício.
- A página não exibe contagens, descontos, reputações ou promessas de pagamento inventadas.
- A paleta global está em `public/css/theme.css`, carregada no layout mestre. As cores Tailwind `brand` e `slate` usam esses mesmos tokens, incluindo modificadores de opacidade. Botões primários usam verde-lima com texto escuro; feedback de erro, alerta e sucesso mantém cores semânticas.
- Estilos específicos da inicial estão em `public/css/storefront.css`, herdando os tokens globais. Ambos são compatíveis com o fluxo atual de assets sem Vite.

## Referências e assets

- [GGMAX](https://ggmax.com.br/): navegação por categorias e estrutura de vitrine.
- [Eneba](https://www.eneba.com/br/): busca proeminente e organização de coleções.
- `hero-blade.svg`: ilustração original, decorativa; não representa anúncio à venda.
- `cs2.jpg`: capa de Counter-Strike 2, Valve, obtida de https://cdn.akamai.steamstatic.com/steam/apps/730/header.jpg.
- `dota-2.jpg`: capa de Dota 2, Valve, obtida de https://cdn.akamai.steamstatic.com/steam/apps/570/header.jpg.
- Capas dos jogos pertencem aos respectivos titulares e são usadas para identificação neste projeto acadêmico.

## Validação

`tests/Feature/StorefrontTest.php` cobre estados vazios, visibilidade de anúncios publicados/jogos ativos e filtros por tipo combinados com busca. Executar com SQLite em memória conforme `docs/TESTES.md`, nunca com o banco de desenvolvimento.
