<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_admin_can_view_roles_index(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin)->get(route('roles.index'));

        $response->assertOk();
        $response->assertSee('Roles & Permissions');
    }

    public function test_non_super_admin_cannot_edit_super_admin_role(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $superAdminRole = Role::query()->where('name', 'Super Admin')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('roles.edit', $superAdminRole));

        $response->assertForbidden();
    }
}
