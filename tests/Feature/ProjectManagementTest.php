<?php

namespace Tests\Feature;

use App\Models\GitConnection;
use App\Models\Project;
use App\Models\SonarConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_admin_can_view_projects_index(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin)->get(route('projects.index'));

        $response->assertOk();
        $response->assertSee('Projects');
    }

    public function test_admin_can_view_project_details(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $gitConnection = GitConnection::factory()->create();
        $sonarConnection = SonarConnection::factory()->create();
        $project = Project::factory()->create([
            'git_connection_id' => $gitConnection->id,
            'sonar_connection_id' => $sonarConnection->id,
        ]);

        $response = $this->actingAs($admin)->get(route('projects.show', $project));

        $response->assertOk();
        $response->assertSee($project->project_name);
        $response->assertSee($project->sonar_project_key);
    }

    public function test_generic_git_branch_fetch_returns_manual_fallback(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $gitConnection = GitConnection::factory()->create([
            'provider_type' => 'generic',
            'auth_type' => 'token',
        ]);

        $response = $this->actingAs($admin)->postJson(route('projects.fetch-branches'), [
            'git_connection_id' => $gitConnection->id,
            'git_url' => 'https://git.example.com/repositories/portal.git',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'branches' => [],
            'default_branch' => null,
            'requires_manual_selection' => true,
        ]);
    }
}
