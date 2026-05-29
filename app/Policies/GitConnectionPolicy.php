<?php

namespace App\Policies;

use App\Models\GitConnection;
use App\Models\User;

class GitConnectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('git_connections.view');
    }

    public function create(User $user): bool
    {
        return $user->can('git_connections.manage');
    }

    public function update(User $user, GitConnection $gitConnection): bool
    {
        return $user->can('git_connections.manage');
    }

    public function delete(User $user, GitConnection $gitConnection): bool
    {
        return $user->can('git_connections.manage');
    }

    public function test(User $user, GitConnection $gitConnection): bool
    {
        return $user->can('git_connections.manage');
    }
}
