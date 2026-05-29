<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ScanHistory;
use App\Models\ScanQueue;
use App\Models\User;
use App\Services\Scan\CleanupService;
use App\Services\Scan\GitCloneService;
use App\Services\Scan\ScanOrchestratorService;
use App\Services\Scan\SonarScannerService;
use App\Services\Scan\StackDetectionService;
use App\Services\Scan\WorkspaceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ScanOrchestratorServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_orchestrator_marks_scan_completed_and_cleans_workspace(): void
    {
        $queue = $this->makeQueue();
        $workspace = [
            'workspace_path' => storage_path('framework/testing/workspaces/queue-'.$queue->id),
            'source_path' => storage_path('framework/testing/workspaces/queue-'.$queue->id.'/source'),
        ];

        $workspaceService = Mockery::mock(WorkspaceService::class);
        $workspaceService->shouldReceive('createWorkspace')->once()->with($queue->id)->andReturn($workspace);
        $workspaceService->shouldReceive('assertWithinWorkspace')->once()->with($workspace['workspace_path']);

        $gitCloneService = Mockery::mock(GitCloneService::class);
        $gitCloneService->shouldReceive('clone')->once()->andReturn([
            'successful' => true,
            'output' => "clone ok\n",
            'error_output' => '',
            'source_path' => $workspace['source_path'],
        ]);

        $stackDetectionService = Mockery::mock(StackDetectionService::class);
        $stackDetectionService->shouldReceive('detect')->once()->andReturn(['generic']);

        $sonarScannerService = Mockery::mock(SonarScannerService::class);
        $sonarScannerService->shouldReceive('run')->once()->andReturn([
            'successful' => true,
            'output' => "scan ok\n",
            'error_output' => '',
        ]);

        $cleanupService = Mockery::mock(CleanupService::class);
        $cleanupService->shouldReceive('deleteWorkspace')->once()->with($workspace['workspace_path']);

        $service = app()->make(ScanOrchestratorService::class, [
            'workspaceService' => $workspaceService,
            'gitCloneService' => $gitCloneService,
            'stackDetectionService' => $stackDetectionService,
            'sonarScannerService' => $sonarScannerService,
            'cleanupService' => $cleanupService,
        ]);

        $service->run($queue->fresh(['project.gitConnection', 'project.sonarConnection', 'queuedBy']), 'worker-1');

        $this->assertDatabaseHas('scan_queue', [
            'id' => $queue->id,
            'queue_status' => 'completed',
        ]);

        $history = ScanHistory::query()->where('queue_id', $queue->id)->first();
        $this->assertNotNull($history);
        $this->assertSame('completed', $history->status);
        $this->assertDatabaseHas('scan_logs', [
            'scan_history_id' => $history->id,
            'log_level' => 'info',
            'message' => 'Workspace cleanup completed.',
        ]);
    }

    public function test_orchestrator_marks_scan_failed_and_cleans_workspace_on_scanner_failure(): void
    {
        $queue = $this->makeQueue();
        $workspace = [
            'workspace_path' => storage_path('framework/testing/workspaces/queue-'.$queue->id),
            'source_path' => storage_path('framework/testing/workspaces/queue-'.$queue->id.'/source'),
        ];

        $workspaceService = Mockery::mock(WorkspaceService::class);
        $workspaceService->shouldReceive('createWorkspace')->once()->andReturn($workspace);
        $workspaceService->shouldReceive('assertWithinWorkspace')->once()->with($workspace['workspace_path']);

        $gitCloneService = Mockery::mock(GitCloneService::class);
        $gitCloneService->shouldReceive('clone')->once()->andReturn([
            'successful' => true,
            'output' => "clone ok\n",
            'error_output' => '',
            'source_path' => $workspace['source_path'],
        ]);

        $stackDetectionService = Mockery::mock(StackDetectionService::class);
        $stackDetectionService->shouldReceive('detect')->once()->andReturn(['generic']);

        $sonarScannerService = Mockery::mock(SonarScannerService::class);
        $sonarScannerService->shouldReceive('run')->once()->andReturn([
            'successful' => false,
            'output' => '',
            'error_output' => 'scanner failure',
        ]);

        $cleanupService = Mockery::mock(CleanupService::class);
        $cleanupService->shouldReceive('deleteWorkspace')->once()->with($workspace['workspace_path']);

        $service = app()->make(ScanOrchestratorService::class, [
            'workspaceService' => $workspaceService,
            'gitCloneService' => $gitCloneService,
            'stackDetectionService' => $stackDetectionService,
            'sonarScannerService' => $sonarScannerService,
            'cleanupService' => $cleanupService,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Scanner execution failed.');

        try {
            $service->run($queue->fresh(['project.gitConnection', 'project.sonarConnection', 'queuedBy']), 'worker-2');
        } finally {
            $this->assertDatabaseHas('scan_queue', [
                'id' => $queue->id,
                'queue_status' => 'failed',
            ]);

            $history = ScanHistory::query()->where('queue_id', $queue->id)->first();
            $this->assertNotNull($history);
            $this->assertSame('failed', $history->status);
        }
    }

    private function makeQueue(): ScanQueue
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        return ScanQueue::factory()->create([
            'project_id' => $project->id,
            'queued_by' => $user->id,
        ]);
    }
}
