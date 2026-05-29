<?php

namespace App\Services\Sonar;

use App\Events\QualityGateFailedEvent;
use App\Models\Project;
use App\Models\SonarMetricSnapshot;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class SonarMetricsService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {
    }

    private const METRIC_KEYS = [
        'bugs',
        'vulnerabilities',
        'code_smells',
        'security_hotspots',
        'coverage',
        'duplicated_lines_density',
        'reliability_rating',
        'security_rating',
        'sqale_rating',
        'sqale_index',
    ];

    /**
     * @return array<string, mixed>
     */
    public function sync(Project $project, bool $dryRun = false): array
    {
        try {
            $metricsResponse = $this->metricsResponse($project);
            $qualityGateResponse = $this->qualityGateResponse($project);

            $payload = $this->normalizedPayload($project, $metricsResponse, $qualityGateResponse);

            if ($dryRun) {
                $this->auditLogService->record('metrics.synced_dry_run', $project, auth()->user(), [], $payload);

                return [
                    'success' => true,
                    'dry_run' => true,
                    'message' => 'SonarQube metrics fetched successfully in dry-run mode.',
                    'payload' => $payload,
                    'snapshot' => null,
                ];
            }

            $snapshot = SonarMetricSnapshot::query()->create($payload);
            $this->auditLogService->record('metrics.synced', $project, auth()->user(), [], $payload);

            if ($snapshot->quality_gate_status !== 'OK') {
                event(new QualityGateFailedEvent($project->fresh(['sonarConnection']), $snapshot));
            }

            return [
                'success' => true,
                'dry_run' => false,
                'message' => 'SonarQube metrics synchronized successfully.',
                'payload' => $payload,
                'snapshot' => $snapshot,
            ];
        } catch (Throwable $exception) {
            $this->auditLogService->record('metrics.sync_failed', $project, auth()->user(), [], [
                'message' => $this->sanitizeException($exception),
            ]);

            return [
                'success' => false,
                'dry_run' => $dryRun,
                'message' => $this->sanitizeException($exception),
                'payload' => null,
                'snapshot' => null,
            ];
        }
    }

    private function metricsResponse(Project $project): Response
    {
        return $this->request($project)
            ->get($this->baseUrl($project).'/api/measures/component', [
                'component' => $project->sonar_project_key,
                'metricKeys' => implode(',', self::METRIC_KEYS),
            ])
            ->throw();
    }

    private function qualityGateResponse(Project $project): Response
    {
        return $this->request($project)
            ->get($this->baseUrl($project).'/api/qualitygates/project_status', [
                'projectKey' => $project->sonar_project_key,
            ])
            ->throw();
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizedPayload(Project $project, Response $metricsResponse, Response $qualityGateResponse): array
    {
        $measures = collect($metricsResponse->json('component.measures', []))
            ->mapWithKeys(fn (array $item) => [$item['metric'] => $item['value'] ?? null]);

        if ($measures->isEmpty()) {
            throw new \RuntimeException('No SonarQube metrics were returned for the configured project key.');
        }

        $qualityGateStatus = $qualityGateResponse->json('projectStatus.status');

        if (! filled($qualityGateStatus)) {
            throw new \RuntimeException('Quality gate status was not returned by the SonarQube API.');
        }

        return [
            'project_id' => $project->id,
            'sonar_project_key' => $project->sonar_project_key,
            'bugs' => $this->nullableInt($measures->get('bugs')),
            'vulnerabilities' => $this->nullableInt($measures->get('vulnerabilities')),
            'code_smells' => $this->nullableInt($measures->get('code_smells')),
            'security_hotspots' => $this->nullableInt($measures->get('security_hotspots')),
            'coverage' => $this->nullableFloat($measures->get('coverage')),
            'duplicated_lines_density' => $this->nullableFloat($measures->get('duplicated_lines_density')),
            'reliability_rating' => $this->ratingFromScore($measures->get('reliability_rating')),
            'security_rating' => $this->ratingFromScore($measures->get('security_rating')),
            'maintainability_rating' => $this->ratingFromScore($measures->get('sqale_rating')),
            'technical_debt' => $this->nullableInt($measures->get('sqale_index')),
            'quality_gate_status' => strtoupper((string) $qualityGateStatus),
            'synced_at' => now(),
        ];
    }

    private function request(Project $project)
    {
        return Http::timeout(15)
            ->retry(1, 200)
            ->withBasicAuth((string) $project->sonarConnection->token, '')
            ->acceptJson();
    }

    private function baseUrl(Project $project): string
    {
        return rtrim((string) $project->sonarConnection->sonar_url, '/');
    }

    private function sanitizeException(Throwable $exception): string
    {
        if ($exception instanceof RequestException && $exception->response) {
            return match ($exception->response->status()) {
                401, 403 => 'SonarQube authentication failed. Please verify the configured connection token.',
                404 => 'SonarQube project key was not found on the configured server.',
                default => 'SonarQube API request failed with HTTP '.$exception->response->status().'.',
            };
        }

        $message = $exception->getMessage();

        if (str_contains(strtolower($message), 'timed out')) {
            return 'SonarQube API request timed out.';
        }

        if (str_contains(strtolower($message), 'could not resolve host')
            || str_contains(strtolower($message), 'connection refused')) {
            return 'SonarQube server is unreachable.';
        }

        return 'SonarQube metrics synchronization failed.';
    }

    private function nullableInt(mixed $value): ?int
    {
        return is_numeric($value) ? (int) round((float) $value) : null;
    }

    private function nullableFloat(mixed $value): ?float
    {
        return is_numeric($value) ? round((float) $value, 2) : null;
    }

    private function ratingFromScore(mixed $value): ?string
    {
        if (! is_numeric($value)) {
            return null;
        }

        return match ((int) round((float) $value)) {
            1 => 'A',
            2 => 'B',
            3 => 'C',
            4 => 'D',
            5 => 'E',
            default => null,
        };
    }
}
