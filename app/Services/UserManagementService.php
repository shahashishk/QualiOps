<?php

namespace App\Services;

use App\Services\Audit\AuditLogService;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserManagementService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {
    }

    public function create(array $validated): User
    {
        return DB::transaction(function () use ($validated) {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'is_active' => (bool) ($validated['is_active'] ?? false),
                'email_verified_at' => now(),
            ]);

            $user->syncRoles([$validated['role']]);

            $this->auditLogService->record('user.created', $user, auth()->user(), [], [
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'roles' => [$validated['role']],
            ]);

            return $user;
        });
    }

    public function update(User $user, array $validated): User
    {
        return DB::transaction(function () use ($user, $validated) {
            $oldValues = [
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'roles' => $user->roles->pluck('name')->all(),
            ];

            $payload = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'is_active' => (bool) ($validated['is_active'] ?? false),
            ];

            if (filled($validated['password'] ?? null)) {
                $payload['password'] = $validated['password'];
            }

            $user->update($payload);
            $user->syncRoles([$validated['role']]);

            $this->auditLogService->record('user.updated', $user, auth()->user(), $oldValues, [
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'roles' => [$validated['role']],
                'password' => filled($validated['password'] ?? null) ? 'updated' : null,
            ]);

            return $user->fresh('roles');
        });
    }

    public function updateStatus(User $user, bool $isActive): void
    {
        $oldValues = ['is_active' => $user->is_active];

        $user->forceFill([
            'is_active' => $isActive,
        ])->save();

        $this->auditLogService->record(
            $isActive ? 'user.activated' : 'user.deactivated',
            $user,
            auth()->user(),
            $oldValues,
            ['is_active' => $isActive]
        );
    }

    public function delete(User $user): void
    {
        DB::transaction(function () use ($user) {
            $oldValues = [
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->all(),
            ];
            $user->syncRoles([]);
            $this->auditLogService->record('user.deleted', $user, auth()->user(), $oldValues, []);
            $user->delete();
        });
    }
}
