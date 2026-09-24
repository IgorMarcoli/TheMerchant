<?php

declare(strict_types=1);

// Adopt pre-existing Supabase chat tables using the same idempotent migration.
return require __DIR__.'/2026_09_24_000001_create_chat_tables.php';
