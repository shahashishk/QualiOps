<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    public function view(User $user, User $managedUser): bool
    {
        if ($managedUser->hasRole('Super Admin') && ! $user->hasRole('Super Admin')) {
            return false;
        }

        return $user->can('users.view');
    }

    public function update(User $user, User $managedUser): bool
    {
        if ($managedUser->hasRole('Super Admin') && ! $user->hasRole('Super Admin')) {
            return false;
        }

        return $user->can('users.edit');
    }

    public function delete(User $user, User $managedUser): bool
    {
        if ($managedUser->hasRole('Super Admin') && ! $user->hasRole('Super Admin')) {
            return false;
        }

        return $user->can('users.delete');
    }
}
