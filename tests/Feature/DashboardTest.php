<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ScanScheduleLog;
use App\Models\SonarMetricSnapshot;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Command center for repository quality oversight');
        $response->assertSee('Total Projects');
    }

    public function test_authenticated_user_can_update_theme_preference_in_session(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('theme.update'), [
            'theme' => 'emerald-governance',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('theme', 'emerald-governance');
        $response->assertSessionHas('success', 'Theme preference updated.');
    }

    public function test_dashboard_shows_scheduler_metrics(): void
    {
        $user = User::factory()->create();

        Project::factory()->create([
            'project_name' => 'Scheduled Project',
            'schedule_enabled' => true,
            'schedule_frequency' => 'daily',
            'schedule_time' => '23:45',
        ]);

        ScanScheduleLog::query()->create([
            'scheduled_at' => CarbonImmutable::parse('2026-05-26 09:00:00'),
            'eligible_projects_count' => 1,
            'queued_projects_count' => 1,
            'skipped_projects_count' => 0,
            'status' => 'queued',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Next Scheduled Scan');
        $response->assertSee('Scheduled Projects');
        $response->assertSee('Last Scheduler Run');
    }

    public function test_dashboard_shows_latest_sonar_metric_aggregates(): void
    {
        $user = User::factory()->create();

        $projectOne = Project::factory()->create();
        $projectTwo = Project::factory()->create();

        SonarMetricSnapshot::factory()->create([
            'project_id' => $projectOne->id,
            'sonar_project_key' => $projectOne->sonar_project_key,
            'bugs' => 4,
            'vulnerabilities' => 1,
            'code_smells' => 10,
            'quality_gate_status' => 'OK',
            'synced_at' => now()->subHour(),
        ]);

        SonarMetricSnapshot::factory()->create([
            'project_id' => $projectTwo->id,
            'sonar_project_key' => $projectTwo->sonar_project_key,
            'bugs' => 2,
            'vulnerabilities' => 3,
            'code_smells' => 8,
            'quality_gate_status' => 'ERROR',
            'synced_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Total Bugs');
        $response->assertSee('Total Vulnerabilities');
        $response->assertSee('Total Code Smells');
        $response->assertSee('Quality Gates Passed');
        $response->assertSee('Quality Gates Failed');
    }
}
