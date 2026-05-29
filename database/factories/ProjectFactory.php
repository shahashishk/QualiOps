<?php

namespace Database\Factories;

use App\Models\GitConnection;
use App\Models\Project;
use App\Models\SonarConnection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'project_name' => fake()->unique()->company().' Portal',
            'description' => fake()->sentence(),
            'git_connection_id' => GitConnection::factory(),
            'git_url' => 'https://github.com/acme/example-repo.git',
            'branch_name' => 'uat',
            'sonar_connection_id' => SonarConnection::factory(),
            'sonar_project_key' => fake()->unique()->slug(),
            'sonar_project_name' => fake()->words(3, true),
            'scan_type' => 'auto',
            'schedule_enabled' => false,
            'schedule_frequency' => 'manual',
            'schedule_day' => null,
            'schedule_time' => null,
            'active' => true,
        ];
    }
}
