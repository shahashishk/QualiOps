<?php

namespace App\Policies;

use App\Models\ScanHistory;
use App\Models\User;

class ScanHistoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('scans.view');
    }

    public function view(User $user, ScanHistory $scanHistory): bool
    {
        return $user->can('scans.view');
    }

    public function viewLogs(User $user, ScanHistory $scanHistory): bool
    {
        return $user->can('scans.logs');
    }
}
