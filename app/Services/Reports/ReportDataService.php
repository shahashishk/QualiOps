<?php

namespace App\Services\Reports;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportDataService
{
    public const REPORT_TYPES = [
        'project_quality_summary' => 'Project Quality Summary',
        'scan_history' => 'Scan History Report',
        'failed_scans' => 'Failed Scan Report',
        'quality_gate_failed' => 'Quality Gate Failed Report',
        'metrics_snapshot' => 'Metrics Snapshot Report',
    ];

    public function __construct(
        private readonly ReportAccessService $reportAccessService,
    ) {
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{title: string, headers: array<int, string>, rows: array<int, array<int, string|int|float|null>>}
     */
    public function build(string $reportType, array $filters, User $user): array
    {
        return match ($reportType) {
            'project_quality_summary' => $this->projectQualitySummary($filters, $user),
            'scan_history' => $this->scanHistory($filters, $user),
            'failed_scans' => $this->failedScans($filters, $user),
            'quality_gate_failed' => $this->qualityGateFailed($filters, $user),
            'metrics_snapshot' => $this->metricsSnapshot($filters, $user),
            default => throw new \InvalidArgumentException('Unsupported report type.'),
        };
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{title: string, headers: array<int, string>, rows: array<int, array<int, string|int|float|null>>}
     */
    private function projectQualitySummary(array $filters, User $user): array
    {
        $projectIds = $this->accessibleProjectIds($user);

        $latestIds = DB::table('sonar_metric_snapshots')
            ->selectRaw('MAX(id) as id')
            ->whereIn('project_id', $projectIds)
            ->groupBy('project_id');

        $rows = DB::table('sonar_metric_snapshots as snapshots')
            ->joinSub($latestIds, 'latest_snapshot_ids', fn ($join) => $join->on('latest_snapshot_ids.id', '=', 'snapshots.id'))
            ->join('projects', 'projects.id', '=', 'snapshots.project_id')
            ->when(filled($filters['project_id'] ?? null), fn ($query) => $query->where('projects.id', $filters['project_id']))
            ->when(filled($filters['branch'] ?? null), fn ($query) => $query->where('projects.branch_name', 'like', '%'.$filters['branch'].'%'))
            ->when(filled($filters['quality_gate_status'] ?? null), fn ($query) => $query->where('snapshots.quality_gate_status', $filters['quality_gate_status']))
            ->orderBy('projects.project_name')
            ->get([
                'projects.project_name',
                'projects.branch_name',
                'snapshots.quality_gate_status',
                'snapshots.bugs',
                'snapshots.vulnerabilities',
                'snapshots.code_smells',
                'snapshots.coverage',
                'snapshots.duplicated_lines_density',
                'snapshots.synced_at',
            ]);

        return [
            'title' => self::REPORT_TYPES['project_quality_summary'],
            'headers' => ['Project', 'Branch', 'Quality Gate', 'Bugs', 'Vulnerabilities', 'Code Smells', 'Coverage', 'Duplication', 'Synced At'],
            'rows' => $rows->map(fn ($row) => [
                $row->project_name,
                $row->branch_name,
                $row->quality_gate_status,
                $row->bugs,
                $row->vulnerabilities,
                $row->code_smells,
                $row->coverage,
                $row->duplicated_lines_density,
                (string) $row->synced_at,
            ])->all(),
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{title: string, headers: array<int, string>, rows: array<int, array<int, string|int|float|null>>}
     */
    private function scanHistory(array $filters, User $user): array
    {
        $rows = $this->scanHistoryBaseQuery($filters, $user)
            ->orderByDesc('scan_histories.created_at')
            ->get([
                'projects.project_name',
                'scan_histories.branch_name',
                'scan_histories.scan_type',
                'scan_histories.status',
                'users.name as triggered_by_name',
                'scan_histories.failure_category',
                'scan_histories.started_at',
                'scan_histories.completed_at',
            ]);

        return [
            'title' => self::REPORT_TYPES['scan_history'],
            'headers' => ['Project', 'Branch', 'Scan Type', 'Status', 'Triggered By', 'Failure Category', 'Started At', 'Completed At'],
            'rows' => $rows->map(fn ($row) => [
                $row->project_name,
                $row->branch_name,
                $row->scan_type,
                $row->status,
                $row->triggered_by_name ?? 'Scheduler automation',
                $row->failure_category,
                (string) $row->started_at,
                (string) $row->completed_at,
            ])->all(),
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{title: string, headers: array<int, string>, rows: array<int, array<int, string|int|float|null>>}
     */
    private function failedScans(array $filters, User $user): array
    {
        $filters['scan_status'] = 'failed';

        $rows = $this->scanHistoryBaseQuery($filters, $user)
            ->orderByDesc('scan_histories.created_at')
            ->get([
                'projects.project_name',
                'scan_histories.branch_name',
                'scan_histories.scan_type',
                'users.name as triggered_by_name',
                'scan_histories.failure_category',
                'scan_histories.completed_at',
                'scan_histories.sonar_project_key',
            ]);

        return [
            'title' => self::REPORT_TYPES['failed_scans'],
            'headers' => ['Project', 'Branch', 'Scan Type', 'Triggered By', 'Failure Category', 'Completed At', 'Sonar Project Key'],
            'rows' => $rows->map(fn ($row) => [
                $row->project_name,
                $row->branch_name,
                $row->scan_type,
                $row->triggered_by_name ?? 'Scheduler automation',
                $row->failure_category,
                (string) $row->completed_at,
                $row->sonar_project_key,
            ])->all(),
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{title: string, headers: array<int, string>, rows: array<int, array<int, string|int|float|null>>}
     */
    private function qualityGateFailed(array $filters, User $user): array
    {
        $projectIds = $this->accessibleProjectIds($user);

        $rows = DB::table('sonar_metric_snapshots')
            ->join('projects', 'projects.id', '=', 'sonar_metric_snapshots.project_id')
            ->whereIn('projects.id', $projectIds)
            ->whereNotNull('sonar_metric_snapshots.quality_gate_status')
            ->where('sonar_metric_snapshots.quality_gate_status', '!=', 'OK')
            ->when(filled($filters['project_id'] ?? null), fn ($query) => $query->where('projects.id', $filters['project_id']))
            ->when(filled($filters['branch'] ?? null), fn ($query) => $query->where('projects.branch_name', 'like', '%'.$filters['branch'].'%'))
            ->when(filled($filters['quality_gate_status'] ?? null), fn ($query) => $query->where('sonar_metric_snapshots.quality_gate_status', $filters['quality_gate_status']))
            ->when(filled($filters['date_from'] ?? null), fn ($query) => $query->whereDate('sonar_metric_snapshots.synced_at', '>=', $filters['date_from']))
            ->when(filled($filters['date_to'] ?? null), fn ($query) => $query->whereDate('sonar_metric_snapshots.synced_at', '<=', $filters['date_to']))
            ->orderByDesc('sonar_metric_snapshots.synced_at')
            ->get([
                'projects.project_name',
                'projects.branch_name',
                'sonar_metric_snapshots.quality_gate_status',
                'sonar_metric_snapshots.bugs',
                'sonar_metric_snapshots.vulnerabilities',
                'sonar_metric_snapshots.code_smells',
                'sonar_metric_snapshots.coverage',
                'sonar_metric_snapshots.synced_at',
            ]);

        return [
            'title' => self::REPORT_TYPES['quality_gate_failed'],
            'headers' => ['Project', 'Branch', 'Quality Gate', 'Bugs', 'Vulnerabilities', 'Code Smells', 'Coverage', 'Synced At'],
            'rows' => $rows->map(fn ($row) => [
                $row->project_name,
                $row->branch_name,
                $row->quality_gate_status,
                $row->bugs,
                $row->vulnerabilities,
                $row->code_smells,
                $row->coverage,
                (string) $row->synced_at,
            ])->all(),
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{title: string, headers: array<int, string>, rows: array<int, array<int, string|int|float|null>>}
     */
    private function metricsSnapshot(array $filters, User $user): array
    {
        $projectIds = $this->accessibleProjectIds($user);

        $rows = DB::table('sonar_metric_snapshots')
            ->join('projects', 'projects.id', '=', 'sonar_metric_snapshots.project_id')
            ->whereIn('projects.id', $projectIds)
            ->when(filled($filters['project_id'] ?? null), fn ($query) => $query->where('projects.id', $filters['project_id']))
            ->when(filled($filters['branch'] ?? null), fn ($query) => $query->where('projects.branch_name', 'like', '%'.$filters['branch'].'%'))
            ->when(filled($filters['quality_gate_status'] ?? null), fn ($query) => $query->where('sonar_metric_snapshots.quality_gate_status', $filters['quality_gate_status']))
            ->when(filled($filters['date_from'] ?? null), fn ($query) => $query->whereDate('sonar_metric_snapshots.synced_at', '>=', $filters['date_from']))
            ->when(filled($filters['date_to'] ?? null), fn ($query) => $query->whereDate('sonar_metric_snapshots.synced_at', '<=', $filters['date_to']))
            ->orderByDesc('sonar_metric_snapshots.synced_at')
            ->get([
                'projects.project_name',
                'projects.branch_name',
                'sonar_metric_snapshots.sonar_project_key',
                'sonar_metric_snapshots.quality_gate_status',
                'sonar_metric_snapshots.bugs',
                'sonar_metric_snapshots.vulnerabilities',
                'sonar_metric_snapshots.code_smells',
                'sonar_metric_snapshots.security_hotspots',
                'sonar_metric_snapshots.coverage',
                'sonar_metric_snapshots.duplicated_lines_density',
                'sonar_metric_snapshots.reliability_rating',
                'sonar_metric_snapshots.security_rating',
                'sonar_metric_snapshots.maintainability_rating',
                'sonar_metric_snapshots.technical_debt',
                'sonar_metric_snapshots.synced_at',
            ]);

        return [
            'title' => self::REPORT_TYPES['metrics_snapshot'],
            'headers' => ['Project', 'Branch', 'Sonar Key', 'Quality Gate', 'Bugs', 'Vulnerabilities', 'Code Smells', 'Hotspots', 'Coverage', 'Duplication', 'Reliability', 'Security', 'Maintainability', 'Technical Debt', 'Synced At'],
            'rows' => $rows->map(fn ($row) => [
                $row->project_name,
                $row->branch_name,
                $row->sonar_project_key,
                $row->quality_gate_status,
                $row->bugs,
                $row->vulnerabilities,
                $row->code_smells,
                $row->security_hotspots,
                $row->coverage,
                $row->duplicated_lines_density,
                $row->reliability_rating,
                $row->security_rating,
                $row->maintainability_rating,
                $row->technical_debt,
                (string) $row->synced_at,
            ])->all(),
        ];
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function scanHistoryBaseQuery(array $filters, User $user)
    {
        return DB::table('scan_histories')
            ->join('projects', 'projects.id', '=', 'scan_histories.project_id')
            ->leftJoin('users', 'users.id', '=', 'scan_histories.triggered_by')
            ->whereIn('projects.id', $this->accessibleProjectIds($user))
            ->when(filled($filters['project_id'] ?? null), fn ($query) => $query->where('projects.id', $filters['project_id']))
            ->when(filled($filters['branch'] ?? null), fn ($query) => $query->where('scan_histories.branch_name', 'like', '%'.$filters['branch'].'%'))
            ->when(filled($filters['scan_status'] ?? null), fn ($query) => $query->where('scan_histories.status', $filters['scan_status']))
            ->when(filled($filters['date_from'] ?? null), fn ($query) => $query->whereDate('scan_histories.created_at', '>=', $filters['date_from']))
            ->when(filled($filters['date_to'] ?? null), fn ($query) => $query->whereDate('scan_histories.created_at', '<=', $filters['date_to']))
            ->when(filled($filters['triggered_by'] ?? null), fn ($query) => $query->where('scan_histories.triggered_by', $filters['triggered_by']));
    }

    /**
     * @return array<int, int>
     */
    private function accessibleProjectIds(User $user): array
    {
        return $this->reportAccessService
            ->accessibleProjectsQuery($user)
            ->pluck('id')
            ->all();
    }
}
