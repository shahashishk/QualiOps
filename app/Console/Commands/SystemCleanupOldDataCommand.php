<?php

namespace App\Console\Commands;

use App\Services\System\SystemCleanupService;
use Illuminate\Console\Command;

class SystemCleanupOldDataCommand extends Command
{
    protected $signature = 'system:cleanup-old-data
        {--dry-run : Preview old data cleanup without deleting rows}
        {--days= : Override the retention window in days for all cleanup targets}';

    protected $description = 'Clean old scan logs, audit logs, and failed queue rows using retention settings.';

    public function handle(SystemCleanupService $systemCleanupService): int
    {
        $days = $this->option('days');
        $daysOverride = is_numeric($days) ? max(1, (int) $days) : null;

        $report = $this->option('dry-run')
            ? $systemCleanupService->preview($daysOverride)
            : $systemCleanupService->cleanup($daysOverride);

        $this->info($this->option('dry-run') ? 'System Cleanup Preview' : 'System Cleanup');
        $this->line('Generated at: '.$report['generated_at']->toDateTimeString());
        $this->newLine();

        $this->table(
            ['Target', 'Retention Days', 'Available', 'Affected', 'Details'],
            collect($report['targets'])->map(fn (array $item) => [
                $item['label'],
                $item['retention_days'],
                $item['available'] ? 'YES' : 'NO',
                $this->option('dry-run') ? $item['count'] : ($item['deleted'] ?? 0),
                $item['details'],
            ])->all()
        );

        $total = $this->option('dry-run')
            ? $report['total_affected']
            : $report['total_deleted'];

        $this->newLine();
        $this->line(($this->option('dry-run') ? 'Total affected: ' : 'Total deleted: ').$total);

        return self::SUCCESS;
    }
}
