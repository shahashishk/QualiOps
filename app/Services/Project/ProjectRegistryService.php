<?php

namespace App\Services\Project;

use App\Models\Project;
use App\Services\Audit\AuditLogService;

class ProjectRegistryService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {
    }

    public function create(array $validated): Project
    {
        $project = Project::query()->create($this->payload($validated));

        $this->auditLogService->record('project.created', $project, auth()->user(), [], $project->toArray());

        return $project;
    }

    public function update(Project $project, array $validated): Project
    {
        $oldValues = $project->toArray();
        $project->update($this->payload($validated));
        $this->auditLogService->record('project.updated', $project, auth()->user(), $oldValues, $project->fresh()->toArray());

        return $project->fresh(['gitConnection', 'sonarConnection']);
    }

    public function updateStatus(Project $project, bool $active): void
    {
        $oldValues = ['active' => $project->active];
        $project->forceFill(['active' => $active])->save();
        $this->auditLogService->record(
            $active ? 'project.activated' : 'project.deactivated',
            $project,
            auth()->user(),
            $oldValues,
            ['active' => $active]
        );
    }

    private function payload(array $validated): array
    {
        return [
            'project_name' => $validated['project_name'],
            'description' => $validated['description'] ?? null,
            'git_connection_id' => $validated['git_connection_id'],
            'git_url' => $validated['git_url'],
            'branch_name' => $validated['branch_name'],
            'sonar_connection_id' => $validated['sonar_connection_id'],
            'sonar_project_key' => $validated['sonar_project_key'],
            'sonar_project_name' => $validated['sonar_project_name'],
            'scan_type' => $validated['scan_type'],
            'schedule_enabled' => (bool) ($validated['schedule_enabled'] ?? false),
            'schedule_frequency' => $validated['schedule_frequency'],
            'schedule_day' => ($validated['schedule_enabled'] ?? false) && $validated['schedule_frequency'] === 'weekly'
                ? ($validated['schedule_day'] ?? null)
                : null,
            'schedule_time' => ($validated['schedule_enabled'] ?? false) ? $validated['schedule_time'] : null,
            'active' => (bool) ($validated['active'] ?? false),
            'notify_pm' => (bool) ($validated['notify_pm'] ?? false),
            'notify_tl' => (bool) ($validated['notify_tl'] ?? false),
            'notify_qa' => (bool) ($validated['notify_qa'] ?? false),
            'notify_developer' => (bool) ($validated['notify_developer'] ?? false),
            'notification_custom_emails' => $this->normalizedEmails($validated['notification_custom_emails'] ?? null),
        ];
    }

    private function normalizedEmails(?string $emails): ?string
    {
        $normalized = collect(preg_split('/[\s,;]+/', (string) $emails, -1, PREG_SPLIT_NO_EMPTY) ?: [])
            ->map(fn (string $email) => strtolower(trim($email)))
            ->filter()
            ->unique()
            ->values()
            ->implode(PHP_EOL);

        return $normalized !== '' ? $normalized : null;
    }
}
