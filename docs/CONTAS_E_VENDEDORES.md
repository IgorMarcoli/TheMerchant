# Conta única, vendas e administração

Toda conta ativa pode comprar. Não há mais `users.role`: a mesma pessoa pode comprar e vender.

- `users.status`: `active` ou `suspended`. Suspender a conta bloqueia as ações autenticadas.
- `users.is_admin`: permissão administrativa independente; não pode ser concedida pelo cadastro ou pelo formulário público de perfil.
- `seller_profiles.status`: `pending`, `approved` ou `suspended`. Apenas `approved`, com conta ativa, permite criar e gerenciar anúncios próprios.

## Fluxo

1. Cadastro cria uma conta comum, sem perfil de vendedor.
2. **Quero vender** (`/quero-vender`) recebe a biografia e cria uma solicitação pendente.
3. O administrador acessa **Administração → Contas e vendedores** (`/admin/usuarios`) e aprova a solicitação.
4. O vendedor continua comprando com a mesma conta. Não pode comprar o próprio anúncio.
5. Suspender vendas oculta os anúncios do catálogo e impede novas compras desses anúncios, inclusive quando já estavam no carrinho. Compras e entregas de pedidos anteriores continuam disponíveis para a conta ativa.
6. Repetir uma solicitação não remove a suspensão nem aprova o perfil. A equipe precisa reativá-lo.

Administração permite moderação, mas não concede automaticamente o direito de criar anúncios próprios. Administradores precisam de perfil aprovado para vender. Um administrador não pode remover o próprio acesso pelo painel.

## Atualização do banco

A migration `2026_09_23_000001_separate_selling_permissions_from_users` preserva IDs, pedidos e perfis. Converte administradores antigos para `is_admin=true`, aprova vendedores existentes e cria o perfil de vendedores legados que não o tinham. Perfis antigos de administradores também são aprovados; administradores sem perfil não ganham um automaticamente.

Com um banco já gerenciado pelo Laravel, execute:

```sh
php artisan migrate
```

No Supabase, usando o SQL Editor, escolha **uma** opção:

- Banco novo / schema `laravel` vazio: `database/sql/supabase-bootstrap.sql`.
- Já executou o bootstrap anterior: `database/sql/supabase-seller-accounts-upgrade.sql`.

Os arquivos incluem o registro das migrations; não é preciso executar as duas opções. As tabelas antigas em português no schema `public` não são convertidas nem excluídas. Mantenha `DB_SCHEMA=laravel` no ambiente e esse schema fora dos schemas expostos pela Data API.

Para regenerar os SQLs sem conectar ao banco:

```sh
php scripts/export-supabase-schema.php
```

Os SQLs não criam contas administrativas com senha padrão. Instalações novas precisam provisionar uma conta administrativa por um operador confiável; o seeder existente é exclusivo de demonstração. O rollback converte os acessos de volta ao modelo antigo e perde a distinção entre aprovação pendente e suspensão comercial; não o use como rotina de implantação.

## Verificação

```sh
php artisan test
```

`SellerPermissionsTest` cobre aprovação, tentativa de elevar privilégios, compra por vendedor, suspensão comercial, suspensão de conta, acesso a anúncios de terceiros e conversão de contas legadas.
