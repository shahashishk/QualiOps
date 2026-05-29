<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'audit_log_retention_days' => '90',
            'scan_log_retention_days' => '30',
            'failed_queue_retention_days' => '30',
            'email_enabled' => '0',
            'notify_on_scan_success' => '1',
            'notify_on_scan_failed' => '1',
            'notify_on_quality_gate_failed' => '1',
            'notify_on_scheduler_failed' => '1',
            'notify_on_worker_down' => '1',
        ];

        foreach ($settings as $key => $value) {
            SystemSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }
}
