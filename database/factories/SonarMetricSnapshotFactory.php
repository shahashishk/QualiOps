<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\SonarMetricSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SonarMetricSnapshot>
 */
class SonarMetricSnapshotFactory extends Factory
{
    protected $model = SonarMetricSnapshot::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'sonar_project_key' => fake()->unique()->slug(),
            'bugs' => fake()->numberBetween(0, 20),
            'vulnerabilities' => fake()->numberBetween(0, 10),
            'code_smells' => fake()->numberBetween(0, 100),
            'security_hotspots' => fake()->numberBetween(0, 15),
            'coverage' => fake()->randomFloat(2, 0, 100),
            'duplicated_lines_density' => fake()->randomFloat(2, 0, 20),
            'reliability_rating' => 'A',
            'security_rating' => 'A',
            'maintainability_rating' => 'A',
            'technical_debt' => fake()->numberBetween(0, 1000),
            'quality_gate_status' => 'OK',
            'synced_at' => now(),
        ];
    }
}
