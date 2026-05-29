<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Services\Sonar\SonarMetricsService;
use Illuminate\Console\Command;

class SyncSonarMetricsCommand extends Command
{
    protected $signature = 'sonar:sync-metrics
        {--project_id= : Synchronize metrics for a single project id}
        {--all : Synchronize metrics for all active projects}
        {--dry-run : Fetch metrics without saving local snapshots}';

    protected $description = 'Synchronize SonarQube metrics into local snapshot storage.';

    public function handle(SonarMetricsService $sonarMetricsService): int
    {
        $projectId = $this->option('project_id');
        $syncAll = (bool) $this->option('all');
        $dryRun = (bool) $this->option('dry-run');

        if (! $projectId && ! $syncAll) {
            $this->error('Use --project_id= or --all to select synchronization scope.');

            return self::FAILURE;
        }

        $projects = Project::query()
            ->with('sonarConnection')
            ->when($projectId, fn ($query) => $query->whereKey($projectId))
            ->when($syncAll, fn ($query) => $query->where('active', true))
            ->orderBy('project_name')
            ->get();

        if ($projects->isEmpty()) {
            $this->error('No matching projects were found for SonarQube metric synchronization.');

            return self::FAILURE;
        }

        $rows = [];
        $failed = false;

        foreach ($projects as $project) {
            $result = $sonarMetricsService->sync($project, $dryRun);
            $payload = $result['payload'] ?? [];

            $rows[] = [
                $project->project_name,
                $project->sonar_project_key,
                $result['success'] ? 'OK' : 'FAIL',
                $payload['quality_gate_status'] ?? '-',
                $payload['bugs'] ?? '-',
                $payload['vulnerabilities'] ?? '-',
                $payload['code_smells'] ?? '-',
                $result['message'],
            ];

            if (! $result['success']) {
                $failed = true;
            }
        }

        $this->info($dryRun ? 'SonarQube Metrics Dry Run' : 'SonarQube Metrics Synchronization');
        $this->table(
            ['Project', 'Project Key', 'Status', 'Quality Gate', 'Bugs', 'Vulnerabilities', 'Code Smells', 'Message'],
            $rows
        );

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
