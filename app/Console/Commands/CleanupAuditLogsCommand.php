<?php

namespace App\Console\Commands;

use App\Services\System\SystemSettingsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupAuditLogsCommand extends Command
{
    protected $signature = 'audit:cleanup {--dry-run : Preview expired audit logs without deleting them}';

    protected $description = 'Delete audit logs older than the configured retention period.';

    public function handle(SystemSettingsService $systemSettingsService): int
    {
        $retentionDays = $systemSettingsService->auditLogRetentionDays();
        $threshold = now()->subDays($retentionDays);

        $query = DB::table('audit_logs')->where('created_at', '<', $threshold);
        $count = (clone $query)->count();

        $this->info($this->option('dry-run') ? 'Audit Cleanup Preview' : 'Audit Cleanup');
        $this->line('Retention days: '.$retentionDays);
        $this->line('Threshold: '.$threshold->toDateTimeString());
        $this->line('Affected logs: '.$count);

        if (! $this->option('dry-run') && $count > 0) {
            $query->delete();
            $this->line('Expired audit logs removed.');
        }

        return self::SUCCESS;
    }
}
