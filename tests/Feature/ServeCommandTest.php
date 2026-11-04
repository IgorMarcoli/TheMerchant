<?php

namespace Tests\Feature;

use Illuminate\Foundation\Console\ServeCommand;
use Tests\TestCase;

class ServeCommandTest extends TestCase
{
    public function test_serve_passes_windows_system_root_to_the_server_process(): void
    {
        $this->assertContains('SystemRoot', ServeCommand::$passthroughVariables);
    }

    public function test_system_root_is_not_duplicated_on_each_boot(): void
    {
        $this->refreshApplication();
        $this->refreshApplication();

        $this->assertCount(1, array_keys(ServeCommand::$passthroughVariables, 'SystemRoot', true));
    }
}
