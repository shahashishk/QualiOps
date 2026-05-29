<?php

namespace App\Console\Commands;

use App\Services\System\SystemPreflightService;
use Illuminate\Console\Command;

class SystemPreflightCommand extends Command
{
    protected $signature = 'system:preflight';

    protected $description = 'Run pre-deployment validation checks for environment, permissions, and services.';

    public function handle(SystemPreflightService $systemPreflightService): int
    {
        $report = $systemPreflightService->getReport();

        $this->info('System Preflight');
        $this->line('Generated at: '.$report['generated_at']->toDateTimeString());
        $this->newLine();

        $this->table(
            ['Check', 'Status', 'Value', 'Details'],
            collect($report['checks'])->map(fn (array $item) => [
                $item['label'],
                $item['status'] ? 'PASS' : 'FAIL',
                $item['value'],
                $item['details'],
            ])->all()
        );

        if ($report['warnings'] !== []) {
            $this->newLine();
            $this->warn('Warnings');
            foreach ($report['warnings'] as $warning) {
                $this->line('- '.$warning);
            }
        }

        return $report['passed'] ? self::SUCCESS : self::FAILURE;
    }
}
