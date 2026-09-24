<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_check_supports_other_drivers_and_rolls_back_writes(): void
    {
        $this->artisan('test:db', ['--write' => true])
            ->expectsOutputToContain('Gravação e leitura OK')
            ->assertExitCode(0);
        $this->assertDatabaseCount('cache', 0);
    }

    public function test_database_check_returns_failure_when_application_tables_are_missing(): void
    {
        Schema::drop('jobs');
        $this->artisan('test:db')->expectsOutputToContain('Tabela ausente: jobs')->assertExitCode(1);
    }
}
