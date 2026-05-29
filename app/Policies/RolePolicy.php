<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('roles.view');
    }

    public function create(User $user): bool
    {
        return $user->can('roles.manage');
    }

    public function update(User $user, Role $role): bool
    {
        if ($role->name === 'Super Admin' && ! $user->hasRole('Super Admin')) {
            return false;
        }

        return $user->can('roles.manage');
    }

    public function delete(User $user, Role $role): bool
    {
        if ($role->name === 'Super Admin' && ! $user->hasRole('Super Admin')) {
            return false;
        }

        return $user->can('roles.manage');
    }
}
