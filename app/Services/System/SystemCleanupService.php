<?php

namespace App\Services\System;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SystemCleanupService
{
    public function __construct(
        private readonly SystemSettingsService $systemSettingsService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function preview(?int $daysOverride = null): array
    {
        $targets = [
            $this->buildTarget(
                table: 'scan_logs',
                label: 'Scan Logs',
                retentionDays: $daysOverride ?? $this->systemSettingsService->scanLogRetentionDays(),
                dateColumn: 'created_at'
            ),
            $this->buildTarget(
                table: 'audit_logs',
                label: 'Audit Logs',
                retentionDays: $daysOverride ?? $this->systemSettingsService->auditLogRetentionDays(),
                dateColumn: 'created_at'
            ),
            $this->buildTarget(
                table: 'failed_jobs',
                label: 'Failed Queue Jobs',
                retentionDays: $daysOverride ?? $this->systemSettingsService->failedQueueRetentionDays(),
                dateColumn: 'failed_at'
            ),
        ];

        return [
            'generated_at' => now(),
            'targets' => $targets,
            'total_affected' => collect($targets)->sum('count'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function cleanup(?int $daysOverride = null): array
    {
        $preview = $this->preview($daysOverride);
        $deleted = [];

        foreach ($preview['targets'] as $target) {
            if (! $target['available'] || $target['count'] === 0) {
                $deleted[] = array_merge($target, ['deleted' => 0]);
                continue;
            }

            $count = DB::table($target['table'])
                ->where($target['date_column'], '<', $target['threshold'])
                ->delete();

            $deleted[] = array_merge($target, ['deleted' => $count]);
        }

        return [
            'generated_at' => now(),
            'targets' => $deleted,
            'total_deleted' => collect($deleted)->sum('deleted'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildTarget(string $table, string $label, int $retentionDays, string $dateColumn): array
    {
        $available = Schema::hasTable($table) && Schema::hasColumn($table, $dateColumn);
        $threshold = now()->subDays(max(1, $retentionDays));
        $count = 0;

        if ($available) {
            $count = DB::table($table)
                ->where($dateColumn, '<', $threshold)
                ->count();
        }

        return [
            'table' => $table,
            'label' => $label,
            'retention_days' => max(1, $retentionDays),
            'date_column' => $dateColumn,
            'threshold' => $threshold,
            'available' => $available,
            'count' => $count,
            'details' => $available
                ? $count.' record(s) older than '.$threshold->toDateTimeString().' are eligible for cleanup.'
                : 'Cleanup skipped because the table or timestamp column is not available.',
        ];
    }
}
