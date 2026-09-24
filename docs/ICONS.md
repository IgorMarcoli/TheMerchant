# Ícones da interface

Usamos SVGs oficiais do Lucide renderizados pelo componente Laravel Blade
`resources/views/components/icon.blade.php`. Apenas os desenhos utilizados são
incluídos; não há dependência de JavaScript, CDN ou requisições externas em execução.

```blade
<x-icon name="cart" />
<x-icon name="game" class="tm-promo-icon" />
<x-icon /> {{-- arrow-right por padrão --}}
```

A classe `tm-icon` mantém os tamanhos definidos no CSS. Os SVGs usam `currentColor`,
viewBox 24 × 24 e a espessura oficial de 2. São decorativos (`aria-hidden="true"`);
links e botões sem texto visível devem receber um nome acessível via `aria-label`.

## Mapeamento

| Nome no Blade | Lucide |
| --- | --- |
| search | search |
| cart | shopping-cart |
| shield | shield-check |
| game | gamepad-2 |
| spark | sparkles |
| bolt | zap |
| grid | layout-grid |
| user | user |
| store | store |
| chevron | chevron-right |
| arrow | arrow-right |

Nomes desconhecidos mantêm o fallback para arrow-right.

## Origem e manutenção

Fonte: https://github.com/lucide-icons/lucide/tree/66d8f9fc394b8530377e5f6112f0b8908ba01280/icons

Revisão: 66d8f9fc394b8530377e5f6112f0b8908ba01280

Licença original preservada em resources/licenses/lucide.txt.

Para adicionar ou atualizar ícones, use os SVGs oficiais, preserve sua geometria,
registre o mapeamento acima e atualize a referência da revisão e a licença quando necessário.
