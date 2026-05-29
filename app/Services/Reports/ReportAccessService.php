<?php

namespace App\Services\Reports;

use App\Models\ExportHistory;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ReportAccessService
{
    public function accessibleProjectsQuery(User $user): Builder
    {
        $query = Project::query()->orderBy('project_name');

        if (! $user->can('projects.view')) {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public function canDownload(User $user, ExportHistory $exportHistory): bool
    {
        if (! $user->can('reports.view')) {
            return false;
        }

        return $user->hasAnyRole(['Super Admin', 'Admin']) || $exportHistory->user_id === $user->id;
    }
}
