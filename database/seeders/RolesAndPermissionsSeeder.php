<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Seed the application's roles and permissions.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'roles.view',
            'roles.manage',
            'git_connections.view',
            'git_connections.manage',
            'sonar_connections.view',
            'sonar_connections.manage',
            'sonar.metrics.sync',
            'sonar.metrics.view',
            'projects.view',
            'projects.create',
            'projects.edit',
            'projects.delete',
            'scans.trigger',
            'scans.view',
            'scans.logs',
            'scans.queue.manage',
            'scans.schedule',
            'scans.retry',
            'scans.cancel',
            'scans.recover',
            'settings.manage',
            'system.health.view',
            'system.maintenance.manage',
            'notifications.view',
            'notifications.manage',
            'reports.view',
            'reports.export',
            'audit.view',
            'audit.manage',
        ];

        Permission::query()
            ->where('guard_name', 'web')
            ->whereNotIn('name', $permissions)
            ->delete();

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $roles = [
            'Super Admin' => $permissions,
            'Admin' => [
                'users.view',
                'users.create',
                'users.edit',
                'users.delete',
                'roles.view',
                'roles.manage',
                'git_connections.view',
                'git_connections.manage',
                'sonar_connections.view',
                'sonar_connections.manage',
                'sonar.metrics.sync',
                'sonar.metrics.view',
                'projects.view',
                'projects.create',
                'projects.edit',
                'projects.delete',
                'scans.trigger',
                'scans.view',
                'scans.logs',
                'scans.queue.manage',
                'scans.schedule',
                'scans.retry',
                'scans.cancel',
                'scans.recover',
                'settings.manage',
                'system.health.view',
                'system.maintenance.manage',
                'notifications.view',
                'notifications.manage',
                'reports.view',
                'reports.export',
                'audit.view',
                'audit.manage',
            ],
            'PM' => [
                'roles.view',
                'projects.view',
                'sonar.metrics.sync',
                'sonar.metrics.view',
                'scans.trigger',
                'scans.view',
                'scans.logs',
                'notifications.view',
                'reports.view',
                'reports.export',
            ],
            'TL' => [
                'projects.view',
                'projects.edit',
                'sonar.metrics.sync',
                'sonar.metrics.view',
                'scans.trigger',
                'scans.view',
                'scans.logs',
                'scans.schedule',
                'scans.retry',
                'scans.cancel',
                'scans.recover',
                'notifications.view',
                'reports.view',
                'reports.export',
            ],
            'QA' => [
                'projects.view',
                'sonar.metrics.sync',
                'sonar.metrics.view',
                'scans.trigger',
                'scans.view',
                'scans.retry',
                'scans.cancel',
                'notifications.view',
                'reports.view',
                'reports.export',
            ],
            'Developer' => [
                'projects.view',
                'sonar.metrics.view',
                'scans.trigger',
                'scans.view',
                'notifications.view',
                'reports.view',
            ],
            'Viewer' => [
                'projects.view',
                'sonar.metrics.view',
                'scans.view',
                'notifications.view',
                'reports.view',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($rolePermissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
