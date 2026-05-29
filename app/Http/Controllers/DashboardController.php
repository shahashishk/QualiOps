<?php

namespace App\Http\Controllers;

use App\Services\Audit\ActivityTimelineService;
use App\Services\Dashboard\DashboardMetricsService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the governance dashboard.
     */
    public function __invoke(DashboardMetricsService $dashboardMetricsService, ActivityTimelineService $activityTimelineService): View
    {
        return view('dashboard.index', [
            'breadcrumbs' => [
                ['label' => 'Dashboard'],
            ],
            'metrics' => $dashboardMetricsService->getMetrics(),
            'recentActivities' => $activityTimelineService->recent(),
        ]);
    }
}
