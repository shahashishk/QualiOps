<?php

namespace App\Console\Commands;

use App\Services\Scheduler\ScheduledScanService;
use Illuminate\Console\Command;

class DispatchScheduledScansCommand extends Command
{
    protected $signature = 'scans:dispatch-scheduled {--dry-run : Preview eligible projects without creating queue records or dispatching jobs}';

    protected $description = 'Queue scheduled project scans and dispatch worker jobs when runtime is available.';

    public function handle(ScheduledScanService $scheduledScanService): int
    {
        $report = $scheduledScanService->dispatchScheduledScans(
            dryRun: (bool) $this->option('dry-run'),
        );

        $this->info($report['dry_run'] ? 'Scheduled Scan Preview' : 'Scheduled Scan Dispatch');
        $this->line('Scheduled at: '.$report['scheduled_at']->toDateTimeString());
        $this->line('Runtime available: '.($report['runtime_available'] ? 'yes' : 'no'));
        $this->newLine();

        $this->table(
            ['Project', 'Branch', 'Frequency', 'Schedule', 'Action'],
            collect($report['eligible_projects'])
                ->map(function (array $item) {
                    /** @var \App\Models\Project $project */
                    $project = $item['project'];

                    $scheduleLabel = $project->schedule_frequency === 'weekly'
                        ? str($project->schedule_day)->title().' @ '.str($project->schedule_time)->substr(0, 5)
                        : str($project->schedule_frequency)->title().' @ '.str($project->schedule_time)->substr(0, 5);

                    return [
                        $project->project_name,
                        $project->branch_name,
                        str($project->schedule_frequency)->title(),
                        $scheduleLabel,
                        $item['will_queue'] ? ($item['duplicate_pending'] ? 'Skip duplicate' : 'Queue') : 'Skip duplicate',
                    ];
                })
                ->whenEmpty(fn ($collection) => collect([['No eligible projects', '-', '-', '-', 'No action']]))
                ->all()
        );

        $this->newLine();
        $this->line('Eligible projects: '.$report['eligible_projects_count']);
        $this->line('Queued projects: '.$report['queued_projects_count']);
        $this->line('Skipped projects: '.$report['skipped_projects_count']);
        $this->line('Dispatched jobs: '.$report['dispatched_jobs_count']);
        $this->line('Status: '.$report['status']);

        return self::SUCCESS;
    }
}
