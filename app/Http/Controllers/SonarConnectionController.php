<?php

namespace App\Http\Controllers;

use App\Http\Requests\SonarConnectionStatusRequest;
use App\Http\Requests\SonarConnectionStoreRequest;
use App\Http\Requests\SonarConnectionUpdateRequest;
use App\Models\SonarConnection;
use App\Services\Sonar\SonarQubeConnectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SonarConnectionController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', SonarConnection::class);

        return view('sonar-connections.index', [
            'breadcrumbs' => [
                ['label' => 'SonarQube Connections'],
            ],
            'connections' => SonarConnection::query()->latest()->paginate(10),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', SonarConnection::class);

        return view('sonar-connections.create', [
            'breadcrumbs' => [
                ['label' => 'SonarQube Connections', 'url' => route('sonar-connections.index')],
                ['label' => 'Create Connection'],
            ],
        ]);
    }

    public function store(SonarConnectionStoreRequest $request, SonarQubeConnectionService $service): RedirectResponse
    {
        $service->create($request->validated());

        return redirect()->route('sonar-connections.index')->with('success', 'SonarQube connection created successfully.');
    }

    public function edit(SonarConnection $sonar_connection): View
    {
        $this->authorize('update', $sonar_connection);

        return view('sonar-connections.edit', [
            'breadcrumbs' => [
                ['label' => 'SonarQube Connections', 'url' => route('sonar-connections.index')],
                ['label' => 'Edit Connection'],
            ],
            'connection' => $sonar_connection,
        ]);
    }

    public function update(SonarConnectionUpdateRequest $request, SonarConnection $sonar_connection, SonarQubeConnectionService $service): RedirectResponse
    {
        $service->update($sonar_connection, $request->validated());

        return redirect()->route('sonar-connections.index')->with('success', 'SonarQube connection updated successfully.');
    }

    public function toggleStatus(SonarConnectionStatusRequest $request, SonarConnection $sonar_connection, SonarQubeConnectionService $service): RedirectResponse
    {
        $this->authorize('update', $sonar_connection);

        $service->updateStatus($sonar_connection, (bool) $request->boolean('active'));

        return back()->with('success', $request->boolean('active') ? 'SonarQube connection activated successfully.' : 'SonarQube connection deactivated successfully.');
    }

    public function test(SonarConnection $sonar_connection, SonarQubeConnectionService $service): RedirectResponse
    {
        $this->authorize('test', $sonar_connection);

        $result = $service->test($sonar_connection);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function destroy(SonarConnection $sonar_connection, SonarQubeConnectionService $service): RedirectResponse
    {
        $this->authorize('delete', $sonar_connection);

        if (! $service->delete($sonar_connection)) {
            return back()->with('error', 'SonarQube connection cannot be deleted because it is assigned to a project.');
        }

        return back()->with('success', 'SonarQube connection deleted successfully.');
    }
}
