<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ScanHistory;
use App\Services\Audit\ActivityTimelineService;
use App\Services\Scan\ScanTrackingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScanHistoryController extends Controller
{
    public function index(Request $request, ScanTrackingService $scanTrackingService): View
    {
        $this->authorize('viewAny', ScanHistory::class);

        return view('scan-history.index', [
            'breadcrumbs' => [
                ['label' => 'Scan History'],
            ],
            'projects' => Project::query()->orderBy('project_name')->get(),
            'statuses' => ['pending', 'running', 'completed', 'failed', 'cancelled'],
            'histories' => $scanTrackingService->historyQuery($request->only(['project_id', 'status', 'branch', 'date_from', 'date_to']))
                ->paginate(12)
                ->withQueryString(),
        ]);
    }

    public function show(ScanHistory $scan_history, ActivityTimelineService $activityTimelineService): View
    {
        $this->authorize('view', $scan_history);

        $scan_history->load(['project', 'queue', 'triggeredBy', 'logs']);

        return view('scan-history.show', [
            'breadcrumbs' => [
                ['label' => 'Scan History', 'url' => route('scan-history.index')],
                ['label' => 'History Detail'],
            ],
            'history' => $scan_history,
            'activityTimeline' => $activityTimelineService->forEntity($scan_history),
        ]);
    }

    public function logs(ScanHistory $scan_history): View
    {
        $this->authorize('viewLogs', $scan_history);

        $scan_history->load(['project', 'logs']);

        return view('scan-history.logs', [
            'breadcrumbs' => [
                ['label' => 'Scan History', 'url' => route('scan-history.index')],
                ['label' => 'Logs Viewer'],
            ],
            'history' => $scan_history,
        ]);
    }
}
