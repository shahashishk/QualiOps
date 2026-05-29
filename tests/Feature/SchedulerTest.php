<?php

namespace Tests\Feature;

use App\Jobs\RunProjectScanJob;
use App\Models\Project;
use App\Models\ScanQueue;
use App\Models\ScanScheduleLog;
use App\Models\User;
use App\Services\System\SystemHealthService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;
use Tests\TestCase;

class SchedulerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_admin_can_view_scheduler_preview_page(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $this->mockRuntimeAvailability(false);

        $response = $this->actingAs($admin)->get(route('scheduler.index'));

        $response->assertOk();
        $response->assertSee('Preview Scheduled Scans');
    }

    public function test_dispatch_command_dry_run_does_not_create_queue_records_or_logs(): void
    {
        Queue::fake();
        $this->mockRuntimeAvailability(false);

        Project::factory()->create([
            'project_name' => 'Daily Governance Portal',
            'schedule_enabled' => true,
            'schedule_frequency' => 'daily',
            'schedule_time' => '10:30',
        ]);

        CarbonImmutable::setTestNow('2026-05-26 10:30:00');

        $this->artisan('scans:dispatch-scheduled --dry-run')
            ->expectsOutput('Scheduled Scan Preview')
            ->assertExitCode(0);

        $this->assertDatabaseCount('scan_queue', 0);
        $this->assertDatabaseCount('scan_schedule_logs', 0);
        Queue::assertNothingPushed();
    }

    public function test_dispatch_command_creates_queue_log_and_skips_job_dispatch_when_runtime_is_unavailable(): void
    {
        Queue::fake();
        $this->mockRuntimeAvailability(false);

        $project = Project::factory()->create([
            'project_name' => 'Daily Governance Portal',
            'branch_name' => 'uat',
            'schedule_enabled' => true,
            'schedule_frequency' => 'daily',
            'schedule_time' => '10:30',
        ]);

        CarbonImmutable::setTestNow('2026-05-26 10:30:00');

        $this->artisan('scans:dispatch-scheduled')
            ->expectsOutput('Scheduled Scan Dispatch')
            ->assertExitCode(0);

        $this->assertDatabaseHas('scan_queue', [
            'project_id' => $project->id,
            'branch_name' => 'uat',
            'queue_status' => 'pending',
            'queued_by' => null,
        ]);

        $this->assertDatabaseHas('scan_schedule_logs', [
            'eligible_projects_count' => 1,
            'queued_projects_count' => 1,
            'skipped_projects_count' => 0,
            'status' => 'queued_without_dispatch',
        ]);

        Queue::assertNothingPushed();
    }

    public function test_dispatch_command_skips_duplicate_pending_queue_entries(): void
    {
        Queue::fake();
        $this->mockRuntimeAvailability(false);

        $project = Project::factory()->create([
            'project_name' => 'Weekly Governance Portal',
            'branch_name' => 'uat',
            'schedule_enabled' => true,
            'schedule_frequency' => 'weekly',
            'schedule_day' => 'tuesday',
            'schedule_time' => '10:30',
        ]);

        ScanQueue::factory()->create([
            'project_id' => $project->id,
            'branch_name' => 'uat',
            'queue_status' => 'pending',
        ]);

        CarbonImmutable::setTestNow('2026-05-26 10:30:00');

        $this->artisan('scans:dispatch-scheduled')->assertExitCode(0);

        $this->assertDatabaseCount('scan_queue', 1);
        $this->assertDatabaseHas('scan_schedule_logs', [
            'eligible_projects_count' => 1,
            'queued_projects_count' => 0,
            'skipped_projects_count' => 1,
            'status' => 'skipped',
        ]);

        Queue::assertNothingPushed();
    }

    private function mockRuntimeAvailability(bool $available): void
    {
        $runtimeChecks = [
            [
                'label' => 'Git',
                'status' => $available,
                'value' => $available ? 'ready' : 'missing',
                'details' => $available ? 'Binary detected.' : 'Binary missing.',
            ],
            [
                'label' => 'Laravel Queue Worker',
                'status' => $available,
                'value' => $available ? 'running' : 'not running',
                'details' => $available ? 'Worker detected.' : 'Worker missing.',
            ],
            [
                'label' => 'Workspace Path',
                'status' => $available,
                'value' => storage_path('app/scan-workspaces'),
                'details' => $available ? 'Writable.' : 'Unavailable.',
            ],
        ];

        $queueChecks = [
            [
                'label' => 'Queue Connection',
                'status' => true,
                'value' => 'database',
                'details' => 'Queue connection is configured.',
            ],
            [
                'label' => 'Queue Backend Availability',
                'status' => true,
                'value' => 'database',
                'details' => 'Queue backend is reachable.',
            ],
        ];

        $this->mock(SystemHealthService::class, function (MockInterface $mock) use ($runtimeChecks, $queueChecks): void {
            $mock->shouldReceive('runtimeChecks')->andReturn($runtimeChecks);
            $mock->shouldReceive('queueChecks')->andReturn($queueChecks);
        });
    }
}
