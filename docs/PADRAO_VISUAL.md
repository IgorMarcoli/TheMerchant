# Padrão visual do TheMerchant

A home é a referência de composição do produto: superfícies escuras, verde para ações e destaques, tipografia Plus Jakarta Sans, títulos com hierarquia clara e conteúdo dividido em blocos com espaço para leitura.

## Diagnóstico

Antes da padronização, a home carregava sozinha `storefront.css`, usava uma marca e navegação próprias e exibia cards diferentes do catálogo. As páginas internas já compartilhavam a paleta em `theme.css`, mas variavam nos títulos, estados vazios e arredondamentos. Alguns botões internos usavam `tm-button` sem carregar sua definição. O catálogo e o detalhe também substituíam as imagens por um emoji.

## Estrutura compartilhada

- `layouts/app.blade.php`: fonte, paleta, estilos, conteúdo principal, mensagens e rodapé. Todas as páginas completas devem estender esse layout.
- `components/storefront-header.blade.php`: marca, busca, links públicos e navegação autenticada, com indicação da página atual e permissões preservadas.
- `components/page-heading.blade.php`: chamada curta, um título principal e descrição opcional. Use textos que expliquem a tarefa do usuário.
- `components/auth-card.blade.php`: painel e erros comuns aos fluxos de acesso, cadastro e recuperação da conta.
- `components/storefront-listing.blade.php`: apresentação de anúncios na home e no catálogo. `listing-card` reutiliza esse componente. Imagens indisponíveis recebem um ícone adequado ao tipo de anúncio.
- `components/empty-state.blade.php`: ícone, título, explicação e ação opcional para estados sem conteúdo.
- `components/marketplace-footer.blade.php`: rodapé único.

## Como construir novas páginas

```blade
@extends('layouts.app')
@section('title', 'Meus itens')
@section('content')
    <x-page-heading
        title="Meus itens"
        eyebrow="SEU INVENTÁRIO"
        description="Organize os itens que fazem parte das suas conquistas."
        class="mb-8"
    />

    <x-empty-state
        title="Seu inventário está começando."
        description="Explore o marketplace para encontrar seu próximo item."
        :href="route('listings.index')"
        action="Explorar marketplace"
    />
@endsection
```

## Critérios de qualidade

- Preserve o conteúdo e a função de cada tela; o hero ilustrado pertence à home.
- Use os tokens de `theme.css` e as utilidades Tailwind para compor os painéis. Mantenha os estilos dos componentes compartilhados em `storefront.css`.
- Use `bg-slate-900`, `border-slate-800`, cantos de 12–16 px e espaçamento de 24–32 px nos painéis. Ações principais usam verde; erros e ações destrutivas mantêm suas cores semânticas.
- Tenha um único `h1`, rótulos associados aos campos, foco visível e alvos de interação de pelo menos 44 px nos formulários.
- Empilhe títulos e ações em telas pequenas. Tabelas e navegação extensa devem permitir rolagem dentro de sua própria área, sem alargar a página.
- Apresente estados vazios com contexto e um próximo passo quando houver uma ação útil.
- Preserve CSRF, autorização, filtros, paginação, validações e estados de carregamento ao alterar a apresentação.
- Revise desktop e celular, com e sem conteúdo, e execute os testes dos fluxos afetados. Compilar as views não substitui a revisão visual.

## Verificação desta alteração

A suíte existente passou com 98 testes e 506 asserções, além dos 11 testes JavaScript de filtros e avaliações. As views também foram compiladas com sucesso. A revisão visual incluiu catálogo em desktop e login, contas, perfil e carrinho vazio em 360 px. O servidor local da porta 8000 não tinha o driver PostgreSQL; a revisão utilizou uma base SQLite temporária com os seeders do projeto, sem alterar o `.env`. Os fluxos privados restantes foram cobertos pelos testes existentes; não foram todos inspecionados visualmente.
