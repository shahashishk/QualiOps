<?php

namespace App\Services\Scan;

use App\Models\GitConnection;
use App\Models\Project;
use Symfony\Component\Process\Process;

class GitCloneService
{
    public function __construct(
        private readonly WorkspaceService $workspaceService,
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

        if ($gitConnection->auth_type === 'token' || $gitConnection->auth_type === 'username_password') {
            $command[] = $this->authenticatedUrl($project->git_url, $gitConnection);
        } else {
            $keyPath = $this->workspaceService->writeSshKey($this->workspaceRoot($sourcePath), (string) $gitConnection->ssh_private_key);
            $environment['GIT_SSH_COMMAND'] = 'ssh -i "'.$keyPath.'" -o IdentitiesOnly=yes -o StrictHostKeyChecking=no';
            $command[] = $project->git_url;
        }

        $command[] = $sourcePath;

        $process = new Process($command, timeout: config('scan.timeout_seconds'), env: $environment);
        $process->run();

        return [
            'successful' => $process->isSuccessful(),
            'output' => $process->getOutput(),
            'error_output' => $process->getErrorOutput(),
            'source_path' => $sourcePath,
        ];
    }

    private function authenticatedUrl(string $gitUrl, GitConnection $gitConnection): string
    {
        $parts = parse_url($gitUrl);

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            return $gitUrl;
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
}
