<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SystemMaintenanceCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_system_preflight_command_runs(): void
    {
        $this->artisan('system:preflight')
            ->expectsOutput('System Preflight');
    }

    public function test_system_cleanup_old_data_dry_run_reports_targets(): void
    {
        DB::table('audit_logs')->insert([
            'user_id' => null,
            'action' => 'test.event',
            'auditable_type' => 'system',
            'auditable_id' => 0,
            'description' => 'Test Event',
            'metadata' => null,
            'entity_type' => 'system',
            'entity_id' => 0,
            'old_values' => null,
            'new_values' => null,
            'ip_address' => null,
            'user_agent' => null,
            'created_at' => now()->subDays(120),
            'updated_at' => now()->subDays(120),
        ]);

        $this->artisan('system:cleanup-old-data --dry-run')
            ->expectsOutput('System Cleanup Preview')
            ->assertExitCode(0);
    }
}
