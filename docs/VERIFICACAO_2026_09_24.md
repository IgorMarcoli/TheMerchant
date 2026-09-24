# Verificação de integração — 24/09/2026

Base incorporada: `acd5768`, da main original. Entrega publicada na branch `João` do original e na `main` do fork, sem criar branches com nomes de IA.

## Evidências locais

- `php artisan test`: 56 testes aprovados, 248 assertions, SQLite em memória. Inclui migrations limpas, autenticação, administração, chat HTTP e diagnóstico com rollback.
- `node scripts/test-schema-consolidation.mjs <caminho-pglite>`: quatro grupos aprovados; preservação de linhas, FKs, sequências, arquivos, reexecução e rollback em conflitos.
- Ícones Lucide com licença versionada, sem dependência JavaScript adicional.
- Supabase remoto: consolidação não executada nesta entrega; seguir o procedimento de backup e verificação em `CONSOLIDACAO_SCHEMA.md`.

## Issues

Fechamento proposto no PR: #28. Diagnósticos HTTP e o controller de consulta genérica foram removidos; testes negam acesso para visitante, usuário e administrador em ambiente local e produção. Diagnóstico CLI sanitiza a falha de conexão e não retorna registros pessoais. Publicação exige `APP_DEBUG=false`, conforme o guia de execução.

As outras 25 issues abertas permanecem sem fechamento automático. Prioridades identificadas:

- #1: evidência de instalação dos dois desenvolvedores ainda precisa ser vinculada.
- #2: validar o aceite completo de autenticação e reputação antes de encerrar; #27 cobre recuperação e verificação de e-mail.
- #29: concluir contrato de capacidade e sessões antes de encerrar implementações dependentes de catálogo/carrinho/checkout.
- #30, #31 e #32: validar transporte real, reconexão, suspensão em canais já abertos e UI; testes HTTP e schema não bastam.
- #33: sincronizar evidências acadêmicas e diagramas com o estado implementado.

Também permanecem abertas #6, #8, #10–#21, #23–#25, referentes aos critérios de catálogo, compra, pagamento, pós-venda, moderação e entrega final. Esta revisão não declara esses fluxos concluídos.
