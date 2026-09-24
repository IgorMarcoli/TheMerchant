// Run with Node and an external @electric-sql/pglite package entry point.
// No application credentials or network database are used.
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { pathToFileURL } from 'node:url';

const { PGlite } = await import(pathToFileURL(process.argv[2]).href);
const bootstrap = await readFile(new URL('../database/sql/supabase-bootstrap.sql', import.meta.url), 'utf8');
const consolidate = await readFile(new URL('../database/sql/supabase-consolidate-schema.sql', import.meta.url), 'utf8');
const chat = bootstrap.split('-- 2026_09_24_000001_create_chat_tables')[1].split('-- Register the exact migrations')[0];
const prototypes = ['usuarios', 'categorias', 'jogos', 'anuncios', 'carrinhos', 'itens_carrinho', 'pedidos', 'itens_pedido', 'pagamentos', 'avaliacoes', 'denuncias'];

async function fixture(db, schema) {
    await db.exec(bootstrap.replaceAll('laravel', schema));
    await db.exec(`
        INSERT INTO ${schema}.users (name, email, password) VALUES ('Buyer', 'buyer@example.test', 'test'), ('Seller', 'seller@example.test', 'test');
        INSERT INTO ${schema}.games (name, slug) VALUES ('Game', 'game');
        INSERT INTO ${schema}.categories (name, slug) VALUES ('Category', 'category');
        INSERT INTO ${schema}.listings (seller_id, game_id, category_id, title, slug, description, price) VALUES (2, 1, 1, 'Listing', 'listing', 'Description', 10);
        INSERT INTO ${schema}.conversations (buyer_id, seller_id, listing_id) VALUES (1, 2, 1);
        INSERT INTO ${schema}.messages (conversation_id, sender_id, client_uuid, body) VALUES (1, 1, 'fixture-message', 'Preserve history');
        CREATE ROLE anon;
        CREATE ROLE authenticated;
    `);
    for (const table of prototypes) {
        await db.exec(`CREATE TABLE public.${table} (id UUID PRIMARY KEY DEFAULT gen_random_uuid()); INSERT INTO public.${table} DEFAULT VALUES;`);
    }
    await db.exec('ALTER TABLE public.anuncios ADD COLUMN vendedor_id UUID REFERENCES public.usuarios(id); UPDATE public.anuncios SET vendedor_id = (SELECT id FROM public.usuarios LIMIT 1);');
}

async function expectAbort(db, sql, pattern) {
    await assert.rejects(db.exec(sql), pattern);
    await db.exec('ROLLBACK');
}

for (const source of ['public', 'laravel']) {
    const db = new PGlite();
    try {
        await fixture(db, source);
        await db.exec(consolidate);
        await db.exec(consolidate);
        assert.equal((await db.query("SELECT count(*)::int AS n FROM pg_tables WHERE schemaname = 'public'")).rows[0].n, 0);
        assert.equal((await db.query("SELECT count(*)::int AS n FROM pg_tables WHERE schemaname = 'legacy_marketplace'")).rows[0].n, 11);
        for (const table of prototypes) {
            assert.equal((await db.query(`SELECT count(*)::int AS n FROM legacy_marketplace.${table}`)).rows[0].n, 1);
        }
        assert.equal((await db.query('SELECT body FROM laravel.messages')).rows[0].body, 'Preserve history');
        assert.equal((await db.query('SELECT count(*)::int AS n FROM legacy_marketplace.anuncios a JOIN legacy_marketplace.usuarios u ON u.id = a.vendedor_id')).rows[0].n, 1);
        assert.equal((await db.query("INSERT INTO laravel.users (name, email, password) VALUES ('Next', 'next@example.test', 'test') RETURNING id")).rows[0].id, 3);
        assert.equal((await db.query("SELECT has_schema_privilege('anon', 'legacy_marketplace', 'USAGE') AS allowed")).rows[0].allowed, false);
        await assert.rejects(db.exec("INSERT INTO laravel.messages (conversation_id, sender_id, client_uuid, body) VALUES (999, 1, 'invalid', 'Invalid FK')"), /foreign key/);
        await db.exec('SET search_path TO laravel');
        await db.exec(chat);
        assert.equal((await db.query('SELECT count(*)::int AS n FROM messages')).rows[0].n, 1);
        await assert.rejects(db.exec("INSERT INTO messages (conversation_id, sender_id, client_uuid, body) VALUES (1, 1, 'fixture-message', 'Duplicate')"), /unique/);
        console.log(`PASS: ${source} consolidation, repeat execution, row/FK/sequence preservation, archive permissions, chat adoption and uniqueness`);
    } finally {
        await db.close();
    }
}

