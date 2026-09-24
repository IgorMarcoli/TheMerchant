-- Read-only inventory. Returns exact counts, never account or payment details.
-- Run before consolidation when both public and laravel contain application tables.
SELECT
    t.schemaname AS schema,
    t.tablename AS tabela,
    ((xpath('/row/total/text()', query_to_xml(
        format('SELECT count(*) AS total FROM %I.%I', t.schemaname, t.tablename),
        false, true, ''
    )))[1]::text)::bigint AS registros
FROM pg_catalog.pg_tables AS t
WHERE t.schemaname IN ('public', 'laravel', 'legacy_marketplace', 'legacy_laravel_empty')
ORDER BY t.tablename, t.schemaname;
