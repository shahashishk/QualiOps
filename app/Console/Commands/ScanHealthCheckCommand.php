<?php

namespace App\Console\Commands;

use App\Services\System\SystemHealthService;
use Illuminate\Console\Command;

class ScanHealthCheckCommand extends Command
{
    protected $signature = 'scan:health-check';

    protected $description = 'Check runtime readiness for background project scans.';

    public function handle(SystemHealthService $systemHealthService): int
    {
        $report = $systemHealthService->getReport();

        $this->info('Scanner Runtime Health Check');
        $this->line('Generated at: '.$report['generated_at']->toDateTimeString());
        $this->newLine();

        $this->table(
            ['Check', 'Status', 'Value', 'Details'],
            collect($report['runtime'])
                ->merge($report['queue'])
                ->map(fn (array $item) => [
                    $item['label'],
                    $item['status'] ? 'OK' : 'FAIL',
                    $item['value'],
                    $item['details'],
                ])
                ->all()
        );

        $this->newLine();
        $this->info('SonarQube Connections');
        $this->table(
            ['Connection', 'Status', 'Details'],
            collect($report['connections']['sonar'])
                ->map(fn (array $item) => [
                    $item['name'],
                    $item['status'] ? 'OK' : 'FAIL',
                    $item['details'],
                ])
                ->whenEmpty(fn ($collection) => collect([['No active SonarQube connections', 'SKIP', 'No active SonarQube connections configured.']]))
                ->all()
        );

        $this->newLine();
        $this->info('Git Connections');
        $this->table(
            ['Connection', 'Status', 'Details'],
            collect($report['connections']['git'])
                ->map(fn (array $item) => [
                    $item['name'],
                    $item['status'] ? 'OK' : 'FAIL',
                    $item['details'],
                ])
                ->whenEmpty(fn ($collection) => collect([['No active Git connections', 'SKIP', 'No active Git connections configured.']]))
                ->all()
        );

        $hasFailure = collect($report['runtime'])
            ->merge($report['queue'])
            ->contains(fn (array $item) => $item['status'] === false);

        $hasConnectionFailure = collect($report['connections']['sonar'])
            ->merge($report['connections']['git'])
            ->contains(fn (array $item) => $item['status'] === false);

        return $hasFailure || $hasConnectionFailure ? self::FAILURE : self::SUCCESS;
    }
}
