# Consolidação do banco

O modelo canônico usa nomes em inglês e IDs bigint, no schema `laravel`, conforme as migrations e os models Eloquent. O protótipo com nomes em português e IDs UUID não é uma segunda fonte de dados da aplicação.

## Banco existente

1. Faça um backup do banco e interrompa o tráfego da aplicação e os workers.
2. No SQL Editor do projeto correto, execute **inteiro** `database/sql/supabase-consolidate-schema.sql` como proprietário do banco.
3. Configure `DB_SCHEMA=laravel`, execute `php artisan config:clear`, `php artisan migrate` e `php artisan test:db --write`.
4. Confira login, catálogo, carrinho e pedidos; retome os workers e a aplicação.

O SQL move as tabelas atuais de `public` para `laravel`, incluindo `migrations`, preservando registros, IDs, índices, sequências associadas e relacionamentos. As 11 tabelas em português são movidas para `legacy_marketplace`. Nenhuma tabela é apagada. Reexecutar o script após sucesso não duplica tabelas.

Se houver tabelas atuais em **ambos** os schemas, o SQL verifica sob bloqueio se todas as tabelas de `laravel` estão vazias, exceto `migrations`. Somente nesse caso arquiva a estrutura vazia em `legacy_laravel_empty` e move o conjunto de `public` para `laravel`. Os dois históricos de migrations são preservados separadamente. Esse tratamento corresponde ao diagnóstico fornecido: dados em `public`, com 16 migrations, e `laravel` vazio, com 14 migrations.

Dados em qualquer tabela de `laravel` além de `migrations`, tabelas inesperadas, contrapartes ausentes em `public`, FKs externas para `laravel` ou colisões no arquivo interrompem a transação. Objetos fora da lista explícita não são movidos. Integrações que usam nomes qualificados `public.*` precisam ser atualizadas antes de retomar o tráfego.

Se aparecer `Application tables exist in both public and laravel`, você está usando a versão anterior do script. Use o arquivo atualizado após conferir `database/sql/supabase-audit-schema.sql`. Essa consulta somente lê nomes e contagens exatas de registros, sem mostrar dados pessoais. Contagens iguais, por si só, não comprovam que os registros sejam idênticos.

O arquivamento **não importa os dados antigos** para o Laravel. Se o protótipo contiver contas ou vendas que precisam continuar acessíveis no aplicativo, é necessária uma importação com mapa UUID → bigint e conversão de status. Avaliações antigas são por pedido, enquanto as atuais são por item; denúncias antigas também podem apontar para usuários. Não há uma conversão automática sem decisões de negócio.

Mantenha `laravel`, `legacy_marketplace` e `legacy_laravel_empty` fora dos schemas expostos pela Data API. O arquivo revoga acesso aos schemas de arquivo para PUBLIC, anon e authenticated; o proprietário continua com acesso. No diagrama do Supabase, selecione apenas `laravel` para visualizar o modelo ativo.

## Instalações novas e chat

Use `php artisan migrate` com schema `laravel` vazio, ou o SQL gerado por `php scripts/export-supabase-schema.php`. Não use o bootstrap sobre um banco existente.

A migration de chat cria as duas tabelas em instalações novas e preserva as tabelas manuais já existentes com o formato do SQL fornecido. Cria índices para unicidade da conversa, idempotência do envio e histórico. Dados duplicados interrompem a migration para revisão, sem exclusão automática. O rollback dessa migration é bloqueado para não apagar histórico preexistente.

A base de chat recebida da main já contém controller, service, policy, interface e testes HTTP. A migration de 23/09 delega à adoção idempotente de 24/09 para não recriar tabelas manuais; ambas preservam o histórico no rollback. Transporte real, reconexão e revogação de canais continuam dependendo da validação das issues #30–#32; os testes HTTP não comprovam essas entregas.

## Verificação desta correção

Os testes Laravel rodam com `php artisan test` em SQLite em memória. O SQL PostgreSQL é validado separadamente com PGlite, sem credenciais ou conexão ao Supabase:

```powershell
npm.cmd install --prefix "$env:TEMP/themerchant-schema-tests" --no-audit --no-fund --ignore-scripts @electric-sql/pglite
node scripts/test-schema-consolidation.mjs "$env:TEMP/themerchant-schema-tests/node_modules/@electric-sql/pglite/dist/index.js"
```

O teste cobre bootstrap, origem em `public` ou `laravel`, reexecução, preservação de registros/FKs/sequências, permissões do arquivo, adoção do chat e conflitos com rollback. Não substitui verificar integrações e permissões específicas do ambiente real.

A conexão MCP do Supabase recusou acesso ao projeto durante a preparação. Os volumes foram informados pelo usuário via consulta de diagnóstico; a consolidação remota permanece pendente de execução e verificação.
