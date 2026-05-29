<?php

namespace App\Services\Scan;

use App\Exceptions\DuplicateActiveScanException;
use App\Jobs\RunProjectScanJob;
use App\Models\ScanQueue;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ScanQueueManagementService
{
    public const FAILURE_CATEGORIES = [
        'git_clone_failed',
        'stack_detection_failed',
        'scanner_failed',
        'sonar_upload_failed',
        'timeout',
        'cleanup_failed',
        'unknown',
    ];

    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly ScanLogService $scanLogService,
    ) {
    }

    public function cancel(ScanQueue $queue, User $user): void
    {
        if ($queue->queue_status !== 'pending') {
            throw new \RuntimeException('Only pending scans can be cancelled.');
        }

        DB::transaction(function () use ($queue, $user) {
            $oldValues = [
                'queue_status' => $queue->queue_status,
                'failure_category' => $queue->failure_category,
            ];
            $queue->update([
                'queue_status' => 'cancelled',
                'completed_at' => now(),
                'remarks' => 'Scan cancelled from the queue controls.',
            ]);

            $this->auditLogService->record(
                'scan.cancelled',
                $queue,
                $user,
                $oldValues,
                [
                    'queue_status' => 'cancelled',
                    'project_id' => $queue->project_id,
                    'branch_name' => $queue->branch_name,
                ]
            );
        });
    }

    public function retryFailed(ScanQueue $queue, User $user): ScanQueue
    {
        if ($queue->queue_status !== 'failed') {
            throw new \RuntimeException('Only failed scans can be retried.');
        }

        return $this->createRequeuedQueue($queue, $user, 'scan.retried', 'Failed scan re-queued for retry.');
    }

    public function requeue(ScanQueue $queue, User $user): ScanQueue
    {
        if (in_array($queue->queue_status, ['pending', 'running'], true)) {
            throw new \RuntimeException('Pending or running scans cannot be requeued.');
        }

        return $this->createRequeuedQueue($queue, $user, 'scan.requeued', 'Scan queue entry requeued.');
    }

    /**
     * @return array<string, mixed>
     */
    public function recoverStuckScans(bool $dryRun = false, ?int $timeoutMinutes = null, ?User $user = null): array
    {
        $timeoutMinutes ??= max(1, (int) ceil(config('scan.timeout_seconds', 1800) / 60));
        $stuckQueues = $this->stuckRunningQuery($timeoutMinutes)
            ->with(['project', 'history.logs'])
            ->get();

        if ($dryRun) {
            return [
                'dry_run' => true,
                'timeout_minutes' => $timeoutMinutes,
                'queues' => $stuckQueues,
                'count' => $stuckQueues->count(),
            ];
        }

        foreach ($stuckQueues as $queue) {
            $this->markTimedOut($queue, $timeoutMinutes, $user);
        }

        return [
            'dry_run' => false,
            'timeout_minutes' => $timeoutMinutes,
            'queues' => $stuckQueues,
            'count' => $stuckQueues->count(),
        ];
    }

    public function markTimedOut(ScanQueue $queue, int $timeoutMinutes, ?User $user = null): void
    {
        if ($queue->queue_status !== 'running') {
            throw new \RuntimeException('Only running scans can be recovered as timed out.');
        }

        $this->markFailed(
            $queue,
            'timeout',
            'Scan exceeded the configured timeout of '.$timeoutMinutes.' minute(s).',
            $user,
            'scan.timeout_recovered',
            'Running scan marked failed due to timeout.',
            ['timeout_minutes' => $timeoutMinutes]
        );
    }

    public function assertNoActiveDuplicate(int $projectId, string $branchName, ?int $ignoreQueueId = null): void
    {
        $exists = ScanQueue::query()
            ->where('project_id', $projectId)
            ->where('branch_name', $branchName)
            ->whereIn('queue_status', ['pending', 'running'])
            ->when($ignoreQueueId, fn (Builder $query) => $query->whereKeyNot($ignoreQueueId))
            ->exists();

        if ($exists) {
            throw new DuplicateActiveScanException('A pending or running scan already exists for this project and branch.');
        }
    }

    /**
     * @return Collection<int, string>
     */
    public function failureCategories(): Collection
    {
        return collect(self::FAILURE_CATEGORIES);
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function markFailed(
        ScanQueue $queue,
        string $failureCategory,
        string $message,
        ?User $user = null,
        ?string $auditAction = null,
        ?string $auditDescription = null,
        array $metadata = []
    ): void {
        DB::transaction(function () use ($queue, $failureCategory, $message, $user, $auditAction, $auditDescription, $metadata) {
            $oldValues = [
                'queue_status' => $queue->queue_status,
                'failure_category' => $queue->failure_category,
                'remarks' => $queue->remarks,
            ];
            $queue->update([
                'queue_status' => 'failed',
                'failure_category' => $failureCategory,
                'completed_at' => now(),
                'remarks' => $message,
            ]);

            if ($queue->history) {
                $queue->history->update([
                    'status' => 'failed',
                    'failure_category' => $failureCategory,
                    'completed_at' => now(),
                    'duration_seconds' => $queue->history->started_at
                        ? now()->diffInSeconds($queue->history->started_at)
                        : $queue->history->duration_seconds,
                ]);

                $this->scanLogService->write(
                    $queue->history,
                    'error',
                    $failureCategory === 'timeout'
                        ? 'Scan timed out and was marked as failed by queue recovery.'
                        : $message,
                    $metadata
                );
            }

            if ($auditAction) {
                $this->auditLogService->record(
                    $auditAction,
                    $queue,
                    $user,
                    $oldValues,
                    array_merge([
                        'queue_status' => 'failed',
                        'project_id' => $queue->project_id,
                        'branch_name' => $queue->branch_name,
                        'failure_category' => $failureCategory,
                        'remarks' => $message,
                    ], $metadata)
                );
            }
        });
    }

    private function createRequeuedQueue(ScanQueue $queue, User $user, string $auditAction, string $description): ScanQueue
    {
        $this->assertNoActiveDuplicate($queue->project_id, $queue->branch_name, $queue->id);

        $newQueue = DB::transaction(function () use ($queue, $user, $auditAction, $description) {
            $newQueue = ScanQueue::query()->create([
                'project_id' => $queue->project_id,
                'branch_name' => $queue->branch_name,
                'scan_type' => $queue->scan_type,
                'queue_status' => 'pending',
                'failure_category' => null,
                'queued_by' => $user->id,
                'remarks' => $description.' Source queue #'.$queue->id.'.',
            ]);

            $this->auditLogService->record(
                $auditAction,
                $newQueue,
                $user,
                [],
                [
                    'source_queue_id' => $queue->id,
                    'project_id' => $queue->project_id,
                    'branch_name' => $queue->branch_name,
                    'queue_status' => 'pending',
                    'remarks' => $description,
                ]
            );

            return $newQueue;
        });

        RunProjectScanJob::dispatch($newQueue->id)->afterCommit();

        return $newQueue;
    }

    private function stuckRunningQuery(int $timeoutMinutes): Builder
    {
        return ScanQueue::query()
            ->where('queue_status', 'running')
            ->whereNotNull('started_at')
            ->where('started_at', '<=', now()->subMinutes($timeoutMinutes));
    }
}
