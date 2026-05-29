<?php

namespace App\Services\Notification;

use App\Models\Project;
use App\Models\User;

class ProjectNotificationRecipientService
{
    /**
     * @return array{users: array<int, \App\Models\User>, emails: array<int, string>}
     */
    public function recipients(Project $project): array
    {
        $roleMap = [
            'PM' => $project->notify_pm,
            'TL' => $project->notify_tl,
            'QA' => $project->notify_qa,
            'Developer' => $project->notify_developer,
        ];

        $roles = collect($roleMap)
            ->filter(fn (bool $enabled) => $enabled)
            ->keys()
            ->values()
            ->all();

        $users = $roles === []
            ? collect()
            : User::query()
                ->where('is_active', true)
                ->whereHas('roles', fn ($query) => $query->whereIn('name', $roles))
                ->orderBy('email')
                ->get();

        $emails = collect(preg_split('/[\s,;]+/', (string) $project->notification_custom_emails, -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn (string $email) => strtolower(trim($email)))
            ->filter(fn (string $email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();

        return [
            'users' => $users->all(),
            'emails' => $emails,
        ];
    }
}
