<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\Sonar\SonarMetricsService;
use Illuminate\Http\RedirectResponse;

class ProjectSonarMetricsController extends Controller
{
    public function store(Project $project, SonarMetricsService $sonarMetricsService): RedirectResponse
    {
        $this->authorize('syncSonarMetrics', $project);

        $result = $sonarMetricsService->sync($project);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }
}
