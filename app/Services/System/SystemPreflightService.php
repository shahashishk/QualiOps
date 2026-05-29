<?php

namespace App\Services\System;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

class SystemPreflightService
{
    public function __construct(
        private readonly SystemHealthService $systemHealthService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getReport(): array
    {
        $healthReport = $this->systemHealthService->getReport();
        $checks = [
            $this->envFileCheck(),
            $this->requiredEnvCheck(),
            $this->appKeyCheck(),
            $this->debugCheck(),
            $this->queueConfiguredCheck(),
            $this->schedulerConfiguredCheck(),
            $this->storageLinkedCheck(),
            $healthReport['storage']['storage'],
            $healthReport['storage']['cache'],
            $healthReport['storage']['workspace'],
            $healthReport['database']['database'],
            $healthReport['queue']['backend'],
            $this->activeServicesReachableCheck($healthReport),
        ];

        return [
            'generated_at' => now(),
            'checks' => $checks,
            'passed' => collect($checks)->every(fn (array $item) => $item['status'] === true),
            'warnings' => collect($checks)
                ->filter(fn (array $item) => $item['status'] === false)
                ->map(fn (array $item) => $item['details'])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function envFileCheck(): array
    {
        $exists = File::exists(base_path('.env'));

        return [
            'label' => '.env File',
            'status' => $exists,
            'value' => $exists ? 'present' : 'missing',
            'details' => $exists ? 'Environment file exists.' : 'Environment file is missing.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function requiredEnvCheck(): array
    {
        $required = ['APP_NAME', 'APP_ENV', 'APP_URL', 'DB_CONNECTION'];
        $dbConnection = (string) env('DB_CONNECTION', config('database.default'));

        if ($dbConnection === 'mysql') {
            $required = array_merge($required, ['DB_HOST', 'DB_DATABASE', 'DB_USERNAME']);
        }

        $missing = collect($required)
            ->filter(fn (string $key) => blank(env($key)))
            ->values()
            ->all();

        return [
            'label' => 'Required Environment Values',
            'status' => $missing === [],
            'value' => $missing === [] ? 'complete' : count($missing).' missing',
            'details' => $missing === []
                ? 'Required deployment environment values are present.'
                : 'Missing required values: '.implode(', ', $missing),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function appKeyCheck(): array
    {
        return [
            'label' => 'Application Key',
            'status' => filled(config('app.key')),
            'value' => filled(config('app.key')) ? 'configured' : 'missing',
            'details' => filled(config('app.key'))
                ? 'Application key is configured.'
                : 'APP_KEY is missing.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function debugCheck(): array
    {
        $safe = ! app()->environment('production') || config('app.debug') === false;

        return [
            'label' => 'Production Debug Safety',
            'status' => $safe,
            'value' => config('app.debug') ? 'debug enabled' : 'debug disabled',
            'details' => $safe
                ? 'Debug mode is acceptable for the current environment.'
                : 'APP_DEBUG must be false in production.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function queueConfiguredCheck(): array
    {
        $queue = (string) Config::get('queue.default');

        return [
            'label' => 'Queue Configuration',
            'status' => filled($queue),
            'value' => $queue ?: 'missing',
            'details' => filled($queue)
                ? 'Queue connection is configured.'
                : 'Queue connection is missing.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function schedulerConfiguredCheck(): array
    {
        $events = app(Schedule::class)->events();

        return [
            'label' => 'Scheduler Configuration',
            'status' => count($events) > 0,
            'value' => count($events).' task(s)',
            'details' => count($events) > 0
                ? 'Laravel scheduler contains registered tasks. Host cron still needs schedule:run.'
                : 'No Laravel scheduler tasks are registered.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function storageLinkedCheck(): array
    {
        $path = public_path('storage');
        $linked = is_link($path) || File::exists($path);

        return [
            'label' => 'Storage Link',
            'status' => $linked,
            'value' => $linked ? 'linked' : 'missing',
            'details' => $linked
                ? 'Public storage link is available.'
                : 'Run php artisan storage:link before deployment.',
        ];
    }

    /**
     * @param array<string, mixed> $healthReport
     * @return array<string, mixed>
     */
    private function activeServicesReachableCheck(array $healthReport): array
    {
        $sonarFailed = collect($healthReport['connections']['sonar'])->filter(fn (array $item) => $item['status'] === false)->count();
        $gitFailed = collect($healthReport['connections']['git'])->filter(fn (array $item) => $item['status'] === false)->count();
        $totalConnections = count($healthReport['connections']['sonar']) + count($healthReport['connections']['git']);

        return [
            'label' => 'External Services Reachability',
            'status' => $sonarFailed === 0 && $gitFailed === 0,
            'value' => $totalConnections > 0 ? $totalConnections.' active connection(s)' : 'none configured',
            'details' => $totalConnections === 0
                ? 'No active Git or SonarQube connections are configured.'
                : ($sonarFailed === 0 && $gitFailed === 0
                    ? 'All active Git and SonarQube connections are reachable.'
                    : 'Some active Git or SonarQube connections are unreachable.'),
        ];
    }
}
