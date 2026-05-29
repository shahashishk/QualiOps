<?php

namespace App\Services\System;

use App\Models\GitConnection;
use App\Models\ScanScheduleLog;
use App\Models\SonarConnection;
use App\Services\Git\GitConnectionService;
use App\Services\Sonar\SonarQubeConnectionService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Throwable;

class SystemHealthService
{
    public function __construct(
        private readonly GitConnectionService $gitConnectionService,
        private readonly SonarQubeConnectionService $sonarQubeConnectionService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getReport(): array
    {
        $application = $this->applicationChecks();
        $database = $this->databaseChecks();
        $queue = $this->queueChecks();
        $scheduler = $this->schedulerChecks();
        $storage = $this->storageChecks();
        $connections = $this->connectionChecks();

        return [
            'generated_at' => now(),
            'application' => $application,
            'database' => $database,
            'queue' => $queue,
            'scheduler' => $scheduler,
            'storage' => $storage,
            'connections' => $connections,
            'runtime' => $this->runtimeChecks($application, $queue, $storage),
            'overview' => $this->overviewCards($application, $database, $queue, $scheduler, $storage, $connections),
            'final_verification' => $this->finalVerification($application, $queue, $scheduler, $storage, $connections),
            'warnings' => $this->warnings($application, $database, $queue, $scheduler, $storage, $connections),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function runtimeChecks(?array $application = null, ?array $queue = null, ?array $storage = null): array
    {
        $application ??= $this->applicationChecks();
        $queue ??= $this->queueChecks();
        $storage ??= $this->storageChecks();

        return [
            $application['php_version'],
            $application['git_binary'],
            $application['composer_binary'],
            $application['java_binary'],
            $application['dotnet_binary'],
            $application['dotnet_sonarscanner_binary'],
            $application['sonar_scanner_binary'],
            $queue['worker'],
            $storage['workspace'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function applicationChecks(): array
    {
        return [
            'application_status' => [
                'label' => 'Application Status',
                'status' => filled(config('app.name')) && filled(config('app.env')),
                'value' => sprintf('%s (%s)', config('app.name'), config('app.env')),
                'details' => 'Application identity and runtime environment are loaded.',
            ],
            'php_version' => $this->phpVersionCheck(),
            'composer_binary' => $this->binaryCheck('Composer', 'composer', [['composer', '--version']]),
            'git_binary' => $this->binaryCheck('Git', 'git', [['git', '--version']]),
            'java_binary' => $this->binaryCheck('Java', 'java', [['java', '-version']], firstLineFromStdErr: true),
            'dotnet_binary' => $this->binaryCheck('.NET SDK', 'dotnet', [['dotnet', '--version']]),
            'dotnet_sonarscanner_binary' => $this->binaryCheck('dotnet-sonarscanner', 'dotnet-sonarscanner', [
                ['dotnet-sonarscanner', '--version'],
                ['dotnet', 'sonarscanner', '--version'],
            ]),
            'sonar_scanner_binary' => $this->binaryCheck('sonar-scanner', 'sonar-scanner', [['sonar-scanner', '--version']]),
            'app_key' => [
                'label' => 'Application Key',
                'status' => filled(config('app.key')),
                'value' => filled(config('app.key')) ? 'configured' : 'missing',
                'details' => filled(config('app.key'))
                    ? 'Application encryption key is configured.'
                    : 'APP_KEY is missing.',
            ],
            'debug_mode' => [
                'label' => 'Debug Mode',
                'status' => ! app()->environment('production') || config('app.debug') === false,
                'value' => config('app.debug') ? 'enabled' : 'disabled',
                'details' => app()->environment('production') && config('app.debug')
                    ? 'APP_DEBUG should be disabled in production.'
                    : 'Debug configuration is acceptable for the current environment.',
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function databaseChecks(): array
    {
        try {
            DB::connection()->getPdo();
            $driver = (string) config('database.default');
            $databaseName = DB::connection()->getDatabaseName() ?: 'connected';

            return [
                'database' => [
                    'label' => 'Database Status',
                    'status' => true,
                    'value' => sprintf('%s (%s)', $driver, $databaseName),
                    'details' => 'Database connection is reachable.',
                ],
            ];
        } catch (Throwable $exception) {
            return [
                'database' => [
                    'label' => 'Database Status',
                    'status' => false,
                    'value' => (string) config('database.default', 'unknown'),
                    'details' => 'Database connection failed: '.$this->safeExceptionMessage($exception),
                ],
            ];
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function queueChecks(): array
    {
        $connection = (string) Config::get('queue.default');
        $backend = $this->queueBackendAvailabilityCheck($connection);

        return [
            'connection' => [
                'label' => 'Queue Connection',
                'status' => filled($connection),
                'value' => $connection ?: 'not configured',
                'details' => filled($connection)
                    ? 'Queue connection is configured.'
                    : 'Queue connection is missing.',
            ],
            'backend' => $backend,
            'worker' => $this->queueWorkerCheck(),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function schedulerChecks(): array
    {
        $events = app(Schedule::class)->events();
        $lastRun = Schema::hasTable('scan_schedule_logs')
            ? ScanScheduleLog::query()->latest('scheduled_at')->first()
            : null;

        return [
            'registered' => [
                'label' => 'Scheduler Status',
                'status' => count($events) > 0,
                'value' => count($events).' scheduled task(s)',
                'details' => count($events) > 0
                    ? 'Laravel schedule definitions are registered. Host cron still needs to call schedule:run.'
                    : 'No Laravel scheduled tasks are registered.',
            ],
            'last_run' => [
                'label' => 'Last Scheduler Run',
                'status' => $lastRun !== null,
                'value' => $lastRun?->scheduled_at?->format('d M Y H:i') ?? 'not recorded',
                'details' => $lastRun
                    ? 'Most recent scheduler execution recorded by the application.'
                    : 'No scheduler execution log recorded yet.',
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function storageChecks(): array
    {
        return [
            'storage' => $this->directoryWritableCheck('Storage Path', storage_path(), 'Application storage directory'),
            'cache' => $this->directoryWritableCheck('Cache Path', base_path('bootstrap/cache'), 'Bootstrap cache directory'),
            'workspace' => $this->workspaceWritableCheck(),
            'storage_link' => $this->storageLinkCheck(),
            'disk_space' => $this->diskSpaceCheck(),
        ];
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function connectionChecks(): array
    {
        return [
            'sonar' => $this->sonarConnectionChecks(),
            'git' => $this->gitConnectionChecks(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function overviewCards(array $application, array $database, array $queue, array $scheduler, array $storage, array $connections): array
    {
        return [
            $application['application_status'],
            $database['database'],
            [
                'label' => 'Queue Status',
                'status' => $queue['connection']['status'] && $queue['backend']['status'],
                'value' => $queue['connection']['value'],
                'details' => $queue['backend']['details'],
            ],
            [
                'label' => 'Scheduler Status',
                'status' => $scheduler['registered']['status'],
                'value' => $scheduler['registered']['value'],
                'details' => $scheduler['registered']['details'],
            ],
            [
                'label' => 'Worker Status',
                'status' => $queue['worker']['status'],
                'value' => $queue['worker']['value'],
                'details' => $queue['worker']['details'],
            ],
            $storage['disk_space'],
            $storage['workspace'],
            $this->aggregateConnectionCheck('SonarQube Connection', $connections['sonar']),
            $this->aggregateConnectionCheck('Git Connection', $connections['git']),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function finalVerification(array $application, array $queue, array $scheduler, array $storage, array $connections): array
    {
        $runtimeDependenciesInstalled = collect([
            $application['php_version'],
            $application['git_binary'],
            $application['java_binary'],
            $application['dotnet_binary'],
            $application['dotnet_sonarscanner_binary'],
            $application['sonar_scanner_binary'],
        ])->every(fn (array $item) => $item['status'] === true);

        return [
            [
                'label' => 'Queue Running',
                'status' => $queue['worker']['status'],
                'value' => $queue['worker']['value'],
                'details' => $queue['worker']['details'],
            ],
            [
                'label' => 'Scheduler Active',
                'status' => $scheduler['registered']['status'],
                'value' => $scheduler['registered']['value'],
                'details' => $scheduler['registered']['details'],
            ],
            [
                'label' => 'Runtime Dependencies Installed',
                'status' => $runtimeDependenciesInstalled,
                'value' => $runtimeDependenciesInstalled ? 'ready' : 'missing dependencies',
                'details' => 'Verifies the core runtime binaries required for full scan execution.',
            ],
            $this->aggregateConnectionCheck('Sonar Reachable', $connections['sonar']),
            [
                'label' => 'Workspace Writable',
                'status' => $storage['workspace']['status'],
                'value' => $storage['workspace']['value'],
                'details' => $storage['workspace']['details'],
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function warnings(array $application, array $database, array $queue, array $scheduler, array $storage, array $connections): array
    {
        $warnings = [];

        if (! $application['debug_mode']['status']) {
            $warnings[] = 'APP_DEBUG is enabled in production and should be disabled before release.';
        }

        if (! $database['database']['status']) {
            $warnings[] = 'Database connectivity is failing.';
        }

        if (! $queue['worker']['status']) {
            $warnings[] = 'No active Laravel queue worker was detected.';
        }

        if (! $queue['backend']['status']) {
            $warnings[] = 'Configured queue backend is not reachable.';
        }

        if (! $scheduler['registered']['status']) {
            $warnings[] = 'No Laravel scheduled tasks are currently registered.';
        }

        if (! $storage['workspace']['status']) {
            $warnings[] = 'Scan workspace path is not writable.';
        }

        if (! $storage['storage_link']['status']) {
            $warnings[] = 'Public storage link is missing or not accessible.';
        }

        if (collect($connections['sonar'])->contains(fn (array $item) => $item['status'] === false)) {
            $warnings[] = 'One or more active SonarQube connections are unreachable.';
        }

        if (collect($connections['git'])->contains(fn (array $item) => $item['status'] === false)) {
            $warnings[] = 'One or more active Git connections are unreachable.';
        }

        return $warnings;
    }

    /**
     * @return array<string, mixed>
     */
    private function phpVersionCheck(): array
    {
        $version = PHP_VERSION;
        $status = version_compare($version, '8.2.0', '>=');

        return [
            'label' => 'PHP Version',
            'status' => $status,
            'value' => $version,
            'details' => $status
                ? 'PHP version satisfies the Laravel baseline.'
                : 'PHP 8.2 or higher is recommended.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function binaryCheck(string $label, string $binary, array $versionCommands, bool $firstLineFromStdErr = false): array
    {
        $path = $this->findBinaryPath($binary);
        $version = null;
        $details = $path ? 'Binary detected in PATH.' : 'Binary not found in PATH.';

        foreach ($versionCommands as $command) {
            $result = $this->runProcess($command, allowFailure: true);

            if ($result['successful']) {
                $version = $this->extractVersionLine($result['output'], $result['error_output'], $firstLineFromStdErr);
                break;
            }
        }

        return [
            'label' => $label,
            'status' => $path !== null || $version !== null,
            'value' => $version ?: ($path ?: 'missing'),
            'details' => $details,
            'path' => $path,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function queueWorkerCheck(): array
    {
        $commands = PHP_OS_FAMILY === 'Windows'
            ? [['powershell', '-NoProfile', '-Command', "Get-CimInstance Win32_Process | Where-Object { \$_.CommandLine -match 'artisan queue:(work|listen)' } | Select-Object -First 1 -ExpandProperty CommandLine"]]
            : [['sh', '-lc', "ps -eo command | grep 'artisan queue:' | grep -v grep | head -n 1"]];

        $workerCommand = null;

        foreach ($commands as $command) {
            $result = $this->runProcess($command, allowFailure: true);

            if ($result['successful'] && filled(trim($result['output']))) {
                $workerCommand = trim($result['output']);
                break;
            }
        }

        return [
            'label' => 'Laravel Queue Worker',
            'status' => $workerCommand !== null,
            'value' => $workerCommand ? 'running' : 'not running',
            'details' => $workerCommand ?: 'No active queue:work or queue:listen process detected.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function workspaceWritableCheck(): array
    {
        $workspacePath = (string) config('scan.workspace_path');

        try {
            File::ensureDirectoryExists($workspacePath);
            $writable = is_writable($workspacePath);
        } catch (Throwable) {
            $writable = false;
        }

        return [
            'label' => 'Workspace Path',
            'status' => $writable,
            'value' => $workspacePath,
            'details' => $writable ? 'Workspace directory is writable.' : 'Workspace directory is not writable.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function directoryWritableCheck(string $label, string $path, string $description): array
    {
        try {
            File::ensureDirectoryExists($path);
            $writable = is_writable($path);
        } catch (Throwable) {
            $writable = false;
        }

        return [
            'label' => $label,
            'status' => $writable,
            'value' => $path,
            'details' => $writable ? $description.' is writable.' : $description.' is not writable.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function storageLinkCheck(): array
    {
        $path = public_path('storage');
        $linked = is_link($path) || File::exists($path);

        return [
            'label' => 'Storage Link',
            'status' => $linked,
            'value' => $path,
            'details' => $linked
                ? 'Public storage link is present.'
                : 'Public storage link is missing. Run php artisan storage:link.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function diskSpaceCheck(): array
    {
        $diskFree = @disk_free_space(base_path());
        $status = $diskFree !== false;

        return [
            'label' => 'Disk Space',
            'status' => $status,
            'value' => $status ? $this->formatBytes((float) $diskFree) : 'unknown',
            'details' => $status
                ? 'Available disk space at the application root.'
                : 'Disk free space could not be determined.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function queueBackendAvailabilityCheck(string $connection): array
    {
        try {
            $available = match ($connection) {
                'database' => $this->databaseQueueAvailable(),
                'redis' => $this->redisQueueAvailable(),
                default => false,
            };
        } catch (Throwable $exception) {
            return [
                'label' => 'Queue Backend Availability',
                'status' => false,
                'value' => $connection ?: 'unknown',
                'details' => 'Queue backend check failed: '.$this->safeExceptionMessage($exception),
            ];
        }

        return [
            'label' => 'Queue Backend Availability',
            'status' => $available,
            'value' => $connection ?: 'unknown',
            'details' => $available
                ? 'Queue backend is reachable.'
                : 'Queue backend is not reachable or not fully configured.',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function sonarConnectionChecks(): array
    {
        return SonarConnection::query()
            ->where('active', true)
            ->orderBy('connection_name')
            ->get()
            ->map(function (SonarConnection $connection) {
                $result = $this->sonarQubeConnectionService->test($connection);

                return [
                    'name' => $connection->connection_name,
                    'status' => $result['success'],
                    'details' => $result['message'],
                    'type' => 'sonar',
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function gitConnectionChecks(): array
    {
        return GitConnection::query()
            ->where('active', true)
            ->orderBy('connection_name')
            ->get()
            ->map(function (GitConnection $connection) {
                $result = $this->gitConnectionService->test($connection);

                return [
                    'name' => $connection->connection_name,
                    'status' => $result['success'],
                    'details' => $result['message'],
                    'type' => 'git',
                ];
            })
            ->all();
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<string, mixed>
     */
    private function aggregateConnectionCheck(string $label, array $items): array
    {
        if ($items === []) {
            return [
                'label' => $label,
                'status' => false,
                'value' => 'not configured',
                'details' => 'No active connection is configured.',
            ];
        }

        $failed = collect($items)->filter(fn (array $item) => $item['status'] === false)->count();

        return [
            'label' => $label,
            'status' => $failed === 0,
            'value' => sprintf('%d active', count($items)),
            'details' => $failed === 0
                ? 'All active connections are reachable.'
                : $failed.' active connection(s) are unreachable.',
        ];
    }

    private function findBinaryPath(string $binary): ?string
    {
        $command = PHP_OS_FAMILY === 'Windows'
            ? ['where.exe', $binary]
            : ['which', $binary];

        $result = $this->runProcess($command, allowFailure: true);

        if (! $result['successful']) {
            return null;
        }

        $lines = preg_split("/\r\n|\n|\r/", trim($result['output']));

        return $lines[0] ?? null;
    }

    private function databaseQueueAvailable(): bool
    {
        DB::connection()->getPdo();

        return Schema::hasTable('jobs');
    }

    private function redisQueueAvailable(): bool
    {
        $response = Redis::connection()->ping();

        return in_array((string) $response, ['1', 'PONG'], true);
    }

    private function extractVersionLine(string $stdout, string $stderr, bool $fromStdErr): string
    {
        $source = $fromStdErr ? $stderr : $stdout;
        $line = trim(strtok($source, PHP_EOL));

        if ($line !== '') {
            return $line;
        }

        $fallback = trim(strtok($fromStdErr ? $stdout : $stderr, PHP_EOL));

        return $fallback !== '' ? $fallback : 'version detected';
    }

    /**
     * @param array<int, string> $command
     * @return array{successful: bool, output: string, error_output: string}
     */
    private function runProcess(array $command, bool $allowFailure = false): array
    {
        $process = new Process($command, timeout: 15);
        $process->run();

        if (! $allowFailure && ! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return [
            'successful' => $process->isSuccessful(),
            'output' => $process->getOutput(),
            'error_output' => $process->getErrorOutput(),
        ];
    }

    private function safeExceptionMessage(Throwable $exception): string
    {
        return str($exception->getMessage())
            ->replaceMatches('/(token|password|secret)=([^\\s&]+)/i', '$1=***')
            ->limit(180)
            ->toString();
    }

    private function formatBytes(float $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $bytes > 0 ? min((int) floor(log($bytes, 1024)), count($units) - 1) : 0;
        $value = $bytes / (1024 ** $power);

        return number_format($value, $power === 0 ? 0 : 2).' '.$units[$power];
    }
}
