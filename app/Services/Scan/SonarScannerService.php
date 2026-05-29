<?php

namespace App\Services\Scan;

use App\Models\Project;
use App\Models\ScanHistory;
use Symfony\Component\Process\Process;

class SonarScannerService
{
    public function __construct(
        private readonly ScanLogService $scanLogService,
    ) {
    }

    /**
     * @param array<int, string> $detectedStacks
     * @return array{successful: bool, output: string, error_output: string}
     */
    public function run(Project $project, ScanHistory $scanHistory, string $sourcePath, array $detectedStacks): array
    {
        $primaryStack = $this->primaryStack($detectedStacks, $project->scan_type);
        $sonarConnection = $project->sonarConnection;
        $secrets = array_values(array_filter([
            (string) $sonarConnection->token,
            (string) $project->gitConnection->token,
        ]));

        if ($primaryStack === 'dotnet') {
            return $this->runDotnetScan($project, $scanHistory, $sourcePath, $secrets);
        }

        return $this->runCliScan($project, $scanHistory, $sourcePath, $primaryStack, $secrets);
    }

    /**
     * @param array<int, string> $secrets
     * @return array{successful: bool, output: string, error_output: string}
     */
    private function runDotnetScan(Project $project, ScanHistory $scanHistory, string $sourcePath, array $secrets): array
    {
        $begin = $this->runProcess([
            'dotnet',
            'sonarscanner',
            'begin',
            '/k:'.$project->sonar_project_key,
            '/n:'.$project->sonar_project_name,
            '/d:sonar.host.url='.$project->sonarConnection->sonar_url,
            '/d:sonar.token='.$project->sonarConnection->token,
        ], $sourcePath);

        $this->scanLogService->writeOutput($scanHistory, $begin['output'], $begin['error_output'], $secrets);

        if (! $begin['successful']) {
            return $begin;
        }

        $build = $this->runProcess(['dotnet', 'build'], $sourcePath);
        $this->scanLogService->writeOutput($scanHistory, $build['output'], $build['error_output'], $secrets);

        if (! $build['successful']) {
            return $build;
        }

        $end = $this->runProcess([
            'dotnet',
            'sonarscanner',
            'end',
            '/d:sonar.token='.$project->sonarConnection->token,
        ], $sourcePath);

        $this->scanLogService->writeOutput($scanHistory, $end['output'], $end['error_output'], $secrets);

        return $end;
    }

    /**
     * @param array<int, string> $secrets
     * @return array{successful: bool, output: string, error_output: string}
     */
    private function runCliScan(Project $project, ScanHistory $scanHistory, string $sourcePath, string $stack, array $secrets): array
    {
        $sources = $stack === 'flutter' ? 'lib' : '.';

        $scan = $this->runProcess([
            'sonar-scanner',
            '-Dsonar.projectKey='.$project->sonar_project_key,
            '-Dsonar.projectName='.$project->sonar_project_name,
            '-Dsonar.host.url='.$project->sonarConnection->sonar_url,
            '-Dsonar.token='.$project->sonarConnection->token,
            '-Dsonar.projectBaseDir='.$sourcePath,
            '-Dsonar.sources='.$sources,
        ], $sourcePath);

        $this->scanLogService->writeOutput($scanHistory, $scan['output'], $scan['error_output'], $secrets);

        return $scan;
    }

    /**
     * @param array<int, string> $command
     * @return array{successful: bool, output: string, error_output: string}
     */
    private function runProcess(array $command, string $workingDirectory): array
    {
        $process = new Process($command, $workingDirectory, timeout: config('scan.timeout_seconds'));
        $process->run();

        return [
            'successful' => $process->isSuccessful(),
            'output' => $process->getOutput(),
            'error_output' => $process->getErrorOutput(),
        ];
    }

    /**
     * @param array<int, string> $detectedStacks
     */
    private function primaryStack(array $detectedStacks, string $requestedScanType): string
    {
        if ($requestedScanType !== 'auto' && $requestedScanType !== 'mixed') {
            return $requestedScanType;
        }

        if (in_array('dotnet', $detectedStacks, true)) {
            return 'dotnet';
        }

        if (in_array('flutter', $detectedStacks, true)) {
            return 'flutter';
        }

        if (in_array('angular', $detectedStacks, true)) {
            return 'angular';
        }

        if (in_array('node', $detectedStacks, true)) {
            return 'node';
        }

        return $detectedStacks[0] ?? 'generic';
    }
}
