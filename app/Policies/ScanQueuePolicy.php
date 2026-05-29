<?php

namespace App\Policies;

use App\Models\ScanQueue;
use App\Models\User;

class ScanQueuePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('scans.view');
    }

    public function view(User $user, ScanQueue $scanQueue): bool
    {
        return $user->can('scans.view');
    }

    public function create(User $user): bool
    {
        return $user->can('scans.trigger');
    }

    public function manage(User $user): bool
    {
        return $user->can('scans.queue.manage');
    }

    public function retry(User $user, ScanQueue $scanQueue): bool
    {
        return $user->can('scans.retry');
    }

    public function cancel(User $user, ScanQueue $scanQueue): bool
    {
        return $user->can('scans.cancel');
    }

    public function recover(User $user, ScanQueue $scanQueue): bool
    {
        return $user->can('scans.recover');
    }

    public function delete(User $user, ScanQueue $scanQueue): bool
    {
        return $user->can('scans.queue.manage') && $scanQueue->queue_status !== 'running';
    }
}