const db = new PGlite();
try {
    await fixture(db, 'public');
    await db.exec('CREATE SCHEMA laravel; CREATE TABLE laravel.users (id BIGINT)');
    await expectAbort(db, consolidate, /Incomplete application schemas/);
    assert.equal((await db.query('SELECT count(*)::int AS n FROM public.users')).rows[0].n, 2);
    await db.exec('DROP TABLE laravel.users; CREATE SCHEMA legacy_marketplace; CREATE TABLE legacy_marketplace.usuarios (id UUID)');
    await expectAbort(db, consolidate, /Archive collision/);
    assert.equal((await db.query('SELECT count(*)::int AS n FROM public.usuarios')).rows[0].n, 1);
    console.log('PASS: conflicting application schemas and archive collisions abort without moving data');
} finally {
    await db.close();
}

// Reported installation: populated public; empty laravel with older migrations.
const duplicateDb = new PGlite();
try {
    await fixture(duplicateDb, 'public');
    await duplicateDb.exec(bootstrap);
    await duplicateDb.exec(`
        DROP TABLE laravel.messages;
        DROP TABLE laravel.conversations;
        DELETE FROM laravel.migrations WHERE migration IN ('2026_09_24_000001_create_chat_tables', '2026_09_23_000002_create_conversations_and_messages_tables', '2026_09_24_000001_add_is_active_to_categories_table');
        DELETE FROM public.migrations WHERE migration IN ('2026_09_24_000001_create_chat_tables', '2026_09_23_000002_create_conversations_and_messages_tables', '2026_09_24_000001_add_is_active_to_categories_table');
        INSERT INTO public.migrations (migration, batch) VALUES ('manual_chat_conversations', 2), ('manual_chat_messages', 2);
    `);
    // A row arriving since the user's audit must prevent consolidation.
    await duplicateDb.exec("INSERT INTO laravel.users (name, email, password) VALUES ('New', 'new@example.test', 'test')");
    await expectAbort(duplicateDb, consolidate, /laravel.users is not empty/);
    assert.equal((await duplicateDb.query("SELECT to_regnamespace('legacy_laravel_empty') AS schema")).rows[0].schema, null);
    await duplicateDb.exec('DELETE FROM laravel.users');
    await duplicateDb.exec('CREATE TABLE public.external_reference (user_id BIGINT REFERENCES laravel.users(id))');
    await expectAbort(duplicateDb, consolidate, /External foreign keys/);
    await duplicateDb.exec('DROP TABLE public.external_reference');
    await duplicateDb.exec(consolidate);
    await duplicateDb.exec(consolidate);
    assert.equal((await duplicateDb.query('SELECT count(*)::int AS n FROM laravel.users')).rows[0].n, 2);
    assert.equal((await duplicateDb.query('SELECT count(*)::int AS n FROM legacy_laravel_empty.users')).rows[0].n, 0);
    assert.equal((await duplicateDb.query('SELECT count(*)::int AS n FROM legacy_laravel_empty.migrations')).rows[0].n, 14);
    assert.equal((await duplicateDb.query('SELECT count(*)::int AS n FROM laravel.migrations')).rows[0].n, 16);
    assert.equal((await duplicateDb.query('SELECT body FROM laravel.messages')).rows[0].body, 'Preserve history');
    assert.equal((await duplicateDb.query("INSERT INTO laravel.users (name, email, password) VALUES ('Next', 'next@example.test', 'test') RETURNING id")).rows[0].id, 3);
    assert.equal((await duplicateDb.query("SELECT has_schema_privilege('authenticated', 'legacy_laravel_empty', 'USAGE') AS allowed")).rows[0].allowed, false);
    assert.equal((await duplicateDb.query("SELECT count(*)::int AS n FROM pg_tables WHERE schemaname = 'public'")).rows[0].n, 0);
    console.log('PASS: populated public replaces empty laravel, both migration histories preserved, reexecution, new-data and external-FK guards');
} finally {
    await duplicateDb.close();
}
