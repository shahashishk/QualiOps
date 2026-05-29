<?php

namespace App\Http\Controllers;

use App\Services\System\SystemCleanupService;
use App\Services\System\SystemHealthService;
use App\Services\System\SystemPreflightService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemMaintenanceController extends Controller
{
    public function index(
        Request $request,
        SystemHealthService $systemHealthService,
        SystemPreflightService $systemPreflightService,
        SystemCleanupService $systemCleanupService
    ): View {
        abort_unless($request->user()->can('system.maintenance.manage'), 403);

        return view('system-settings.maintenance', [
            'breadcrumbs' => [
                ['label' => 'System Settings'],
                ['label' => 'Maintenance'],
            ],
            'healthReport' => $systemHealthService->getReport(),
            'preflightReport' => $systemPreflightService->getReport(),
            'cleanupPreview' => $systemCleanupService->preview(),
        ]);
    }
}
