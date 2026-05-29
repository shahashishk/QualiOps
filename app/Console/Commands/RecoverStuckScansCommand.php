<?php

namespace App\Console\Commands;

use App\Services\Scan\ScanQueueManagementService;
use Illuminate\Console\Command;

class RecoverStuckScansCommand extends Command
{
    protected $signature = 'scans:recover-stuck
        {--dry-run : Preview stuck running scans without changing queue records}
        {--timeout-minutes= : Override the configured scan timeout for recovery checks}';

    protected $description = 'Mark stuck running scans as failed based on the configured or supplied timeout.';

    public function handle(ScanQueueManagementService $scanQueueManagementService): int
    {
        $timeoutMinutes = $this->option('timeout-minutes');
        $parsedTimeout = $timeoutMinutes !== null ? max(1, (int) $timeoutMinutes) : null;

        $report = $scanQueueManagementService->recoverStuckScans(
            dryRun: (bool) $this->option('dry-run'),
            timeoutMinutes: $parsedTimeout,
        );

        $this->info($report['dry_run'] ? 'Stuck Scan Recovery Preview' : 'Stuck Scan Recovery');
        $this->line('Timeout minutes: '.$report['timeout_minutes']);
        $this->newLine();

        $this->table(
            ['Queue ID', 'Project', 'Branch', 'Started At', 'Current Status'],
            collect($report['queues'])
                ->map(fn ($queue) => [
                    $queue->id,
                    $queue->project->project_name,
                    $queue->branch_name,
                    $queue->started_at?->format('d M Y H:i') ?? 'N/A',
                    $queue->queue_status,
                ])
                ->whenEmpty(fn ($collection) => collect([['-', 'No stuck scans detected', '-', '-', '-']]))
                ->all()
        );

        $this->newLine();
        $this->line('Affected scans: '.$report['count']);

        return self::SUCCESS;
    }
}
