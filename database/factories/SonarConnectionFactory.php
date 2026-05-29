<?php

namespace Database\Factories;

use App\Models\SonarConnection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SonarConnection>
 */
class SonarConnectionFactory extends Factory
{
    protected $model = SonarConnection::class;

    public function definition(): array
    {
        return [
            'connection_name' => fake()->unique()->company().' Sonar',
            'sonar_url' => 'https://sonarqube.example.com',
            'token' => 'test-token',
            'active' => true,
            'last_tested_at' => null,
            'last_test_status' => null,
            'last_test_result' => null,
        ];
    }
}
