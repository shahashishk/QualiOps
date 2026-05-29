<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SonarConnectionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_admin_can_view_sonar_connections_index(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin)->get(route('sonar-connections.index'));

        $response->assertOk();
        $response->assertSee('SonarQube Connections');
    }

    public function test_pm_cannot_view_sonar_connections_index(): void
    {
        $pm = User::factory()->create();
        $pm->assignRole('PM');

        $response = $this->actingAs($pm)->get(route('sonar-connections.index'));

        $response->assertForbidden();
    }
}
