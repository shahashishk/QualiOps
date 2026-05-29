<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleStoreRequest;
use App\Http\Requests\RoleUpdateRequest;
use App\Services\RoleManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Role::class);

        $roles = Role::query()
            ->withCount('users')
            ->with('permissions')
            ->orderBy('name')
            ->paginate(10);

        return view('roles.index', [
            'breadcrumbs' => [
                ['label' => 'Roles & Permissions'],
            ],
            'roles' => $roles,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Role::class);

        return view('roles.create', [
            'breadcrumbs' => [
                ['label' => 'Roles & Permissions', 'url' => route('roles.index')],
                ['label' => 'Create Role'],
            ],
            'permissionGroups' => $this->permissionGroups(),
        ]);
    }

    public function store(RoleStoreRequest $request, RoleManagementService $roleManagementService): RedirectResponse
    {
        $roleManagementService->create($request->validated());

        return redirect()->route('roles.index')->with('success', 'Role created successfully.');
    }

    public function edit(Role $role): View
    {
        $this->authorize('update', $role);

        $role->load('permissions');

        return view('roles.edit', [
            'breadcrumbs' => [
                ['label' => 'Roles & Permissions', 'url' => route('roles.index')],
                ['label' => 'Edit Role'],
            ],
            'managedRole' => $role,
            'permissionGroups' => $this->permissionGroups(),
        ]);
    }

    public function update(RoleUpdateRequest $request, Role $role, RoleManagementService $roleManagementService): RedirectResponse
    {
        $roleManagementService->update($role, $request->validated());

        return redirect()->route('roles.index')->with('success', 'Role updated successfully.');
    }

    public function destroy(Request $request, Role $role, RoleManagementService $roleManagementService): RedirectResponse
    {
        $this->authorize('delete', $role);

        if (! $roleManagementService->delete($role)) {
            return back()->with('error', 'Role cannot be deleted while assigned to users.');
        }

        return back()->with('success', 'Role deleted successfully.');
    }

    private function permissionGroups(): array
    {
        return Permission::query()
            ->orderBy('name')
            ->get()
            ->groupBy(function (Permission $permission) {
                return ucfirst(str($permission->name)->before('.')->replace('-', ' ')->toString());
            })
            ->all();
    }
}
