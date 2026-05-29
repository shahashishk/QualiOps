<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InitialSuperAdminSeeder extends Seeder
{
    /**
     * Seed the initial super admin account.
     */
    public function run(): void
    {
        $email = (string) env('INITIAL_SUPER_ADMIN_EMAIL', 'superadmin@qualiops.local');
        $password = (string) env('INITIAL_SUPER_ADMIN_PASSWORD', 'ChangeMe!123');
        $name = (string) env('INITIAL_SUPER_ADMIN_NAME', 'Super Admin');

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $user->syncRoles(['Super Admin']);
    }
}
