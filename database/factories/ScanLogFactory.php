<?php

namespace Database\Factories;

use App\Models\ScanHistory;
use App\Models\ScanLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScanLog>
 */
class ScanLogFactory extends Factory
{
    protected $model = ScanLog::class;

    public function definition(): array
    {
        return [
            'scan_history_id' => ScanHistory::factory(),
            'log_level' => 'info',
            'message' => fake()->sentence(),
            'context' => ['source' => 'test'],
        ];
    }
}
