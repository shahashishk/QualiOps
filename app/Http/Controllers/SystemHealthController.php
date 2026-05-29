<?php

namespace App\Http\Controllers;

use App\Services\System\SystemHealthService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemHealthController extends Controller
{
    public function index(Request $request, SystemHealthService $systemHealthService): View
    {
        abort_unless($request->user()->can('system.health.view'), 403);

        return view('system-health.index', [
            'breadcrumbs' => [
                ['label' => 'System Health'],
            ],
            'report' => $systemHealthService->getReport(),
        ]);
    }
}
