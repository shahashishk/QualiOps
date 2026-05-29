<?php

namespace App\Http\Controllers;

use App\Models\NotificationHistory;
use App\Services\Reports\ReportAccessService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationHistoryController extends Controller
{
    public function index(Request $request, ReportAccessService $reportAccessService): View
    {
        abort_unless($request->user()->can('notifications.view'), 403);

        $accessibleProjectIds = $reportAccessService->accessibleProjectsQuery($request->user())->pluck('id');

        return view('notifications.history', [
            'breadcrumbs' => [
                ['label' => 'Notifications'],
                ['label' => 'History'],
            ],
            'projects' => $reportAccessService->accessibleProjectsQuery($request->user())->get(),
            'eventTypes' => [
                'scan_completed',
                'scan_failed',
                'quality_gate_failed',
                'scheduled_scan_completed',
                'scheduled_scan_failed',
                'scheduler_failed',
                'worker_down',
            ],
            'statuses' => ['queued', 'sent', 'failed'],
            'histories' => NotificationHistory::query()
                ->with(['project', 'scanHistory'])
                ->when(
                    ! $request->user()->hasAnyRole(['Super Admin', 'Admin']),
                    fn ($query) => $query->where(function ($innerQuery) use ($accessibleProjectIds) {
                        $innerQuery
                            ->whereIn('project_id', $accessibleProjectIds)
                            ->orWhereNull('project_id');
                    })
                )
                ->when(filled($request->input('project_id')), fn ($query) => $query->where('project_id', $request->integer('project_id')))
                ->when(filled($request->input('event_type')), fn ($query) => $query->where('event_type', (string) $request->string('event_type')))
                ->when(filled($request->input('status')), fn ($query) => $query->where('status', (string) $request->string('status')))
                ->when(filled($request->input('date_from')), fn ($query) => $query->whereDate('created_at', '>=', $request->date('date_from')))
                ->when(filled($request->input('date_to')), fn ($query) => $query->whereDate('created_at', '<=', $request->date('date_to')))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
        ]);
    }
}
