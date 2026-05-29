<?php

namespace App\Services\Git;

use App\Models\GitConnection;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;

class GitBranchService
{
    public function __construct(
        private readonly GitRepositoryResolver $repositoryResolver,
    ) {
    }

    public function fetchBranches(GitConnection $gitConnection, string $gitUrl): array
    {
        if ($gitConnection->provider_type === 'generic') {
            return [
                'success' => true,
                'branches' => [],
                'default_branch' => null,
                'message' => 'Generic Git repositories require manual branch entry at this stage.',
                'requires_manual_selection' => true,
            ];
        }

        $repository = $this->repositoryResolver->resolve($gitConnection->provider_type, $gitUrl);

        if (! $repository) {
            return [
                'success' => false,
                'branches' => [],
                'default_branch' => null,
                'message' => 'Unable to resolve the repository from the provided Git URL.',
                'requires_manual_selection' => true,
            ];
        }

        try {
            $branches = match ($gitConnection->provider_type) {
                'gitlab' => $this->fetchGitLabBranches($gitConnection, $repository),
                'github' => $this->fetchGitHubBranches($gitConnection, $repository),
                'bitbucket' => $this->fetchBitbucketBranches($gitConnection, $repository),
                default => [],
            };
        } catch (Throwable $exception) {
            return [
                'success' => false,
                'branches' => [],
                'default_branch' => null,
                'message' => $this->sanitizeException($exception),
                'requires_manual_selection' => true,
            ];
        }

        sort($branches);

        $defaultBranch = in_array('uat', $branches, true) ? 'uat' : null;

        return [
            'success' => true,
            'branches' => $branches,
            'default_branch' => $defaultBranch,
            'message' => $defaultBranch
                ? 'Branches fetched successfully. The "uat" branch was preselected.'
                : 'Branches fetched successfully. Please select the required branch manually.',
            'requires_manual_selection' => $defaultBranch === null,
        ];
    }

    private function fetchGitLabBranches(GitConnection $gitConnection, array $repository): array
    {
        $response = $this->requestFor($gitConnection)
            ->acceptJson()
            ->get(rtrim($gitConnection->base_url, '/').'/api/v4/projects/'.$repository['project_path'].'/repository/branches')
            ->throw()
            ->json();

        return collect($response)->pluck('name')->filter()->values()->all();
    }

    private function fetchGitHubBranches(GitConnection $gitConnection, array $repository): array
    {
        $apiBase = $this->resolveGitHubApiBase($gitConnection->base_url);

        $response = $this->requestFor($gitConnection)
            ->acceptJson()
            ->withHeaders(['User-Agent' => 'QualiOps'])
            ->get($apiBase.'/repos/'.$repository['owner'].'/'.$repository['repo'].'/branches')
            ->throw()
            ->json();

        return collect($response)->pluck('name')->filter()->values()->all();
    }

    private function fetchBitbucketBranches(GitConnection $gitConnection, array $repository): array
    {
        $apiBase = $this->resolveBitbucketApiBase($gitConnection->base_url);

        $response = $this->requestFor($gitConnection)
            ->acceptJson()
            ->get($apiBase.'/repositories/'.$repository['workspace'].'/'.$repository['repo_slug'].'/refs/branches')
            ->throw()
            ->json();

        return collect($response['values'] ?? [])->pluck('name')->filter()->values()->all();
    }

    private function requestFor(GitConnection $gitConnection): PendingRequest
    {
        $request = Http::timeout(12)->retry(1, 200);

        return match ($gitConnection->auth_type) {
            'username_password' => $request->withBasicAuth((string) $gitConnection->username, (string) $gitConnection->token),
            'token' => $this->tokenRequest($request, $gitConnection),
            default => $request,
        };
    }

    private function tokenRequest(PendingRequest $request, GitConnection $gitConnection): PendingRequest
    {
        return match ($gitConnection->provider_type) {
            'gitlab' => $request->withHeaders(['PRIVATE-TOKEN' => (string) $gitConnection->token]),
            'github' => $request->withToken((string) $gitConnection->token),
            'bitbucket' => $request->withHeaders(['Authorization' => 'Bearer '.$gitConnection->token]),
            default => $request->withToken((string) $gitConnection->token),
        };
    }

    private function resolveGitHubApiBase(string $baseUrl): string
    {
        $trimmed = rtrim($baseUrl, '/');

        if (str_contains($trimmed, 'api.github.com')) {
            return $trimmed;
        }

        if (str_contains($trimmed, 'github.com')) {
            return 'https://api.github.com';
        }

        return $trimmed.'/api/v3';
    }

    private function resolveBitbucketApiBase(string $baseUrl): string
    {
        $trimmed = rtrim($baseUrl, '/');

        if (str_contains($trimmed, 'api.bitbucket.org/2.0')) {
            return $trimmed;
        }

        if (str_contains($trimmed, 'api.bitbucket.org')) {
            return $trimmed.'/2.0';
        }

        if (str_contains($trimmed, 'bitbucket.org')) {
            return 'https://api.bitbucket.org/2.0';
        }

        return $trimmed.'/2.0';
    }

    private function sanitizeException(Throwable $exception): string
    {
        if ($exception instanceof RequestException && $exception->response) {
            return 'Branch fetch failed with HTTP '.$exception->response->status().'.';
        }

        return 'Unable to fetch branches for the selected repository.';
    }
}
