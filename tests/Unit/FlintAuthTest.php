<?php
declare(strict_types=1);

namespace Tests\Unit;

use Flint\Application;
use PHPUnit\Framework\TestCase;
use Vancil\FlintAuth\Commands\UiCommand;
use Vancil\FlintAuth\FlintAuth;

class FlintAuthTest extends TestCase
{
    public function test_commands_returns_array_with_ui_command(): void
    {
        $app      = new Application(sys_get_temp_dir());
        $commands = FlintAuth::commands($app);

        $this->assertCount(1, $commands);
        $this->assertInstanceOf(UiCommand::class, $commands[0]);
    }

    public function test_register_runs_without_error(): void
    {
        $app = new Application(sys_get_temp_dir());
        FlintAuth::register($app);

        $this->assertTrue(true);
    }
}
