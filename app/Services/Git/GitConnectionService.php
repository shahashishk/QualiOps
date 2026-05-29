<?php

namespace App\Services\Git;

use App\Models\GitConnection;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Throwable;

class GitConnectionService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {
    }

    public function create(array $validated): GitConnection
    {
        $connection = GitConnection::query()->create($this->payload($validated));
        $this->auditLogService->record('git_connection.created', $connection, auth()->user(), [], $connection->toArray());

        return $connection;
    }

    public function update(GitConnection $gitConnection, array $validated): GitConnection
    {
        $oldValues = $gitConnection->toArray();
        $gitConnection->update($this->payload($validated, $gitConnection));
        $this->auditLogService->record('git_connection.updated', $gitConnection, auth()->user(), $oldValues, $gitConnection->fresh()->toArray());

        return $gitConnection->fresh();
    }

    public function updateStatus(GitConnection $gitConnection, bool $active): void
    {
        $oldValues = ['active' => $gitConnection->active];
        $gitConnection->forceFill(['active' => $active])->save();
        $this->auditLogService->record(
            $active ? 'git_connection.activated' : 'git_connection.deactivated',
            $gitConnection,
            auth()->user(),
            $oldValues,
            ['active' => $active]
        );
    }

    public function delete(GitConnection $gitConnection): bool
    {
        if ($this->isUsedByProject($gitConnection->id)) {
            return false;
        }

        $gitConnection->delete();

        return true;
    }

    public function test(GitConnection $gitConnection): array
    {
        try {
            $result = match ($gitConnection->provider_type) {
                'gitlab' => $this->testGitLab($gitConnection),
                'github' => $this->testGitHub($gitConnection),
                'bitbucket' => $this->testBitbucket($gitConnection),
                default => $this->testGeneric($gitConnection),
            };
        } catch (Throwable $exception) {
            $result = [
                'success' => false,
                'message' => $this->sanitizeException($exception),
            ];
        }

        $gitConnection->forceFill([
            'last_tested_at' => now(),
            'last_test_status' => $result['success'],
            'last_test_result' => $result['message'],
        ])->save();

        $this->auditLogService->record('git_connection.tested', $gitConnection, auth()->user(), [], [
            'success' => $result['success'],
            'message' => $result['message'],
        ]);

        return $result;
    }

    private function payload(array $validated, ?GitConnection $gitConnection = null): array
    {
        return [
            'connection_name' => $validated['connection_name'],
            'provider_type' => $validated['provider_type'],
            'base_url' => rtrim($validated['base_url'], '/'),
            'auth_type' => $validated['auth_type'],
            'username' => $validated['username'] ?? null,
            'token' => filled($validated['token'] ?? null) ? $validated['token'] : $gitConnection?->token,
            'ssh_private_key' => filled($validated['ssh_private_key'] ?? null) ? $validated['ssh_private_key'] : $gitConnection?->ssh_private_key,
            'active' => (bool) ($validated['active'] ?? false),
        ];
    }

    private function testGitLab(GitConnection $gitConnection): array
    {
        $response = $this->requestFor($gitConnection)
            ->acceptJson()
            ->get(rtrim($gitConnection->base_url, '/').'/api/v4/version')
            ->throw();

        return [
            'success' => true,
            'message' => 'GitLab connection verified successfully.',
        ];
    }

    private function testGitHub(GitConnection $gitConnection): array
    {
        $apiBase = $this->resolveGitHubApiBase($gitConnection->base_url);

        $this->requestFor($gitConnection)
            ->acceptJson()
            ->withHeaders(['User-Agent' => 'QualiOps'])
            ->get($apiBase.'/user')
            ->throw();

        return [
            'success' => true,
            'message' => 'GitHub connection verified successfully.',
        ];
    }

    private function testBitbucket(GitConnection $gitConnection): array
    {
        $apiBase = $this->resolveBitbucketApiBase($gitConnection->base_url);

        $this->requestFor($gitConnection)
            ->acceptJson()
            ->get($apiBase.'/user')
            ->throw();

        return [
            'success' => true,
            'message' => 'Bitbucket connection verified successfully.',
        ];
    }

    private function testGeneric(GitConnection $gitConnection): array
    {
        return [
            'success' => filter_var($gitConnection->base_url, FILTER_VALIDATE_URL) !== false,
            'message' => 'Generic Git connection validated by URL format only.',
        ];
    }

    private function requestFor(GitConnection $gitConnection): PendingRequest
    {
        $request = Http::timeout(10)->retry(1, 200);

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
            return 'Connection test failed with HTTP '.$exception->response->status().'.';
        }

        return 'Connection test failed. Please review the endpoint and credentials.';
    }

    private function isUsedByProject(int $connectionId): bool
    {
        if (! Schema::hasTable('projects') || ! Schema::hasColumn('projects', 'git_connection_id')) {
            return false;
        }

        return DB::table('projects')->where('git_connection_id', $connectionId)->exists();
    }
}
