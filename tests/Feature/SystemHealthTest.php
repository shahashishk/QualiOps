<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemHealthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_admin_can_view_system_health_page(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin)->get(route('system-health.index'));

        $response->assertOk();
        $response->assertSee('Production Readiness Dashboard');
        $response->assertSee('Final Verification');
    }

    public function test_pm_cannot_view_system_health_page(): void
    {
        $pm = User::factory()->create();
        $pm->assignRole('PM');

        $response = $this->actingAs($pm)->get(route('system-health.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_view_system_maintenance_page(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin)->get(route('system-maintenance.index'));

        $response->assertOk();
        $response->assertSee('Maintenance Readiness');
        $response->assertSee('Preflight Checks');
        $response->assertSee('Cleanup Preview');
    }
}
