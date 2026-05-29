<?php

namespace App\Jobs;

use App\Models\ScanQueue;
use App\Services\Scan\ScanQueueManagementService;
use App\Services\Scan\ScanOrchestratorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\TimeoutExceededException;
use Throwable;

class RunProjectScanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout;

    public int $tries = 1;

    public function __construct(
        public readonly int $queueId,
    ) {
        $this->timeout = config('scan.timeout_seconds');
        $this->onQueue('scans');
    }

    public function handle(ScanOrchestratorService $scanOrchestratorService): void
    {
        /** @var ScanQueue $queue */
        $queue = ScanQueue::query()
            ->with(['project.gitConnection', 'project.sonarConnection', 'queuedBy'])
            ->findOrFail($this->queueId);

        $workerId = (string) ($this->job?->getJobId() ?? uniqid('scan-worker-', true));

        if (! $scanOrchestratorService->canRunNow()) {
            $this->release(30);

            return;
        }

        $scanOrchestratorService->run($queue, $workerId);
    }

    public function failed(?Throwable $exception): void
    {
        $queue = ScanQueue::query()
            ->with('history.logs')
            ->find($this->queueId);

        if (! $queue || in_array($queue->queue_status, ['completed', 'failed', 'cancelled'], true)) {
            return;
        }

        $isTimeout = $exception instanceof TimeoutExceededException;

        app(ScanQueueManagementService::class)->markFailed(
            $queue,
            $isTimeout ? 'timeout' : 'unknown',
            $isTimeout
                ? 'Queue worker timeout interrupted the scan job.'
                : 'The scan job failed before completion.',
            auditAction: $isTimeout ? 'scan.timeout_recovered' : null,
            auditDescription: $isTimeout ? 'Running scan marked failed due to queue worker timeout.' : null
        );
    }
}
