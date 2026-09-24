<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class CheckDatabase extends Command
{
    protected $signature = 'test:db {--write : Verify insert, read and update with rollback}';

    protected $description = 'Verifica a conexão e as tabelas da aplicação, inclusive no Supabase';

    public function handle(): int
    {
        $connection = DB::connection();
        try {
            $driver = $connection->getDriverName();
            $connection->select('SELECT 1');
            $this->info('Conexão OK: '.$driver.' / '.$connection->getDatabaseName());
            if ($driver === 'pgsql') {
                $schema = $connection->selectOne('SELECT current_schema() AS name')->name;
                $this->line('Schema: '.($schema ?? '(não encontrado)'));
            }
            foreach (['users', 'seller_profiles', 'listings', 'sessions', 'cache', 'jobs'] as $table) {
                if (! $connection->getSchemaBuilder()->hasTable($table)) {
                    $this->error('Tabela ausente: '.$table.'. Execute as migrations.');

                    return self::FAILURE;
                }
            }
            $this->info('Tabelas essenciais OK.');
            if ($this->option('write')) {
                $connection->beginTransaction();
                try {
                    $key = 'themerchant-database-check-'.Str::uuid();
                    $connection->table('cache')->insert(['key' => $key, 'value' => 'initial', 'expiration' => time() + 60]);
                    $connection->table('cache')->where('key', $key)->update(['value' => 'verified']);
                    if ($connection->table('cache')->where('key', $key)->value('value') !== 'verified') {
                        throw new RuntimeException('Write verification failed');
                    }
                } finally {
                    $connection->rollBack();
                }
                $this->info('Gravação e leitura OK; alterações de teste desfeitas.');
            }

            return self::SUCCESS;
        } catch (Throwable $exception) {
            // Connection exceptions may contain credentials; do not print them.
            $message = $exception->getMessage();
            $hint = match (true) {
                str_contains($message, 'could not find driver') => 'Habilite pdo_pgsql no PHP que está executando a aplicação.',
                str_contains($message, 'password authentication failed') => 'Confira DB_USERNAME e DB_PASSWORD no .env e execute config:clear.',
                default => 'Confira host, porta, rede, SSL e permissões do banco.',
            };
            $this->error('Falha na verificação do banco. '.$hint);

            return self::FAILURE;
        }
    }
}
