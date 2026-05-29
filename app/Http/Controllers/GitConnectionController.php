<?php

namespace App\Http\Controllers;

use App\Http\Requests\GitConnectionStatusRequest;
use App\Http\Requests\GitConnectionStoreRequest;
use App\Http\Requests\GitConnectionUpdateRequest;
use App\Models\GitConnection;
use App\Services\Git\GitConnectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GitConnectionController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', GitConnection::class);

        return view('git-connections.index', [
            'breadcrumbs' => [
                ['label' => 'Git Connections'],
            ],
            'connections' => GitConnection::query()->latest()->paginate(10),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', GitConnection::class);

        return view('git-connections.create', [
            'breadcrumbs' => [
                ['label' => 'Git Connections', 'url' => route('git-connections.index')],
                ['label' => 'Create Connection'],
            ],
        ]);
    }

    public function store(GitConnectionStoreRequest $request, GitConnectionService $service): RedirectResponse
    {
        $service->create($request->validated());

        return redirect()->route('git-connections.index')->with('success', 'Git connection created successfully.');
    }

    public function edit(GitConnection $git_connection): View
    {
        $this->authorize('update', $git_connection);

        return view('git-connections.edit', [
            'breadcrumbs' => [
                ['label' => 'Git Connections', 'url' => route('git-connections.index')],
                ['label' => 'Edit Connection'],
            ],
            'connection' => $git_connection,
        ]);
    }

    public function update(GitConnectionUpdateRequest $request, GitConnection $git_connection, GitConnectionService $service): RedirectResponse
    {
        $service->update($git_connection, $request->validated());

        return redirect()->route('git-connections.index')->with('success', 'Git connection updated successfully.');
    }

    public function toggleStatus(GitConnectionStatusRequest $request, GitConnection $git_connection, GitConnectionService $service): RedirectResponse
    {
        $this->authorize('update', $git_connection);

        $service->updateStatus($git_connection, (bool) $request->boolean('active'));

        return back()->with('success', $request->boolean('active') ? 'Git connection activated successfully.' : 'Git connection deactivated successfully.');
    }

    public function test(GitConnection $git_connection, GitConnectionService $service): RedirectResponse
    {
        $this->authorize('test', $git_connection);

        $result = $service->test($git_connection);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function destroy(GitConnection $git_connection, GitConnectionService $service): RedirectResponse
    {
        $this->authorize('delete', $git_connection);

        if (! $service->delete($git_connection)) {
            return back()->with('error', 'Git connection cannot be deleted because it is assigned to a project.');
        }

        return back()->with('success', 'Git connection deleted successfully.');
    }
}
