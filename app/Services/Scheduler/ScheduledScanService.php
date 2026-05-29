<?php

namespace App\Services\Scheduler;

use App\Events\SystemWarningEvent;
use App\Jobs\RunProjectScanJob;
use App\Models\Project;
use App\Models\ScanQueue;
use App\Models\ScanScheduleLog;
use App\Services\Audit\AuditLogService;
use App\Services\System\SystemHealthService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

class ScheduledScanService
{
    public function __construct(
        private readonly SystemHealthService $systemHealthService,
        private readonly AuditLogService $auditLogService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function preview(?CarbonInterface $scheduledAt = null): array
    {
        $scheduledAt ??= now();

        $eligibleProjects = $this->eligibleProjects($scheduledAt);
        $duplicateIds = $this->duplicateProjectIds($eligibleProjects);

        return [
            'scheduled_at' => $scheduledAt,
            'runtime_available' => $this->runtimeAvailable(),
            'eligible_projects' => $eligibleProjects->map(function (Project $project) use ($duplicateIds) {
                $isDuplicate = in_array($project->id, $duplicateIds, true);

                return [
                    'project' => $project,
                    'duplicate_pending' => $isDuplicate,
                    'will_queue' => ! $isDuplicate,
                ];
            })->all(),
            'eligible_projects_count' => $eligibleProjects->count(),
            'queued_projects_count' => $eligibleProjects->count() - count($duplicateIds),
            'skipped_projects_count' => count($duplicateIds),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function dispatchScheduledScans(bool $dryRun = false, ?CarbonInterface $scheduledAt = null): array
    {
        $preview = $this->preview($scheduledAt);

        if ($dryRun) {
            return [
                ...$preview,
                'dry_run' => true,
                'status' => 'dry-run',
                'dispatched_jobs_count' => 0,
            ];
        }

        $dispatchedJobsCount = 0;
        $runtimeAvailable = $preview['runtime_available'];

        foreach ($preview['eligible_projects'] as $item) {
            if (! $item['will_queue']) {
                continue;
            }

            /** @var Project $project */
            $project = $item['project'];

            $queue = ScanQueue::query()->create([
                'project_id' => $project->id,
                'branch_name' => $project->branch_name,
                'scan_type' => $project->scan_type,
                'queue_status' => 'pending',
                'queued_by' => null,
                'remarks' => $runtimeAvailable
                    ? 'Queued by scheduler automation.'
                    : 'Queued by scheduler automation. Job dispatch skipped because scan runtime is unavailable.',
            ]);

            $this->auditLogService->record('scan.queued', $queue, auth()->user(), [], [
                'project_id' => $project->id,
                'branch_name' => $project->branch_name,
                'scan_type' => $project->scan_type,
                'queue_status' => 'pending',
                'source' => 'scheduler',
            ]);

            if ($runtimeAvailable) {
                RunProjectScanJob::dispatch($queue->id)->onQueue('scans');
                $dispatchedJobsCount++;
            }
        }

        $status = match (true) {
            $preview['eligible_projects_count'] === 0 => 'no-projects',
            $preview['queued_projects_count'] === 0 => 'skipped',
            $runtimeAvailable => 'queued',
            default => 'queued_without_dispatch',
        };

        $log = ScanScheduleLog::query()->create([
            'scheduled_at' => $preview['scheduled_at'],
            'eligible_projects_count' => $preview['eligible_projects_count'],
            'queued_projects_count' => $preview['queued_projects_count'],
            'skipped_projects_count' => $preview['skipped_projects_count'],
            'status' => $status,
        ]);

        $this->auditLogService->record('scheduler.executed', $log, auth()->user(), [], [
            'scheduled_at' => $preview['scheduled_at']->toDateTimeString(),
            'eligible_projects_count' => $preview['eligible_projects_count'],
            'queued_projects_count' => $preview['queued_projects_count'],
            'skipped_projects_count' => $preview['skipped_projects_count'],
            'status' => $status,
            'dry_run' => false,
        ]);

        if (in_array($status, ['queued_without_dispatch', 'skipped'], true)) {
            event(new SystemWarningEvent(
                'scheduler_failed',
                ['Scheduled dispatch completed with status: '.$status.'. Review scheduler preview and runtime readiness.'],
                route('scheduler.index')
            ));
        }

        return [
            ...$preview,
            'dry_run' => false,
            'status' => $status,
            'dispatched_jobs_count' => $dispatchedJobsCount,
        ];
    }

    public function nextScheduledProject(?CarbonInterface $from = null): ?array
    {
        $from ??= now();

        return Project::query()
            ->where('active', true)
            ->where('schedule_enabled', true)
            ->whereNotNull('schedule_time')
            ->get()
            ->map(function (Project $project) use ($from) {
                $nextRunAt = $this->nextRunAt($project, $from);

                return $nextRunAt ? [
                    'project' => $project,
                    'next_run_at' => $nextRunAt,
                ] : null;
            })
            ->filter()
            ->sortBy(fn (array $item) => $item['next_run_at']->timestamp)
            ->first();
    }

    public function scheduledProjectsCount(): int
    {
        return Project::query()
            ->where('active', true)
            ->where('schedule_enabled', true)
            ->count();
    }

    public function lastSchedulerRun(): ?ScanScheduleLog
    {
        return ScanScheduleLog::query()
            ->latest('scheduled_at')
            ->first();
    }

    private function runtimeAvailable(): bool
    {
        $runtimeReady = collect($this->systemHealthService->runtimeChecks())
            ->every(fn (array $item) => $item['status'] === true);

        $queueReady = collect($this->systemHealthService->queueChecks())
            ->every(fn (array $item) => $item['status'] === true);

        return $runtimeReady && $queueReady;
    }

    /**
     * @return Collection<int, Project>
     */
    private function eligibleProjects(CarbonInterface $scheduledAt): Collection
    {
        return Project::query()
            ->with(['gitConnection', 'sonarConnection'])
            ->where('active', true)
            ->where('schedule_enabled', true)
            ->whereIn('schedule_frequency', ['daily', 'weekly'])
            ->whereTime('schedule_time', $scheduledAt->format('H:i:00'))
            ->get()
            ->filter(function (Project $project) use ($scheduledAt) {
                if ($project->schedule_frequency === 'weekly') {
                    return filled($project->schedule_day)
                        && strtolower((string) $project->schedule_day) === strtolower($scheduledAt->englishDayOfWeek);
                }

                return true;
            })
            ->values();
    }

    /**
     * @param Collection<int, Project> $projects
     * @return array<int, int>
     */
    private function duplicateProjectIds(Collection $projects): array
    {
        if ($projects->isEmpty()) {
            return [];
        }

        return ScanQueue::query()
            ->whereIn('project_id', $projects->pluck('id'))
            ->whereIn('queue_status', ['pending', 'running'])
            ->get()
            ->filter(function (ScanQueue $queue) use ($projects) {
                $project = $projects->firstWhere('id', $queue->project_id);

                return $project !== null && $queue->branch_name === $project->branch_name;
            })
            ->pluck('project_id')
            ->unique()
            ->values()
            ->all();
    }

    private function nextRunAt(Project $project, CarbonInterface $from): ?CarbonInterface
    {
        if (! $project->schedule_enabled || blank($project->schedule_time)) {
            return null;
        }

        [$hour, $minute] = array_map('intval', explode(':', (string) $project->schedule_time));
        $candidate = $from->copy()->setTime($hour, $minute, 0);

        if ($project->schedule_frequency === 'daily') {
            if ($candidate->lessThanOrEqualTo($from)) {
                $candidate = $candidate->addDay();
            }

            return $candidate;
        }

        if ($project->schedule_frequency !== 'weekly' || blank($project->schedule_day)) {
            return null;
        }

        $dayMap = [
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
            'sunday' => 0,
        ];

        $targetDay = $dayMap[strtolower((string) $project->schedule_day)] ?? null;

        if ($targetDay === null) {
            return null;
        }

        $daysUntil = ($targetDay - (int) $candidate->dayOfWeek + 7) % 7;
        $candidate = $candidate->addDays($daysUntil);

        if ($candidate->lessThanOrEqualTo($from)) {
            $candidate = $candidate->addWeek();
        }

        return $candidate;
    }
}
