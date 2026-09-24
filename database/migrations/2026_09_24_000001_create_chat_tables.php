<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['pgsql', 'sqlite'], true)) {
            throw new RuntimeException('Chat schema supports PostgreSQL and SQLite only.');
        }

        // IF NOT EXISTS adopts the manually created tables from the original
        // Supabase schema without replacing conversations or messages.
        $id = DB::getDriverName() === 'pgsql' ? 'BIGSERIAL PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';

        DB::statement("CREATE TABLE IF NOT EXISTS conversations (
            id {$id},
            buyer_id BIGINT NOT NULL REFERENCES users(id),
            seller_id BIGINT NOT NULL REFERENCES users(id),
            listing_id BIGINT NOT NULL REFERENCES listings(id),
            buyer_last_read_message_id BIGINT NULL,
            seller_last_read_message_id BIGINT NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        )");
        DB::statement("CREATE TABLE IF NOT EXISTS messages (
            id {$id},
            conversation_id BIGINT NOT NULL REFERENCES conversations(id),
            sender_id BIGINT NOT NULL REFERENCES users(id),
            client_uuid VARCHAR(255) NOT NULL,
            body TEXT NOT NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        )");
        // Invalid existing duplicates fail the migration instead of losing data.
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS conversations_participants_listing_unique ON conversations (buyer_id, seller_id, listing_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS conversations_buyer_id_updated_at_index ON conversations (buyer_id, updated_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS conversations_seller_id_updated_at_index ON conversations (seller_id, updated_at)');
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS messages_client_unique ON messages (conversation_id, sender_id, client_uuid)');
        DB::statement('CREATE INDEX IF NOT EXISTS messages_conversation_id_index ON messages (conversation_id, id)');
    }

    public function down(): void
    {
        // The tables may predate this migration. Rollback must not erase chat history.
        throw new RuntimeException('Chat tables may contain pre-existing history; automatic rollback is intentionally disabled.');
    }
};
