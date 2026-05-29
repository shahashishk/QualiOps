<?php

namespace App\Services\Scan;

use App\Jobs\RunProjectScanJob;
use App\Models\Project;
use App\Models\ScanHistory;
use App\Models\ScanQueue;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Database\Eloquent\Builder;

class ScanTrackingService
{
    public function __construct(
        private readonly ScanQueueManagementService $scanQueueManagementService,
        private readonly AuditLogService $auditLogService,
    ) {
    }

    public function queueScan(Project $project, User $user): ScanQueue
    {
        $this->scanQueueManagementService->assertNoActiveDuplicate($project->id, $project->branch_name);

        $queue = ScanQueue::query()->create([
            'project_id' => $project->id,
            'branch_name' => $project->branch_name,
            'scan_type' => $project->scan_type,
            'queue_status' => 'pending',
            'failure_category' => null,
            'queued_by' => $user->id,
            'remarks' => 'Queued manually from the governance portal.',
        ]);

        $this->auditLogService->record('scan.queued', $queue, $user, [], [
            'project_id' => $project->id,
            'branch_name' => $project->branch_name,
            'scan_type' => $project->scan_type,
            'queue_status' => 'pending',
        ]);

        RunProjectScanJob::dispatch($queue->id)->afterCommit();

        return $queue;
    }

    public function hasPendingOrRunning(Project $project): bool
    {
        return ScanQueue::query()
            ->where('project_id', $project->id)
            ->where('branch_name', $project->branch_name)
            ->whereIn('queue_status', ['pending', 'running'])
            ->exists();
    }

    public function queueQuery(array $filters): Builder
    {
        return ScanQueue::query()
            ->with(['project', 'queuedBy', 'history'])
            ->when(filled($filters['project_id'] ?? null), fn (Builder $query) => $query->where('project_id', $filters['project_id']))
            ->when(filled($filters['status'] ?? null), fn (Builder $query) => $query->where('queue_status', $filters['status']))
            ->when(filled($filters['failure_category'] ?? null), fn (Builder $query) => $query->where('failure_category', $filters['failure_category']))
            ->when(filled($filters['branch'] ?? null), fn (Builder $query) => $query->where('branch_name', 'like', '%'.$filters['branch'].'%'))
            ->when(filled($filters['date_from'] ?? null), fn (Builder $query) => $query->whereDate('created_at', '>=', $filters['date_from']))
            ->when(filled($filters['date_to'] ?? null), fn (Builder $query) => $query->whereDate('created_at', '<=', $filters['date_to']))
            ->latest();
    }

    public function historyQuery(array $filters): Builder
    {
        return ScanHistory::query()
            ->with(['project', 'queue', 'triggeredBy', 'logs'])
            ->when(filled($filters['project_id'] ?? null), fn (Builder $query) => $query->where('project_id', $filters['project_id']))
            ->when(filled($filters['status'] ?? null), fn (Builder $query) => $query->where('status', $filters['status']))
            ->when(filled($filters['branch'] ?? null), fn (Builder $query) => $query->where('branch_name', 'like', '%'.$filters['branch'].'%'))
            ->when(filled($filters['date_from'] ?? null), fn (Builder $query) => $query->whereDate('created_at', '>=', $filters['date_from']))
            ->when(filled($filters['date_to'] ?? null), fn (Builder $query) => $query->whereDate('created_at', '<=', $filters['date_to']))
            ->latest();
    }

    public function dashboardMetrics(): array
    {
        return [
            'pending_queue' => ScanQueue::query()->where('queue_status', 'pending')->count(),
            'running_queue' => ScanQueue::query()->where('queue_status', 'running')->count(),
            'failed_today' => ScanHistory::query()->where('status', 'failed')->whereDate('created_at', now()->toDateString())->count(),
            'successful_today' => ScanHistory::query()->where('status', 'completed')->whereDate('created_at', now()->toDateString())->count(),
        ];
    }
}
