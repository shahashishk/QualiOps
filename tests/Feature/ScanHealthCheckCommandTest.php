<?php

namespace Tests\Feature;

use App\Services\System\SystemHealthService;
use Carbon\CarbonImmutable;
use Mockery\MockInterface;
use Tests\TestCase;

class ScanHealthCheckCommandTest extends TestCase
{
    public function test_health_check_command_fails_when_runtime_or_connection_checks_fail(): void
    {
        $this->mock(SystemHealthService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getReport')->once()->andReturn([
                'generated_at' => CarbonImmutable::parse('2026-05-26 10:30:00'),
                'runtime' => [
                    [
                        'label' => 'Git',
                        'status' => true,
                        'value' => 'git version 2.52.0.windows.1',
                        'details' => 'Binary detected in PATH.',
                    ],
                ],
                'queue' => [
                    [
                        'label' => 'Queue Connection',
                        'status' => true,
                        'value' => 'database',
                        'details' => 'Queue connection is configured.',
                    ],
                ],
                'connections' => [
                    'sonar' => [
                        [
                            'name' => 'Primary Sonar',
                            'status' => false,
                            'details' => 'Connection test failed with HTTP 401.',
                        ],
                    ],
                    'git' => [],
                ],
            ]);
        });

        $this->artisan('scan:health-check')
            ->expectsOutput('Scanner Runtime Health Check')
            ->expectsOutput('SonarQube Connections')
            ->expectsOutput('Git Connections')
            ->assertExitCode(1);
    }
}
