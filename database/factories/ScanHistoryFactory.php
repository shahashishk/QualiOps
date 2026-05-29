<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ScanHistory;
use App\Models\ScanQueue;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScanHistory>
 */
class ScanHistoryFactory extends Factory
{
    protected $model = ScanHistory::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'branch_name' => 'uat',
            'scan_type' => 'auto',
            'triggered_by' => User::factory(),
            'queue_id' => null,
            'sonar_project_key' => fake()->unique()->slug(),
            'sonar_dashboard_url' => null,
            'status' => 'pending',
            'failure_category' => null,
            'started_at' => null,
            'completed_at' => null,
            'duration_seconds' => null,
            'total_bugs' => null,
            'total_vulnerabilities' => null,
            'total_code_smells' => null,
            'coverage' => null,
            'duplicated_lines' => null,
        ];
    }
}
