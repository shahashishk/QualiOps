<?php

namespace App\Console\Commands;

use App\Events\SystemWarningEvent;
use App\Services\System\SystemHealthService;
use Illuminate\Console\Command;

class SystemHealthCheckCommand extends Command
{
    protected $signature = 'system:health-check';

    protected $description = 'Run deployment-oriented health checks for the application runtime.';

    public function handle(SystemHealthService $systemHealthService): int
    {
        $report = $systemHealthService->getReport();

        $this->info('System Health Check');
        $this->line('Generated at: '.$report['generated_at']->toDateTimeString());
        $this->newLine();

        $this->table(
            ['Check', 'Status', 'Value', 'Details'],
            collect([
                ...array_values($report['overview']),
                ...array_values($report['application']),
                ...array_values($report['database']),
                ...array_values($report['queue']),
                ...array_values($report['scheduler']),
                ...array_values($report['storage']),
            ])->unique('label')->map(fn (array $item) => [
                $item['label'],
                $item['status'] ? 'OK' : 'FAIL',
                $item['value'],
                $item['details'],
            ])->values()->all()
        );

        $this->newLine();
        $this->info('SonarQube Connections');
        $this->table(
            ['Connection', 'Status', 'Details'],
            collect($report['connections']['sonar'])
                ->map(fn (array $item) => [$item['name'], $item['status'] ? 'OK' : 'FAIL', $item['details']])
                ->whenEmpty(fn ($items) => collect([['No active SonarQube connections', 'SKIP', 'No active SonarQube connections configured.']]))
                ->all()
        );

        $this->newLine();
        $this->info('Git Connections');
        $this->table(
            ['Connection', 'Status', 'Details'],
            collect($report['connections']['git'])
                ->map(fn (array $item) => [$item['name'], $item['status'] ? 'OK' : 'FAIL', $item['details']])
                ->whenEmpty(fn ($items) => collect([['No active Git connections', 'SKIP', 'No active Git connections configured.']]))
                ->all()
        );

        if ($report['warnings'] !== []) {
            $this->newLine();
            $this->warn('Warnings');
            foreach ($report['warnings'] as $warning) {
                $this->line('- '.$warning);
            }

            if (collect($report['warnings'])->contains(fn (string $warning) => str_contains(strtolower($warning), 'queue worker'))) {
                event(new SystemWarningEvent('worker_down', $report['warnings'], route('system-health.index')));
            }
        }

        $hasFailure = collect([
            ...array_values($report['application']),
            ...array_values($report['database']),
            ...array_values($report['queue']),
            ...array_values($report['scheduler']),
            ...array_values($report['storage']),
        ])->contains(fn (array $item) => $item['status'] === false);

        $hasConnectionFailure = collect($report['connections']['sonar'])
            ->merge($report['connections']['git'])
            ->contains(fn (array $item) => $item['status'] === false);

        return $hasFailure || $hasConnectionFailure ? self::FAILURE : self::SUCCESS;
    }
}
