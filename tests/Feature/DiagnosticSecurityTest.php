<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Connection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class DiagnosticSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_diagnostics_are_unavailable_to_visitors_and_accounts_in_all_environments(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();
        foreach (['local', 'production'] as $environment) {
            $this->app->instance('env', $environment);
            foreach ([null, $user, $admin] as $account) {
                auth()->forgetGuards();
                if ($account !== null) {
                    $this->actingAs($account);
                }
                foreach (['/test-db', '/tabela', '/tabela/users?all=1', '/api/tabela', '/api/tabela/users?all=1', '/api/tabela/sessions'] as $url) {
                    $this->getJson($url)->assertNotFound();
                }
            }
        }
    }

    public function test_cli_diagnostic_does_not_disclose_connection_errors(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('getDriverName')->andReturn('pgsql');
        $connection->shouldReceive('select')->with('SELECT 1')
            ->andThrow(new \RuntimeException('password authentication failed: secret-sentinel'));
        DB::shouldReceive('connection')->once()->andReturn($connection);
        $this->artisan('test:db')
            ->expectsOutputToContain('Confira DB_USERNAME e DB_PASSWORD')
            ->doesntExpectOutputToContain('secret-sentinel')
            ->assertExitCode(1);
    }
}
