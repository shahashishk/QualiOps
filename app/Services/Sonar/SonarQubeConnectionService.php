<?php

namespace App\Services\Sonar;

use App\Models\SonarConnection;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SonarQubeConnectionService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {
    }

    public function create(array $validated): SonarConnection
    {
        $connection = SonarConnection::query()->create($this->payload($validated));
        $this->auditLogService->record('sonar_connection.created', $connection, auth()->user(), [], $connection->toArray());

        return $connection;
    }

    public function update(SonarConnection $sonarConnection, array $validated): SonarConnection
    {
        $oldValues = $sonarConnection->toArray();
        $sonarConnection->update($this->payload($validated, $sonarConnection));
        $this->auditLogService->record('sonar_connection.updated', $sonarConnection, auth()->user(), $oldValues, $sonarConnection->fresh()->toArray());

        return $sonarConnection->fresh();
    }

    public function updateStatus(SonarConnection $sonarConnection, bool $active): void
    {
        $oldValues = ['active' => $sonarConnection->active];
        $sonarConnection->forceFill(['active' => $active])->save();
        $this->auditLogService->record(
            $active ? 'sonar_connection.activated' : 'sonar_connection.deactivated',
            $sonarConnection,
            auth()->user(),
            $oldValues,
            ['active' => $active]
        );
    }

    public function delete(SonarConnection $sonarConnection): bool
    {
        if ($this->isUsedByProject($sonarConnection->id)) {
            return false;
        }

        $sonarConnection->delete();

        return true;
    }

    public function test(SonarConnection $sonarConnection): array
    {
        try {
            $baseUrl = rtrim($sonarConnection->sonar_url, '/');

            $response = Http::timeout(10)
                ->retry(1, 200)
                ->withBasicAuth((string) $sonarConnection->token, '')
                ->acceptJson()
                ->get($baseUrl.'/api/system/status');

            if ($response->failed()) {
                $response = Http::timeout(10)
                    ->retry(1, 200)
                    ->withBasicAuth((string) $sonarConnection->token, '')
                    ->acceptJson()
                    ->get($baseUrl.'/api/authentication/validate')
                    ->throw();
            } else {
                $response->throw();
            }

            $result = [
                'success' => true,
                'message' => 'SonarQube connection verified successfully.',
            ];
        } catch (Throwable $exception) {
            $result = [
                'success' => false,
                'message' => $this->sanitizeException($exception),
            ];
        }

        $sonarConnection->forceFill([
            'last_tested_at' => now(),
            'last_test_status' => $result['success'],
            'last_test_result' => $result['message'],
        ])->save();

        $this->auditLogService->record('sonar_connection.tested', $sonarConnection, auth()->user(), [], [
            'success' => $result['success'],
            'message' => $result['message'],
        ]);

        return $result;
    }

    private function payload(array $validated, ?SonarConnection $sonarConnection = null): array
    {
        return [
            'connection_name' => $validated['connection_name'],
            'sonar_url' => rtrim($validated['sonar_url'], '/'),
            'token' => filled($validated['token'] ?? null) ? $validated['token'] : $sonarConnection?->token,
            'active' => (bool) ($validated['active'] ?? false),
        ];
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
        if (! Schema::hasTable('projects') || ! Schema::hasColumn('projects', 'sonar_connection_id')) {
            return false;
        }

        return DB::table('projects')->where('sonar_connection_id', $connectionId)->exists();
    }
}
