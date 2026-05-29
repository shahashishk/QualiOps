<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditGovernanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_admin_can_view_audit_logs_index(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        app(AuditLogService::class)->record('auth.logged_in', $admin, $admin, [], ['email' => $admin->email]);

        $response = $this->actingAs($admin)->get(route('audit-logs.index'));

        $response->assertOk();
        $response->assertSee('Audit Logs');
        $response->assertSee('Auth Logged In');
    }

    public function test_pm_cannot_view_audit_logs_index(): void
    {
        $pm = User::factory()->create();
        $pm->assignRole('PM');

        $response = $this->actingAs($pm)->get(route('audit-logs.index'));

        $response->assertForbidden();
    }

    public function test_user_detail_shows_activity_timeline(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $managedUser = User::factory()->create();

        app(AuditLogService::class)->record('user.created', $managedUser, $admin, [], ['email' => $managedUser->email]);

        $response = $this->actingAs($admin)->get(route('users.show', $managedUser));

        $response->assertOk();
        $response->assertSee('Activity Timeline');
        $response->assertSee('User Created');
    }

    public function test_audit_cleanup_dry_run_does_not_delete_logs(): void
    {
        AuditLog::query()->create([
            'user_id' => null,
            'entity_type' => User::class,
            'entity_id' => null,
            'action' => 'auth.logged_in',
            'old_values' => null,
            'new_values' => ['email' => 'old@example.com'],
            'ip_address' => null,
            'user_agent' => null,
            'created_at' => CarbonImmutable::now()->subDays(100),
            'updated_at' => CarbonImmutable::now()->subDays(100),
        ]);

        $this->artisan('audit:cleanup --dry-run')
            ->expectsOutput('Audit Cleanup Preview')
            ->assertExitCode(0);

        $this->assertDatabaseCount('audit_logs', 1);
    }

    public function test_audit_cleanup_deletes_logs_older_than_retention(): void
    {
        AuditLog::query()->create([
            'user_id' => null,
            'entity_type' => User::class,
            'entity_id' => null,
            'action' => 'auth.logged_in',
            'old_values' => null,
            'new_values' => ['email' => 'old@example.com'],
            'ip_address' => null,
            'user_agent' => null,
            'created_at' => CarbonImmutable::now()->subDays(100),
            'updated_at' => CarbonImmutable::now()->subDays(100),
        ]);

        $this->artisan('audit:cleanup')->expectsOutput('Audit Cleanup')->assertExitCode(0);

        $this->assertDatabaseCount('audit_logs', 0);
    }
}
