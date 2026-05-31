<?php

namespace App\Services\Scan;

use App\Models\GitConnection;
use App\Models\Project;
use App\Services\Git\GitRepositoryResolver;
use Symfony\Component\Process\Process;

class GitCloneService
{
    public function __construct(
        private readonly WorkspaceService $workspaceService,
        private readonly GitRepositoryResolver $repositoryResolver,
    ) {
    }

    /**
     * @return array{successful: bool, output: string, error_output: string, source_path: string}
     */
    public function clone(Project $project, string $sourcePath): array
    {
        $gitConnection = $project->gitConnection;
        $environment = [
            'GIT_TERMINAL_PROMPT' => '0',
        ];

        $command = [
            'git',
            'clone',
            '--depth',
            '1',
            '--single-branch',
            '--branch',
            $project->branch_name,
        ];

        if ($gitConnection->provider_type !== 'generic'
            && ! $this->repositoryResolver->matchesConnectionHost($project->git_url, $gitConnection->base_url)) {
            return [
                'successful' => false,
                'output' => '',
                'error_output' => 'git_clone_failed: Repository URL does not match the selected Git connection host.',
                'source_path' => $sourcePath,
            ];
        }

        if ($gitConnection->auth_type === 'token' || $gitConnection->auth_type === 'username_password') {
            $authenticatedUrl = $this->authenticatedUrl($project->git_url, $gitConnection);

            if ($authenticatedUrl === null) {
                return [
                    'successful' => false,
                    'output' => '',
                    'error_output' => 'git_clone_failed: Token or username/password authentication requires an HTTP or HTTPS repository URL.',
                    'source_path' => $sourcePath,
                ];
            }

            $command[] = $authenticatedUrl;
        } else {
            $keyPath = $this->workspaceService->writeSshKey($this->workspaceRoot($sourcePath), (string) $gitConnection->ssh_private_key);
            $environment['GIT_SSH_COMMAND'] = 'ssh -i "'.$keyPath.'" -o IdentitiesOnly=yes -o StrictHostKeyChecking=no';
            $command[] = $project->git_url;
        }

        $command[] = $sourcePath;

        $readiness = $this->preflight($project, $command[count($command) - 2], $environment);

        if (! $readiness['successful']) {
            return [
                'successful' => false,
                'output' => $readiness['output'],
                'error_output' => $readiness['error_output'],
                'source_path' => $sourcePath,
            ];
        }

        $result = $this->runProcess($command, null, $environment);

        return [
            'successful' => $result['successful'],
            'output' => $result['output'],
            'error_output' => $result['error_output'],
            'source_path' => $sourcePath,
        ];
    }

    /**
     * @param array<string, string> $environment
     * @return array{successful: bool, output: string, error_output: string}
     */
    private function preflight(Project $project, string $repositoryUrl, array $environment): array
    {
        return $this->runProcess([
            'git',
            'ls-remote',
            '--exit-code',
            '--heads',
            $repositoryUrl,
            'refs/heads/'.$project->branch_name,
        ], null, $environment);
    }

    private function authenticatedUrl(string $gitUrl, GitConnection $gitConnection): ?string
    {
        $parts = parse_url($gitUrl);

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        if (! in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true)) {
            return null;
        }

        $username = $gitConnection->auth_type === 'username_password'
            ? (string) $gitConnection->username
            : $this->tokenUsername($gitConnection);

        $password = rawurlencode((string) $gitConnection->token);
        $path = $parts['path'] ?? '';
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        return $parts['scheme'].'://'.rawurlencode($username).':'.$password.'@'.$parts['host'].$port.$path;
    }

    private function tokenUsername(GitConnection $gitConnection): string
    {
        return match ($gitConnection->provider_type) {
            'github' => 'x-access-token',
            'gitlab' => 'oauth2',
            default => $gitConnection->username ?: 'token',
        };
    }

    private function workspaceRoot(string $sourcePath): string
    {
        return dirname($sourcePath);
    }

    /**
     * @param array<int, string> $command
     * @param array<string, string> $environment
     * @return array{successful: bool, output: string, error_output: string}
     */
    protected function runProcess(array $command, ?string $workingDirectory = null, array $environment = []): array
    {
        $process = new Process($command, $workingDirectory, $environment, timeout: config('scan.timeout_seconds'));
        $process->run();

        return [
            'successful' => $process->isSuccessful(),
            'output' => $process->getOutput(),
            'error_output' => $process->getErrorOutput(),
        ];
    }
}
