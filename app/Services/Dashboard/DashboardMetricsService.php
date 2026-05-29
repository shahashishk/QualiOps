<?php

namespace App\Services\Dashboard;

use App\Services\Scheduler\ScheduledScanService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Services\Scan\ScanTrackingService;

class DashboardMetricsService
{
    public function __construct(
        private readonly ScanTrackingService $scanTrackingService,
        private readonly ScheduledScanService $scheduledScanService,
    ) {
    }

    /**
     * Return dashboard card metrics with safe defaults while modules are pending.
     */
    public function getMetrics(): array
    {
        return array_merge([
            'total_projects' => $this->countProjects(),
            'active_projects' => $this->countProjects(true),
            'todays_scans' => $this->countScansForToday(),
            'running_scans' => $this->countScansByStatus('running'),
            'successful_scans' => $this->countScansByStatus('completed'),
            'failed_scans' => $this->countScansByStatus('failed'),
            'total_bugs' => $this->totalLatestMetric('bugs'),
            'total_vulnerabilities' => $this->totalLatestMetric('vulnerabilities'),
            'total_code_smells' => $this->totalLatestMetric('code_smells'),
            'quality_gate_passed_count' => $this->countLatestQualityGateStatus('OK'),
            'quality_gate_failed_count' => $this->countLatestQualityGateFailed(),
            'next_scheduled_scan' => $this->nextScheduledScanLabel(),
            'scheduled_projects_count' => $this->scheduledProjectsCount(),
            'last_scheduler_run' => $this->lastSchedulerRunLabel(),
        ], $this->queueMetrics());
    }

    private function countProjects(?bool $onlyActive = null): int
    {
        if (! Schema::hasTable('projects')) {
            return 0;
        }

        $query = DB::table('projects');

        if ($onlyActive !== null && Schema::hasColumn('projects', 'active')) {
            $query->where('active', $onlyActive);
        }

        return $query->count();
    }

    private function countScansForToday(): int
    {
        if (! Schema::hasTable('scan_histories') || ! Schema::hasColumn('scan_histories', 'created_at')) {
            return 0;
        }

        return DB::table('scan_histories')
            ->whereDate('created_at', now()->toDateString())
            ->count();
    }

    private function countScansByStatus(string $status): int
    {
        if (! Schema::hasTable('scan_histories') || ! Schema::hasColumn('scan_histories', 'status')) {
            return 0;
        }

        return DB::table('scan_histories')
            ->where('status', $status)
            ->count();
    }

    /**
     * @return array<string, int>
     */
    private function queueMetrics(): array
    {
        if (! Schema::hasTable('scan_queue') || ! Schema::hasTable('scan_histories')) {
            return [
                'pending_queue' => 0,
                'running_queue' => 0,
                'failed_today' => 0,
                'successful_today' => 0,
            ];
        }

        return $this->scanTrackingService->dashboardMetrics();
    }

    private function nextScheduledScanLabel(): string
    {
        $nextRun = $this->scheduledScanService->nextScheduledProject();

        if (! $nextRun) {
            return 'Not scheduled';
        }

        return $nextRun['project']->project_name.' - '.$nextRun['next_run_at']->format('d M H:i');
    }

    private function scheduledProjectsCount(): int
    {
        if (! Schema::hasTable('projects')) {
            return 0;
        }

        return $this->scheduledScanService->scheduledProjectsCount();
    }

    private function lastSchedulerRunLabel(): string
    {
        if (! Schema::hasTable('scan_schedule_logs')) {
            return 'Not run yet';
        }

        return $this->scheduledScanService->lastSchedulerRun()?->scheduled_at?->format('d M H:i') ?? 'Not run yet';
    }

    private function totalLatestMetric(string $column): int
    {
        if (! Schema::hasTable('sonar_metric_snapshots')) {
            return 0;
        }

        return (int) $this->latestSonarSnapshotQuery()
            ->sum('latest_snapshots.'.$column);
    }

    private function countLatestQualityGateStatus(string $status): int
    {
        if (! Schema::hasTable('sonar_metric_snapshots')) {
            return 0;
        }

        return $this->latestSonarSnapshotQuery()
            ->where('latest_snapshots.quality_gate_status', $status)
            ->count();
    }

    private function countLatestQualityGateFailed(): int
    {
        if (! Schema::hasTable('sonar_metric_snapshots')) {
            return 0;
        }

        return $this->latestSonarSnapshotQuery()
            ->whereNotNull('latest_snapshots.quality_gate_status')
            ->where('latest_snapshots.quality_gate_status', '!=', 'OK')
            ->count();
    }

    private function latestSonarSnapshotQuery()
    {
        $latestIds = DB::table('sonar_metric_snapshots')
            ->selectRaw('MAX(id) as id')
            ->groupBy('project_id');

        return DB::table('sonar_metric_snapshots as latest_snapshots')
            ->joinSub($latestIds, 'latest_snapshot_ids', function ($join) {
                $join->on('latest_snapshot_ids.id', '=', 'latest_snapshots.id');
            });
    }
}
