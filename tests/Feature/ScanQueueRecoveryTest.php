<?php

namespace Tests\Feature;

use App\Jobs\RunProjectScanJob;
use App\Models\ScanHistory;
use App\Models\ScanQueue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ScanQueueRecoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_admin_can_cancel_pending_scan(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $queue = ScanQueue::factory()->create([
            'queue_status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->post(route('scan-queue.cancel', $queue));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Pending scan cancelled successfully.');
        $this->assertDatabaseHas('scan_queue', [
            'id' => $queue->id,
            'queue_status' => 'cancelled',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'scan.cancelled',
            'auditable_id' => $queue->id,
        ]);
    }

    public function test_admin_can_retry_failed_scan(): void
    {
        Queue::fake();

        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $queue = ScanQueue::factory()->create([
            'queue_status' => 'failed',
            'failure_category' => 'scanner_failed',
        ]);

        $response = $this->actingAs($admin)->post(route('scan-queue.retry', $queue));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Failed scan queued for retry.');
        $this->assertDatabaseCount('scan_queue', 2);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'scan.retried',
        ]);
        Queue::assertPushed(RunProjectScanJob::class);
    }

    public function test_duplicate_pending_scan_is_blocked_for_manual_scan_now(): void
    {
        Queue::fake();

        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $queue = ScanQueue::factory()->create([
            'queue_status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->post(route('projects.scan-now', $queue->project));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'A pending or running scan already exists for this project and branch.');
        $this->assertDatabaseCount('scan_queue', 1);
        Queue::assertNothingPushed();
    }

    public function test_recover_stuck_command_dry_run_does_not_change_queue_state(): void
    {
        $queue = ScanQueue::factory()->create([
            'queue_status' => 'running',
            'started_at' => now()->subMinutes(90),
        ]);

        $this->artisan('scans:recover-stuck', [
            '--dry-run' => true,
            '--timeout-minutes' => 30,
        ])->expectsOutput('Stuck Scan Recovery Preview')
            ->assertExitCode(0);

        $this->assertDatabaseHas('scan_queue', [
            'id' => $queue->id,
            'queue_status' => 'running',
        ]);
    }

    public function test_recover_stuck_command_marks_running_scan_failed_and_logs_timeout(): void
    {
        $queue = ScanQueue::factory()->create([
            'queue_status' => 'running',
            'started_at' => now()->subMinutes(90),
        ]);

        $history = ScanHistory::factory()->create([
            'queue_id' => $queue->id,
            'project_id' => $queue->project_id,
            'status' => 'running',
            'started_at' => now()->subMinutes(90),
        ]);

        $this->artisan('scans:recover-stuck', [
            '--timeout-minutes' => 30,
        ])->expectsOutput('Stuck Scan Recovery')
            ->assertExitCode(0);

        $this->assertDatabaseHas('scan_queue', [
            'id' => $queue->id,
            'queue_status' => 'failed',
            'failure_category' => 'timeout',
        ]);

        $this->assertDatabaseHas('scan_histories', [
            'id' => $history->id,
            'status' => 'failed',
            'failure_category' => 'timeout',
        ]);

        $this->assertDatabaseHas('scan_logs', [
            'scan_history_id' => $history->id,
            'message' => 'Scan timed out and was marked as failed by queue recovery.',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'scan.timeout_recovered',
            'auditable_id' => $queue->id,
        ]);
    }
}
