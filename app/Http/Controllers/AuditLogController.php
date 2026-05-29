<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\Audit\ActivityTimelineService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request, ActivityTimelineService $activityTimelineService): View
    {
        abort_unless($request->user()->can('audit.view'), 403);

        return view('audit-logs.index', [
            'breadcrumbs' => [
                ['label' => 'Audit Logs'],
            ],
            'users' => User::query()->orderBy('name')->get(),
            'entityTypes' => AuditLog::query()->select('entity_type')->whereNotNull('entity_type')->distinct()->orderBy('entity_type')->pluck('entity_type'),
            'actions' => AuditLog::query()->select('action')->distinct()->orderBy('action')->pluck('action'),
            'logs' => $activityTimelineService->paginated($request->only(['user_id', 'entity_type', 'action', 'date_from', 'date_to'])),
        ]);
    }

    public function show(Request $request, AuditLog $audit_log, ActivityTimelineService $activityTimelineService): View
    {
        abort_unless($request->user()->can('audit.view'), 403);

        $audit_log->load('user');

        return view('audit-logs.show', [
            'breadcrumbs' => [
                ['label' => 'Audit Logs', 'url' => route('audit-logs.index')],
                ['label' => 'Audit Detail'],
            ],
            'entry' => $activityTimelineService->present($audit_log),
        ]);
    }
}
