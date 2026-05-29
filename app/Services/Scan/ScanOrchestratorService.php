<?php

namespace App\Services\Scan;

use App\Events\ScanLifecycleEvent;
use App\Models\Project;
use App\Models\ScanHistory;
use App\Models\ScanQueue;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class ScanOrchestratorService
{
    public function __construct(
        private readonly WorkspaceService $workspaceService,
        private readonly GitCloneService $gitCloneService,
        private readonly StackDetectionService $stackDetectionService,
        private readonly SonarScannerService $sonarScannerService,
        private readonly ScanLogService $scanLogService,
        private readonly CleanupService $cleanupService,
        private readonly AuditLogService $auditLogService,
    ) {
    }

    public function canRunNow(): bool
    {
        $limit = (int) config('scan.max_concurrent_jobs', 1);

        if ($limit < 1) {
            return true;
        }

        return Cache::lock('scan-concurrency-gate', 10)->get(function () use ($limit) {
            return ScanQueue::query()
                ->where('queue_status', 'running')
                ->count() < $limit;
        }) ?? false;
    }

    public function run(ScanQueue $queue, string $workerId): void
    {
        $workspace = null;
        $history = null;

        try {
            [$queue, $history] = DB::transaction(function () use ($queue, $workerId) {
                $queue->update([
                    'queue_status' => 'running',
                    'started_at' => now(),
                    'worker_id' => $workerId,
                ]);

                $history = ScanHistory::query()->create([
                    'project_id' => $queue->project_id,
                    'branch_name' => $queue->branch_name,
                    'scan_type' => $queue->scan_type,
                    'triggered_by' => $queue->queued_by,
                    'queue_id' => $queue->id,
                    'sonar_project_key' => $queue->project->sonar_project_key,
                    'sonar_dashboard_url' => rtrim((string) $queue->project->sonarConnection->sonar_url, '/').'/dashboard?id='.$queue->project->sonar_project_key,
                    'status' => 'running',
                    'started_at' => now(),
                ]);

                $this->auditLogService->record('scan.started', $history, $queue->queuedBy, [], [
                    'project_id' => $queue->project_id,
                    'branch_name' => $queue->branch_name,
                    'status' => 'running',
                ]);

                return [$queue->fresh(['project.gitConnection', 'project.sonarConnection', 'queuedBy']), $history];
            });

            $workspace = $this->workspaceService->createWorkspace($queue->id);
            $this->workspaceService->assertWithinWorkspace($workspace['workspace_path']);
            $this->auditLogService->record('workspace.created', $queue, $queue->queuedBy, [], [
                'workspace_path' => $workspace['workspace_path'],
            ]);

            $this->scanLogService->write($history, 'info', 'Workspace created for scan.', [
                'workspace_path' => $workspace['workspace_path'],
            ]);

            $cloneResult = $this->gitCloneService->clone($queue->project, $workspace['source_path']);
            $this->scanLogService->writeOutput($history, $cloneResult['output'], $cloneResult['error_output'], $this->secrets($queue->project));

            if (! $cloneResult['successful']) {
                throw new \RuntimeException('git_clone_failed: Repository clone failed for the requested branch.');
            }

            try {
                $detectedStacks = $this->stackDetectionService->detect($workspace['source_path'], $queue->scan_type);
            } catch (Throwable $exception) {
                throw new \RuntimeException('stack_detection_failed: '.$exception->getMessage(), 0, $exception);
            }

            $this->scanLogService->write($history, 'info', 'Stack detection completed.', [
                'detected_stacks' => $detectedStacks,
            ], $this->secrets($queue->project));

            $scanResult = $this->sonarScannerService->run($queue->project, $history, $workspace['source_path'], $detectedStacks);

            if (! $scanResult['successful']) {
                $failureCategory = str_contains(strtolower($scanResult['error_output']), 'sonar')
                    ? 'sonar_upload_failed'
                    : 'scanner_failed';

                throw new \RuntimeException($failureCategory.': Scanner execution failed.');
            }

            $completedAt = now();

            $history->update([
                'status' => 'completed',
                'failure_category' => null,
                'completed_at' => $completedAt,
                'duration_seconds' => $completedAt->diffInSeconds($history->started_at ?? $completedAt),
            ]);
            $this->auditLogService->record('scan.completed', $history, $queue->queuedBy, [], [
                'status' => 'completed',
                'duration_seconds' => $history->duration_seconds,
            ]);
            event(new ScanLifecycleEvent(
                $queue->queued_by === null ? 'scheduled_scan_completed' : 'scan_completed',
                $queue->project,
                $history->fresh(['project', 'queue', 'triggeredBy']),
                $queue->queued_by === null
            ));

            $queue->update([
                'queue_status' => 'completed',
                'failure_category' => null,
                'completed_at' => $completedAt,
                'remarks' => 'Scan completed successfully.',
            ]);
        } catch (Throwable $exception) {
            $completedAt = now();
            $failureCategory = $this->failureCategoryFor($exception);
            $sanitizedMessage = $this->scanLogService->maskSecrets($exception->getMessage(), $this->secrets($queue->project));

            if ($history) {
                $this->scanLogService->write($history, 'error', $sanitizedMessage);

                $history->update([
                    'status' => 'failed',
                    'failure_category' => $failureCategory,
                    'completed_at' => $completedAt,
                    'duration_seconds' => $history->started_at ? $completedAt->diffInSeconds($history->started_at) : null,
                ]);
                $this->auditLogService->record('scan.failed', $history, $queue->queuedBy, [], [
                    'status' => 'failed',
                    'failure_category' => $failureCategory,
                    'remarks' => $sanitizedMessage,
                ]);
                event(new ScanLifecycleEvent(
                    $queue->queued_by === null ? 'scheduled_scan_failed' : 'scan_failed',
                    $queue->project,
                    $history->fresh(['project', 'queue', 'triggeredBy']),
                    $queue->queued_by === null
                ));
            }

            $queue->update([
                'queue_status' => 'failed',
                'failure_category' => $failureCategory,
                'completed_at' => $completedAt,
                'remarks' => $sanitizedMessage,
            ]);

            $this->auditLogService->record('scan.failed', $queue, $queue->queuedBy, [], [
                'failure_category' => $failureCategory,
                'remarks' => $sanitizedMessage,
            ]);

            throw new \RuntimeException($sanitizedMessage);
        } finally {
            if ($workspace) {
                try {
                    $this->cleanupService->deleteWorkspace($workspace['workspace_path']);
                    $this->auditLogService->record('workspace.deleted', $queue, $queue->queuedBy, [], [
                        'workspace_path' => $workspace['workspace_path'],
                    ]);
                } catch (Throwable $cleanupException) {
                    $cleanupMessage = $this->scanLogService->maskSecrets(
                        'cleanup_failed: '.$cleanupException->getMessage(),
                        $this->secrets($queue->project)
                    );

                    if ($history) {
                        $this->scanLogService->write($history, 'error', $cleanupMessage);
                        $history->update([
                            'status' => 'failed',
                            'failure_category' => 'cleanup_failed',
                            'completed_at' => now(),
                            'duration_seconds' => $history->started_at ? now()->diffInSeconds($history->started_at) : null,
                        ]);
                    }

                    $queue->update([
                        'queue_status' => 'failed',
                        'failure_category' => 'cleanup_failed',
                        'completed_at' => now(),
                        'remarks' => $cleanupMessage,
                    ]);

                    $this->auditLogService->record('workspace.cleanup_failed', $queue, $queue->queuedBy, [], [
                        'workspace_path' => $workspace['workspace_path'],
                        'failure_category' => 'cleanup_failed',
                        'remarks' => $cleanupMessage,
                    ]);

                    throw $cleanupException;
                }

                if ($history) {
                    $this->scanLogService->write($history, 'info', 'Workspace cleanup completed.', [
                        'workspace_path' => $workspace['workspace_path'],
                    ]);
                }
            }
        }
    }

    /**
     * @return array<int, string>
     */
    private function secrets(Project $project): array
    {
        return array_values(array_filter([
            (string) $project->gitConnection->token,
            (string) $project->gitConnection->ssh_private_key,
            (string) $project->sonarConnection->token,
        ]));
    }

    private function failureCategoryFor(Throwable $exception): string
    {
        $message = strtolower($exception->getMessage());

        foreach (ScanQueueManagementService::FAILURE_CATEGORIES as $category) {
            if (str_contains($message, $category)) {
                return $category;
            }
        }

        return 'unknown';
    }
}
