<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('test:db', function () {
    $this->info('Testando conexao com o banco de dados MySQL...');
    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        $dbName = \Illuminate\Support\Facades\DB::connection()->getDatabaseName();
        $this->info(" -> [SUCESSO] Conectado ao banco [$dbName] com sucesso!");

        if (\Illuminate\Support\Facades\Schema::hasTable('users')) {
            $count = \App\Models\User::count();
            $this->info(" -> [OK] Tabela 'users' encontrada com $count usuario(s) cadastrado(s).");
        } else {
            $this->warn(" -> [AVISO] Tabela 'users' nao encontrada.");
        }

        $tablesRaw = \Illuminate\Support\Facades\DB::select('SHOW TABLES');
        $this->info(" -> [OK] Total de tabelas criadas no banco: " . count($tablesRaw));
    } catch (\Throwable $e) {
        $this->error(' -> [ERRO] Falha ao conectar no banco de dados: ' . $e->getMessage());
    }
})->purpose('Testa a conexao com o banco de dados e verifica a tabela users');
