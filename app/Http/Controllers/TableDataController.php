<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TableDataController extends Controller
{
    /**
     * Lista todas as tabelas do banco de dados com contagem de registros e links.
     */
    public function index(): JsonResponse
    {
        try {
            $allTables = Schema::getTableListing();

            $summary = [];
            foreach ($allTables as $table) {
                $summary[] = [
                    'tabela' => $table,
                    'total_registros' => DB::table($table)->count(),
                    'url' => url("/api/tabela/{$table}"),
                ];
            }

            return response()->json([
                'status' => 'success',
                'banco' => DB::connection()->getDatabaseName(),
                'mensagem' => 'Envie o nome da tabela na URL para consultar seus dados. Ex: /api/tabela/users',
                'tabelas' => $summary,
            ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'mensagem' => 'Erro ao consultar o banco de dados: '.$e->getMessage(),
            ], 500, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Retorna todos os registros da tabela informada na URL em formato JSON.
     */
    public function show(string $table, Request $request): JsonResponse
    {
        try {
            $table = strtolower(trim($table));

            // Obtém todas as tabelas existentes para prevenção estrita de SQL Injection
            $allTables = Schema::getTableListing();

            if (! in_array($table, $allTables)) {
                return response()->json([
                    'status' => 'error',
                    'mensagem' => "A tabela '{$table}' não existe no banco de dados.",
                    'tabelas_disponiveis' => $allTables,
                ], 404, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }

            $query = DB::table($table);

            // Se for a tabela de usuários, oculta o hash da senha por segurança, a menos que ?all=1 seja passado
            if ($table === 'users' && ! $request->boolean('all')) {
                $columns = Schema::getColumnListing('users');
                $visibleColumns = array_values(array_diff($columns, ['password', 'remember_token']));
                $records = $query->select($visibleColumns)->get();
            } else {
                $records = $query->get();
            }

            return response()->json([
                'status' => 'success',
                'banco' => DB::connection()->getDatabaseName(),
                'tabela' => $table,
                'total_registros' => $records->count(),
                'dados' => $records,
            ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'mensagem' => 'Erro ao consultar a tabela: '.$e->getMessage(),
            ], 500, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
    }
}
