<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\SonarMetricSnapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SonarMetricsSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_admin_can_sync_sonar_metrics_from_project_page(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $project = Project::factory()->create();

        Http::fake([
            'https://sonarqube.example.com/api/measures/component*' => Http::response([
                'component' => [
                    'measures' => [
                        ['metric' => 'bugs', 'value' => '3'],
                        ['metric' => 'vulnerabilities', 'value' => '2'],
                        ['metric' => 'code_smells', 'value' => '14'],
                        ['metric' => 'security_hotspots', 'value' => '1'],
                        ['metric' => 'coverage', 'value' => '87.15'],
                        ['metric' => 'duplicated_lines_density', 'value' => '2.45'],
                        ['metric' => 'reliability_rating', 'value' => '1.0'],
                        ['metric' => 'security_rating', 'value' => '2.0'],
                        ['metric' => 'sqale_rating', 'value' => '3.0'],
                        ['metric' => 'sqale_index', 'value' => '41'],
                    ],
                ],
            ], 200),
            'https://sonarqube.example.com/api/qualitygates/project_status*' => Http::response([
                'projectStatus' => [
                    'status' => 'OK',
                ],
            ], 200),
        ]);

        $response = $this->actingAs($admin)->post(route('projects.sync-sonar-metrics', $project));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'SonarQube metrics synchronized successfully.');

        $this->assertDatabaseHas('sonar_metric_snapshots', [
            'project_id' => $project->id,
            'sonar_project_key' => $project->sonar_project_key,
            'bugs' => 3,
            'vulnerabilities' => 2,
            'code_smells' => 14,
            'security_hotspots' => 1,
            'reliability_rating' => 'A',
            'security_rating' => 'B',
            'maintainability_rating' => 'C',
            'technical_debt' => 41,
            'quality_gate_status' => 'OK',
        ]);
    }

    public function test_sync_command_dry_run_fetches_metrics_without_saving_snapshot(): void
    {
        $project = Project::factory()->create();

        Http::fake([
            'https://sonarqube.example.com/api/measures/component*' => Http::response([
                'component' => [
                    'measures' => [
                        ['metric' => 'bugs', 'value' => '1'],
                        ['metric' => 'vulnerabilities', 'value' => '0'],
                        ['metric' => 'code_smells', 'value' => '4'],
                    ],
                ],
            ], 200),
            'https://sonarqube.example.com/api/qualitygates/project_status*' => Http::response([
                'projectStatus' => [
                    'status' => 'ERROR',
                ],
            ], 200),
        ]);

        $this->artisan('sonar:sync-metrics', [
            '--project_id' => $project->id,
            '--dry-run' => true,
        ])->expectsOutput('SonarQube Metrics Dry Run')
            ->assertExitCode(0);

        $this->assertDatabaseCount('sonar_metric_snapshots', 0);
    }

    public function test_sync_command_returns_failure_for_invalid_token_without_exposing_secrets(): void
    {
        $project = Project::factory()->create();

        Http::fake([
            'https://sonarqube.example.com/api/measures/component*' => Http::response([], 401),
            'https://sonarqube.example.com/api/qualitygates/project_status*' => Http::response([], 401),
        ]);

        $this->artisan('sonar:sync-metrics', [
            '--project_id' => $project->id,
        ])->expectsOutput('SonarQube Metrics Synchronization')
            ->assertExitCode(1);

        $this->assertDatabaseCount('sonar_metric_snapshots', 0);
    }

    public function test_project_detail_page_shows_latest_sonar_snapshot(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('Viewer');

        $project = Project::factory()->create();

        SonarMetricSnapshot::factory()->create([
            'project_id' => $project->id,
            'sonar_project_key' => $project->sonar_project_key,
            'bugs' => 7,
            'vulnerabilities' => 1,
            'code_smells' => 33,
            'quality_gate_status' => 'ERROR',
        ]);

        $response = $this->actingAs($viewer)->get(route('projects.show', $project));

        $response->assertOk();
        $response->assertSee('Latest SonarQube Snapshot');
        $response->assertSee('Historical Metric Snapshots');
        $response->assertSee('ERROR');
        $response->assertSee('33');
    }
}
