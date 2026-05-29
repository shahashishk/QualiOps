<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserStatusUpdateRequest;
use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Models\User;
use App\Services\Audit\ActivityTimelineService;
use App\Services\UserManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->with('roles')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->string('search'));

                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('users.index', [
            'breadcrumbs' => [
                ['label' => 'Users'],
            ],
            'users' => $users,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('users.create', [
            'breadcrumbs' => [
                ['label' => 'Users', 'url' => route('users.index')],
                ['label' => 'Create User'],
            ],
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }

    public function show(User $user, ActivityTimelineService $activityTimelineService): View
    {
        $this->authorize('view', $user);

        $user->load('roles');

        return view('users.show', [
            'breadcrumbs' => [
                ['label' => 'Users', 'url' => route('users.index')],
                ['label' => 'User Detail'],
            ],
            'managedUser' => $user,
            'activityTimeline' => $activityTimelineService->forEntity($user),
        ]);
    }

    public function store(UserStoreRequest $request, UserManagementService $userManagementService): RedirectResponse
    {
        $userManagementService->create($request->validated());

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        $user->load('roles');

        return view('users.edit', [
            'breadcrumbs' => [
                ['label' => 'Users', 'url' => route('users.index')],
                ['label' => 'Edit User'],
            ],
            'managedUser' => $user,
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }

    public function update(UserUpdateRequest $request, User $user, UserManagementService $userManagementService): RedirectResponse
    {
        $userManagementService->update($user, $request->validated());

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function toggleStatus(UserStatusUpdateRequest $request, User $user, UserManagementService $userManagementService): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $userManagementService->updateStatus($user, (bool) $request->boolean('is_active'));

        return back()->with('success', $request->boolean('is_active') ? 'User activated successfully.' : 'User deactivated successfully.');
    }

    public function destroy(Request $request, User $user, UserManagementService $userManagementService): RedirectResponse
    {
        $this->authorize('delete', $user);

        if ($request->user()->is($user)) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $userManagementService->delete($user);

        return back()->with('success', 'User deleted successfully.');
    }
}
