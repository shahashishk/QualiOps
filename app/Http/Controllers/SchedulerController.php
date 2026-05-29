<?php

namespace App\Http\Controllers;

use App\Services\Scheduler\ScheduledScanService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SchedulerController extends Controller
{
    public function index(Request $request, ScheduledScanService $scheduledScanService): View
    {
        abort_unless($request->user()->can('scans.schedule'), 403);

        return view('scheduler.index', [
            'breadcrumbs' => [
                ['label' => 'Scheduler'],
            ],
            'preview' => $scheduledScanService->preview(),
            'lastRun' => $scheduledScanService->lastSchedulerRun(),
            'nextRun' => $scheduledScanService->nextScheduledProject(),
        ]);
    }
}
