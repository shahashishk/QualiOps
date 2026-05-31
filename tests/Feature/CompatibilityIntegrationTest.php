<?php

namespace Tests\Feature;

use App\Models\GitConnection;
use App\Models\Project;
use App\Models\SonarConnection;
use App\Services\Git\GitBranchService;
use App\Services\Sonar\SonarMetricsService;
use App\Services\Sonar\SonarQubeConnectionService;
use App\Services\Scan\GitCloneService;
use App\Services\Scan\WorkspaceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class CompatibilityIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_sonar_connection_test_uses_bearer_authentication_and_supported_endpoints(): void
    {
        $connection = SonarConnection::factory()->create([
            'sonar_url' => 'https://sonarqube.example.com',
            'token' => 'sonar-secret-token',
        ]);

        Http::fake([
            'https://sonarqube.example.com/api/authentication/validate' => Http::response([
                'valid' => true,
            ], 200),
            'https://sonarqube.example.com/api/server/version' => Http::response('24.12.0.100206', 200),
        ]);

        $result = app(SonarQubeConnectionService::class)->test($connection);

        $this->assertTrue($result['success']);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://sonarqube.example.com/api/authentication/validate'
                && $request->hasHeader('Authorization', 'Bearer sonar-secret-token');
        });

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://sonarqube.example.com/api/server/version'
                && $request->hasHeader('Authorization', 'Bearer sonar-secret-token');
        });
    }

    public function test_sonar_metrics_sync_uses_bearer_authentication(): void
    {
        $project = Project::factory()->create();
        $project->sonarConnection->update(['token' => 'sonar-secret-token']);

        Http::fake([
            'https://sonarqube.example.com/api/measures/component*' => Http::response([
                'component' => [
                    'measures' => [
                        ['metric' => 'bugs', 'value' => '2'],
                        ['metric' => 'vulnerabilities', 'value' => '1'],
                        ['metric' => 'code_smells', 'value' => '4'],
                    ],
                ],
            ], 200),
            'https://sonarqube.example.com/api/qualitygates/project_status*' => Http::response([
                'projectStatus' => [
                    'status' => 'OK',
                ],
            ], 200),
        ]);

        $result = app(SonarMetricsService::class)->sync($project->fresh('sonarConnection'), true);

        $this->assertTrue($result['success']);

        Http::assertSent(function (Request $request) {
            return str_starts_with($request->url(), 'https://sonarqube.example.com/api/measures/component')
                && $request->hasHeader('Authorization', 'Bearer sonar-secret-token');
        });

        Http::assertSent(function (Request $request) {
            return str_starts_with($request->url(), 'https://sonarqube.example.com/api/qualitygates/project_status')
                && $request->hasHeader('Authorization', 'Bearer sonar-secret-token');
        });
    }

    public function test_gitlab_branch_fetch_paginates_results_for_self_hosted_gitlab(): void
    {
        $connection = GitConnection::factory()->create([
            'provider_type' => 'gitlab',
            'base_url' => 'https://gitlab.example.com/gitlab',
            'auth_type' => 'token',
            'token' => 'gitlab-secret-token',
        ]);

        Http::fake([
            'https://gitlab.example.com/gitlab/api/v4/projects/team%2Fcore%2Fquality-portal/repository/branches*' => function (Request $request) {
                $page = (int) ($request->data()['page'] ?? 1);

                if ($page === 1) {
                    return Http::response([
                        ['name' => 'develop'],
                    ], 200, ['X-Next-Page' => '2']);
                }

                return Http::response([
                    ['name' => 'uat'],
                    ['name' => 'main'],
                ], 200, ['X-Next-Page' => '']);
            },
        ]);

        $result = app(GitBranchService::class)->fetchBranches(
            $connection,
            'https://gitlab.example.com/gitlab/team/core/quality-portal.git'
        );

        $this->assertTrue($result['success']);
        $this->assertSame('uat', $result['default_branch']);
        $this->assertSame(['develop', 'main', 'uat'], $result['branches']);

        Http::assertSent(function (Request $request) {
            return str_starts_with($request->url(), 'https://gitlab.example.com/gitlab/api/v4/projects/team%2Fcore%2Fquality-portal/repository/branches')
                && $request->hasHeader('PRIVATE-TOKEN', 'gitlab-secret-token');
        });
    }

    public function test_gitlab_branch_fetch_rejects_repository_url_from_different_host(): void
    {
        $connection = GitConnection::factory()->create([
            'provider_type' => 'gitlab',
            'base_url' => 'https://gitlab.example.com/gitlab',
            'auth_type' => 'token',
            'token' => 'gitlab-secret-token',
        ]);

        Http::fake();

        $result = app(GitBranchService::class)->fetchBranches(
            $connection,
            'https://github.com/acme/quality-portal.git'
        );

        $this->assertFalse($result['success']);
        $this->assertSame('Repository URL does not match the selected Git connection host.', $result['message']);

        Http::assertNothingSent();
    }

    public function test_git_clone_service_runs_readiness_check_before_clone_and_stops_on_failure(): void
    {
        $project = Project::factory()->create([
            'git_url' => 'https://gitlab.example.com/team/core/quality-portal.git',
            'branch_name' => 'uat',
        ]);

        $project->gitConnection->update([
            'provider_type' => 'gitlab',
            'base_url' => 'https://gitlab.example.com',
            'auth_type' => 'token',
            'token' => 'gitlab-secret-token',
        ]);

        $workspaceService = Mockery::mock(WorkspaceService::class);

        $service = new class($workspaceService, app(\App\Services\Git\GitRepositoryResolver::class)) extends GitCloneService
        {
            /** @var array<int, array<int, string>> */
            public array $commands = [];

            protected function runProcess(array $command, ?string $workingDirectory = null, array $environment = []): array
            {
                $this->commands[] = $command;

                return [
                    'successful' => false,
                    'output' => '',
                    'error_output' => 'remote: HTTP Basic: Access denied',
                ];
            }
        };

        $result = $service->clone($project->fresh('gitConnection'), 'C:\\scan-workspaces\\queue-1\\source');

        $this->assertFalse($result['successful']);
        $this->assertCount(1, $service->commands);
        $this->assertSame(['git', 'ls-remote', '--exit-code', '--heads'], array_slice($service->commands[0], 0, 4));
    }
}
