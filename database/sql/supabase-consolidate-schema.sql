-- Existing installations only. Run the entire file as database owner.
-- Canonical application: laravel. Portuguese prototype: legacy_marketplace.
-- No rows are deleted or merged. Stop application traffic during this operation.
-- If both exist, only an empty laravel installation may be archived.
BEGIN;
SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '60s';
SELECT pg_advisory_xact_lock(724196203);

DO $$
DECLARE
    table_name text;
    application_tables text[] := ARRAY[
        'migrations', 'users', 'seller_profiles', 'games', 'categories',
        'listings', 'listing_images', 'carts', 'cart_items', 'orders',
        'order_items', 'payments', 'reviews', 'reports', 'sessions',
        'cache', 'cache_locks', 'jobs', 'conversations', 'messages'
    ];
    prototype_tables text[] := ARRAY[
        'usuarios', 'categorias', 'jogos', 'anuncios', 'carrinhos',
        'itens_carrinho', 'pedidos', 'itens_pedido', 'pagamentos',
        'avaliacoes', 'denuncias'
    ];
    source_schema text;
    has_rows boolean;
    archived_schema text;
BEGIN
    -- A single source prevents moving a child table with FKs to the wrong users.
    IF EXISTS (SELECT 1 FROM pg_tables WHERE schemaname = 'public' AND tablename = ANY(application_tables))
       AND EXISTS (SELECT 1 FROM pg_tables WHERE schemaname = 'laravel' AND tablename = ANY(application_tables)) THEN
        IF to_regclass('public.users') IS NULL OR to_regclass('public.migrations') IS NULL
           OR to_regclass('laravel.migrations') IS NULL THEN
            RAISE EXCEPTION 'Incomplete application schemas: expected users and migration history in public and migration history in laravel.';
        END IF;
        IF EXISTS (SELECT 1 FROM pg_namespace WHERE nspname = 'legacy_laravel_empty') THEN
            RAISE EXCEPTION 'Archive collision: legacy_laravel_empty already exists.';
        END IF;
        IF EXISTS (SELECT 1 FROM pg_tables WHERE schemaname = 'laravel' AND NOT (tablename = ANY(application_tables))) THEN
            RAISE EXCEPTION 'Unexpected tables in laravel. Manual reconciliation required.';
        END IF;
        -- Lock both datasets before checking emptiness, blocking concurrent writes.
        FOR source_schema, table_name IN
            SELECT schemaname, tablename FROM pg_tables
            WHERE schemaname IN ('public', 'laravel') AND tablename = ANY(application_tables)
            ORDER BY schemaname, tablename
        LOOP
            EXECUTE format('LOCK TABLE %I.%I IN ACCESS EXCLUSIVE MODE', source_schema, table_name);
        END LOOP;
        -- Moving the empty tables must not redirect dependencies of live tables.
        IF EXISTS (
            SELECT 1 FROM pg_constraint fk
            JOIN pg_class child ON child.oid = fk.conrelid
            JOIN pg_namespace child_ns ON child_ns.oid = child.relnamespace
            JOIN pg_class parent ON parent.oid = fk.confrelid
            JOIN pg_namespace parent_ns ON parent_ns.oid = parent.relnamespace
            WHERE fk.contype = 'f' AND parent_ns.nspname = 'laravel'
              AND child_ns.nspname <> 'laravel'
        ) THEN
            RAISE EXCEPTION 'External foreign keys reference laravel. Manual reconciliation required.';
        END IF;
        FOR table_name IN SELECT tablename FROM pg_tables WHERE schemaname = 'laravel' LOOP
            IF to_regclass(format('public.%I', table_name)) IS NULL THEN
                RAISE EXCEPTION 'Missing public counterpart for laravel.%. Nothing has been moved.', table_name;
            END IF;
            IF table_name <> 'migrations' THEN
                EXECUTE format('SELECT EXISTS (SELECT 1 FROM laravel.%I)', table_name) INTO has_rows;
                IF has_rows THEN
                    RAISE EXCEPTION 'Both schemas contain data: laravel.% is not empty. Nothing has been moved.', table_name;
                END IF;
            END IF;
        END LOOP;
        CREATE SCHEMA legacy_laravel_empty;
        FOREACH table_name IN ARRAY application_tables LOOP
            IF to_regclass(format('laravel.%I', table_name)) IS NOT NULL THEN
                EXECUTE format('ALTER TABLE laravel.%I SET SCHEMA legacy_laravel_empty', table_name);
            END IF;
        END LOOP;
    END IF;
    source_schema := CASE WHEN to_regclass('public.users') IS NOT NULL THEN 'public' ELSE 'laravel' END;
    IF to_regclass(format('%I.users', source_schema)) IS NULL
       OR to_regclass(format('%I.migrations', source_schema)) IS NULL THEN
        RAISE EXCEPTION 'Expected existing Laravel users and migrations tables. This is not a bootstrap script.';
    END IF;
    FOREACH table_name IN ARRAY prototype_tables LOOP
        IF to_regclass(format('public.%I', table_name)) IS NOT NULL
           AND to_regclass(format('legacy_marketplace.%I', table_name)) IS NOT NULL THEN
            RAISE EXCEPTION 'Archive collision for %. Nothing has been moved.', table_name;
        END IF;
    END LOOP;

    CREATE SCHEMA IF NOT EXISTS laravel;
    CREATE SCHEMA IF NOT EXISTS legacy_marketplace;
    -- Archive is accessible to the owner, not to browser/API roles.
    FOREACH archived_schema IN ARRAY ARRAY['legacy_marketplace', 'legacy_laravel_empty'] LOOP
        IF EXISTS (SELECT 1 FROM pg_namespace WHERE nspname = archived_schema) THEN
            EXECUTE format('REVOKE ALL ON SCHEMA %I FROM PUBLIC', archived_schema);
            IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'anon') THEN
                EXECUTE format('REVOKE ALL ON SCHEMA %I FROM anon', archived_schema);
            END IF;
            IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'authenticated') THEN
                EXECUTE format('REVOKE ALL ON SCHEMA %I FROM authenticated', archived_schema);
            END IF;
        END IF;
    END LOOP;

    FOREACH table_name IN ARRAY application_tables LOOP
        IF to_regclass(format('public.%I', table_name)) IS NOT NULL THEN
            EXECUTE format('ALTER TABLE public.%I SET SCHEMA laravel', table_name);
        END IF;
    END LOOP;
    FOREACH table_name IN ARRAY prototype_tables LOOP
        IF to_regclass(format('public.%I', table_name)) IS NOT NULL THEN
            EXECUTE format('ALTER TABLE public.%I SET SCHEMA legacy_marketplace', table_name);
        END IF;
    END LOOP;
END
$$;
COMMIT;

SELECT schemaname, tablename FROM pg_tables
WHERE schemaname IN ('public', 'laravel', 'legacy_marketplace', 'legacy_laravel_empty')
ORDER BY schemaname, tablename;
