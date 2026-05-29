<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ScanHistory;
use App\Models\ScanLog;
use App\Models\ScanQueue;
use App\Models\User;
use App\Jobs\RunProjectScanJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ScanTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_admin_can_queue_scan_from_project(): void
    {
        Queue::fake();

        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $project = Project::factory()->create();

        $response = $this->actingAs($admin)->post(route('projects.scan-now', $project));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Scan has been added to the queue successfully.');
        $this->assertDatabaseHas('scan_queue', [
            'project_id' => $project->id,
            'queue_status' => 'pending',
        ]);
        Queue::assertPushed(RunProjectScanJob::class);
    }

    public function test_admin_can_view_scan_queue_index(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin)->get(route('scan-queue.index'));

        $response->assertOk();
        $response->assertSee('Scan Queue');
    }

    public function test_admin_can_view_scan_history_logs(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $history = ScanHistory::factory()->create([
            'triggered_by' => $admin->id,
            'status' => 'completed',
        ]);

        ScanLog::factory()->create([
            'scan_history_id' => $history->id,
            'message' => 'Scanner output placeholder',
        ]);

        $response = $this->actingAs($admin)->get(route('scan-history.logs', $history));

        $response->assertOk();
        $response->assertSee('Scanner output placeholder');
    }
}
