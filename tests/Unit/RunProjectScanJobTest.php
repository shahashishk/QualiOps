<?php

namespace Tests\Unit;

use App\Jobs\RunProjectScanJob;
use App\Models\Project;
use App\Models\ScanQueue;
use App\Models\User;
use App\Services\Scan\ScanOrchestratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class RunProjectScanJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_job_calls_orchestrator_when_slot_is_available(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $queue = ScanQueue::factory()->create([
            'project_id' => $project->id,
            'queued_by' => $user->id,
        ]);

        $orchestrator = Mockery::mock(ScanOrchestratorService::class);
        $orchestrator->shouldReceive('canRunNow')->once()->andReturn(true);
        $orchestrator->shouldReceive('run')->once()->withArgs(function (ScanQueue $resolvedQueue, string $workerId) use ($queue) {
            return $resolvedQueue->is($queue) && filled($workerId);
        });

        $job = new RunProjectScanJob($queue->id);
        $job->handle($orchestrator);
    }
}
