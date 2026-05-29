<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectBranchFetchRequest;
use App\Http\Requests\ProjectStatusRequest;
use App\Http\Requests\ProjectStoreRequest;
use App\Http\Requests\ProjectUpdateRequest;
use App\Models\GitConnection;
use App\Models\Project;
use App\Models\SonarConnection;
use App\Services\Audit\ActivityTimelineService;
use App\Services\Git\GitBranchService;
use App\Services\Project\ProjectRegistryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Project::class);

        return view('projects.index', [
            'breadcrumbs' => [
                ['label' => 'Projects'],
            ],
            'projects' => Project::query()
                ->with(['gitConnection', 'sonarConnection'])
                ->latest()
                ->paginate(10),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Project::class);

        return view('projects.create', [
            'breadcrumbs' => [
                ['label' => 'Projects', 'url' => route('projects.index')],
                ['label' => 'Create Project'],
            ],
            ...$this->formData(),
        ]);
    }

    public function store(ProjectStoreRequest $request, ProjectRegistryService $service): RedirectResponse
    {
        $service->create($request->validated());

        return redirect()->route('projects.index')->with('success', 'Project registered successfully.');
    }

    public function show(Project $project, ActivityTimelineService $activityTimelineService): View
    {
        $this->authorize('view', $project);

        $project->load([
            'gitConnection',
            'sonarConnection',
            'latestSonarMetricSnapshot',
        ]);

        return view('projects.show', [
            'breadcrumbs' => [
                ['label' => 'Projects', 'url' => route('projects.index')],
                ['label' => 'Project Details'],
            ],
            'project' => $project,
            'metricSnapshots' => $project->sonarMetricSnapshots()
                ->latest('synced_at')
                ->paginate(10, ['*'], 'metrics_page')
                ->withQueryString(),
            'activityTimeline' => $activityTimelineService->forEntity($project),
        ]);
    }

    public function edit(Project $project): View
    {
        $this->authorize('update', $project);

        $project->load(['gitConnection', 'sonarConnection']);

        return view('projects.edit', [
            'breadcrumbs' => [
                ['label' => 'Projects', 'url' => route('projects.index')],
                ['label' => 'Edit Project'],
            ],
            'project' => $project,
            ...$this->formData($project),
        ]);
    }

    public function update(ProjectUpdateRequest $request, Project $project, ProjectRegistryService $service): RedirectResponse
    {
        $service->update($project, $request->validated());

        return redirect()->route('projects.index')->with('success', 'Project updated successfully.');
    }

    public function toggleStatus(ProjectStatusRequest $request, Project $project, ProjectRegistryService $service): RedirectResponse
    {
        $this->authorize('delete', $project);

        $service->updateStatus($project, (bool) $request->boolean('active'));

        return back()->with('success', $request->boolean('active') ? 'Project activated successfully.' : 'Project deactivated successfully.');
    }

    public function fetchBranches(ProjectBranchFetchRequest $request, GitBranchService $branchService): JsonResponse
    {
        $gitConnection = GitConnection::query()->findOrFail($request->integer('git_connection_id'));
        $result = $branchService->fetchBranches($gitConnection, (string) $request->string('git_url'));

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function testGitUrl(ProjectBranchFetchRequest $request, GitBranchService $branchService): JsonResponse
    {
        $gitConnection = GitConnection::query()->findOrFail($request->integer('git_connection_id'));
        $result = $branchService->fetchBranches($gitConnection, (string) $request->string('git_url'));

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
        ], $result['success'] ? 200 : 422);
    }

    private function formData(?Project $project = null): array
    {
        return [
            'gitConnections' => GitConnection::query()
                ->when($project, function ($query) use ($project) {
                    $query->where('active', true)->orWhereKey($project->git_connection_id);
                }, function ($query) {
                    $query->where('active', true);
                })
                ->orderBy('connection_name')
                ->get(),
            'sonarConnections' => SonarConnection::query()
                ->when($project, function ($query) use ($project) {
                    $query->where('active', true)->orWhereKey($project->sonar_connection_id);
                }, function ($query) {
                    $query->where('active', true);
                })
                ->orderBy('connection_name')
                ->get(),
            'scanTypes' => ['auto', 'dotnet', 'angular', 'flutter', 'node', 'generic', 'mixed'],
            'scheduleFrequencies' => ['manual', 'daily', 'weekly'],
            'scheduleDays' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
        ];
    }
}
