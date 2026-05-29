<?php

namespace Database\Factories;

use App\Models\GitConnection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GitConnection>
 */
class GitConnectionFactory extends Factory
{
    protected $model = GitConnection::class;

    public function definition(): array
    {
        return [
            'connection_name' => fake()->unique()->company().' Git',
            'provider_type' => 'github',
            'base_url' => 'https://github.com',
            'auth_type' => 'token',
            'username' => null,
            'token' => 'test-token',
            'ssh_private_key' => null,
            'active' => true,
            'last_tested_at' => null,
            'last_test_status' => null,
            'last_test_result' => null,
        ];
    }
}
