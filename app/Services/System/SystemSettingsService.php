<?php

namespace App\Services\System;

use App\Models\SystemSetting;

class SystemSettingsService
{
    public function get(string $key, mixed $default = null): mixed
    {
        return SystemSetting::query()
            ->where('key', $key)
            ->value('value') ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        SystemSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value]
        );
    }

    public function auditLogRetentionDays(): int
    {
        return max(1, (int) $this->get('audit_log_retention_days', 90));
    }

    public function scanLogRetentionDays(): int
    {
        return max(1, (int) $this->get('scan_log_retention_days', 30));
    }

    public function failedQueueRetentionDays(): int
    {
        return max(1, (int) $this->get('failed_queue_retention_days', 30));
    }

    public function emailEnabled(): bool
    {
        return (bool) (int) $this->get('email_enabled', 0);
    }

    public function notifyOnScanSuccess(): bool
    {
        return (bool) (int) $this->get('notify_on_scan_success', 1);
    }

    public function notifyOnScanFailed(): bool
    {
        return (bool) (int) $this->get('notify_on_scan_failed', 1);
    }

    public function notifyOnQualityGateFailed(): bool
    {
        return (bool) (int) $this->get('notify_on_quality_gate_failed', 1);
    }

    public function notifyOnSchedulerFailed(): bool
    {
        return (bool) (int) $this->get('notify_on_scheduler_failed', 1);
    }

    public function notifyOnWorkerDown(): bool
    {
        return (bool) (int) $this->get('notify_on_worker_down', 1);
    }

    /**
     * @return array<string, bool>
     */
    public function notificationSettings(): array
    {
        return [
            'email_enabled' => $this->emailEnabled(),
            'notify_on_scan_success' => $this->notifyOnScanSuccess(),
            'notify_on_scan_failed' => $this->notifyOnScanFailed(),
            'notify_on_quality_gate_failed' => $this->notifyOnQualityGateFailed(),
            'notify_on_scheduler_failed' => $this->notifyOnSchedulerFailed(),
            'notify_on_worker_down' => $this->notifyOnWorkerDown(),
        ];
    }
}
