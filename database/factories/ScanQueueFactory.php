<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ScanQueue;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScanQueue>
 */
class ScanQueueFactory extends Factory
{
    protected $model = ScanQueue::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'branch_name' => 'uat',
            'scan_type' => 'auto',
            'queue_status' => 'pending',
            'failure_category' => null,
            'queued_by' => User::factory(),
            'started_at' => null,
            'completed_at' => null,
            'worker_id' => null,
            'remarks' => fake()->sentence(),
        ];
    }
}
