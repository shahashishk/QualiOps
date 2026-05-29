<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_admin_can_view_user_index(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin)->get(route('users.index'));

        $response->assertOk();
        $response->assertSee('Create User');
    }

    public function test_non_super_admin_cannot_edit_super_admin_user(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $superAdmin = User::query()->where('email', 'admin@qualiops.com')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('users.edit', $superAdmin));

        $response->assertForbidden();
    }

    public function test_user_cannot_deactivate_own_account(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin)->patch(route('users.status', $admin), [
            'is_active' => false,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'You cannot deactivate your own account.');
        $this->assertTrue($admin->fresh()->is_active);
    }
}
