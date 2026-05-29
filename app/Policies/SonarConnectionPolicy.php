<?php

namespace App\Policies;

use App\Models\SonarConnection;
use App\Models\User;

class SonarConnectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sonar_connections.view');
    }

    public function create(User $user): bool
    {
        return $user->can('sonar_connections.manage');
    }

    public function update(User $user, SonarConnection $sonarConnection): bool
    {
        return $user->can('sonar_connections.manage');
    }

    public function delete(User $user, SonarConnection $sonarConnection): bool
    {
        return $user->can('sonar_connections.manage');
    }

    public function test(User $user, SonarConnection $sonarConnection): bool
    {
        return $user->can('sonar_connections.manage');
    }
}
