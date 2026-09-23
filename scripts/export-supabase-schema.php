<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require dirname(__DIR__).'/vendor/autoload.php';

$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

// Compile the migrations without connecting to or changing a database.
config(['database.default' => 'pgsql']);
DB::purge('pgsql');
$connection = DB::connection('pgsql');
$files = glob(database_path('migrations/*.php'));
sort($files);

$sql = <<<'SQL'
-- TheMerchant: bootstrap PostgreSQL / Supabase.
-- Generated from the repository migrations by scripts/export-supabase-schema.php.
-- Run the entire file in the Supabase SQL Editor, using the database owner.
-- Requires an empty (or absent) laravel schema. Existing tables cause a rollback.
-- Does not delete, rename, or import the tables/data in public.
-- This creates the current application structure; it does not implement Auth,
-- Storage, payment providers, email delivery, or application business rules.
-- Keep laravel out of the Supabase Data API exposed schemas.
-- Laravel environment: DB_CONNECTION=pgsql, DB_SCHEMA=laravel, DB_SSLMODE=require.
-- Migration records are inserted only after all tables have been created.
-- Do not use migrate:fresh or migrate:rollback on a populated application database.

BEGIN;
SET LOCAL lock_timeout = '5s';
CREATE SCHEMA IF NOT EXISTS laravel;
SET LOCAL search_path TO laravel;

SQL;

$queries = $connection->pretend(function (): void {
    Schema::create('migrations', function (Blueprint $table): void {
        $table->increments('id');
        $table->string('migration');
        $table->integer('batch');
    });
});
foreach ($queries as $query) {
    $sql .= $query['query'].";\n";
}

foreach ($files as $file) {
    $name = basename($file, '.php');
    $migration = require $file;
    $queries = $connection->pretend(function () use ($migration): void {
        $migration->up();
    });
    $sql .= "\n-- {$name}\n";
    foreach ($queries as $query) {
        if ($query['bindings'] !== []) {
            throw new RuntimeException('Bound statements require explicit export support: '.$name);
        }
        $sql .= $query['query'].";\n";
    }
}

$sql .= "\n-- Register the exact migrations represented above.\n";
$sql .= "INSERT INTO migrations (migration, batch) VALUES\n";
$rows = array_map(
    static fn (string $file): string => "    ('".str_replace("'", "''", basename($file, '.php'))."', 1)",
    $files,
);
$sql .= implode(",\n", $rows).";\n\nCOMMIT;\n";
$sql .= "\n-- The following result confirms the tables created in the application schema.\n";
$sql .= "SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname = 'laravel' ORDER BY tablename;\n";

$directory = database_path('sql');
if (! is_dir($directory)) {
    mkdir($directory, 0755, true);
}
$path = $directory.'/supabase-bootstrap.sql';
file_put_contents($path, $sql);
echo 'Generated '.count($files).' migrations into '.$path.PHP_EOL;

// An alternative to the full bootstrap for installations using the previous SQL.
$upgradeName = '2026_09_23_000001_separate_selling_permissions_from_users';
$migration = require database_path('migrations/'.$upgradeName.'.php');
$upgrade = <<<'SQL'
-- TheMerchant: update the previous Laravel schema, preserving accounts and data.
-- Alternative to the new bootstrap. Execute this entire file only once.
-- Requires the previous 13 migrations in schema laravel.
BEGIN;
SET LOCAL lock_timeout = '5s';
SET LOCAL search_path TO laravel;
LOCK TABLE migrations IN EXCLUSIVE MODE;
DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_09_23_000001_separate_selling_permissions_from_users') THEN
        RAISE EXCEPTION 'Seller account migration already applied';
    END IF;
END
$$;

SQL;
foreach ($connection->pretend(fn () => $migration->up()) as $query) {
    if ($query['bindings'] !== []) {
        throw new RuntimeException('Upgrade contains unsupported bindings.');
    }
    $upgrade .= $query['query'].";\n";
}
$upgrade .= "\nINSERT INTO migrations (migration, batch) SELECT '{$upgradeName}', COALESCE(MAX(batch), 0) + 1 FROM migrations;\nCOMMIT;\n";
$upgradePath = $directory.'/supabase-seller-accounts-upgrade.sql';
file_put_contents($upgradePath, $upgrade);
echo 'Generated upgrade into '.$upgradePath.PHP_EOL;
