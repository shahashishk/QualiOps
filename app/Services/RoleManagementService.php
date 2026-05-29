<?php

namespace App\Services;

use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class RoleManagementService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {
    }

    public function create(array $validated): Role
    {
        return DB::transaction(function () use ($validated) {
            $role = Role::query()->create([
                'name' => $validated['name'],
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($validated['permissions'] ?? []);

            $this->auditLogService->record('role.created', $role, auth()->user(), [], [
                'name' => $role->name,
                'permissions' => $validated['permissions'] ?? [],
            ]);

            return $role;
        });
    }

    public function update(Role $role, array $validated): Role
    {
        return DB::transaction(function () use ($role, $validated) {
            $oldValues = [
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->all(),
            ];

            $role->update([
                'name' => $validated['name'],
            ]);

            $role->syncPermissions($validated['permissions'] ?? []);

            $this->auditLogService->record('role.updated', $role, auth()->user(), $oldValues, [
                'name' => $role->name,
                'permissions' => $validated['permissions'] ?? [],
            ]);

            return $role->fresh('permissions');
        });
    }

    public function delete(Role $role): bool
    {
        if ($role->users()->exists()) {
            return false;
        }

        DB::transaction(function () use ($role) {
            $oldValues = [
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->all(),
            ];
            $role->syncPermissions([]);
            $this->auditLogService->record('role.deleted', $role, auth()->user(), $oldValues, []);
            $role->delete();
        });

        return true;
    }
}
