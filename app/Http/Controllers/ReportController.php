<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportExportRequest;
use App\Jobs\GenerateReportExportJob;
use App\Models\ExportHistory;
use App\Models\User;
use App\Services\Reports\ReportAccessService;
use App\Services\Reports\ReportDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request, ReportAccessService $reportAccessService): View
    {
        abort_unless($request->user()->can('reports.view'), 403);

        return view('reports.index', [
            'breadcrumbs' => [
                ['label' => 'Reports'],
            ],
            'reportTypes' => ReportDataService::REPORT_TYPES,
            'projects' => $reportAccessService->accessibleProjectsQuery($request->user())->get(),
            'scanStatuses' => ['pending', 'running', 'completed', 'failed', 'cancelled'],
            'qualityGateStatuses' => ['OK', 'ERROR', 'WARN'],
            'triggerUsers' => User::query()->orderBy('name')->get(['id', 'name']),
            'exports' => ExportHistory::query()
                ->with('user')
                ->when(
                    ! $request->user()->hasAnyRole(['Super Admin', 'Admin']),
                    fn ($query) => $query->where('user_id', $request->user()->id)
                )
                ->latest()
                ->paginate(12)
                ->withQueryString(),
        ]);
    }

    public function store(ReportExportRequest $request, ReportAccessService $reportAccessService): RedirectResponse
    {
        abort_unless($request->user()->can('reports.export'), 403);

        $validated = $request->validated();

        if (filled($validated['project_id'] ?? null)
            && ! $reportAccessService->accessibleProjectsQuery($request->user())->whereKey($validated['project_id'])->exists()) {
            abort(403);
        }

        $export = ExportHistory::query()->create([
            'user_id' => $request->user()->id,
            'report_type' => $validated['report_type'],
            'filters' => collect($validated)->only([
                'project_id',
                'branch',
                'scan_status',
                'quality_gate_status',
                'date_from',
                'date_to',
                'triggered_by',
            ])->filter(fn ($value) => $value !== null && $value !== '')->all(),
            'format' => $validated['format'],
            'file_path' => null,
            'status' => 'queued',
            'generated_at' => null,
        ]);

        GenerateReportExportJob::dispatch($export->id)->afterCommit();

        return back()->with('success', 'Report export has been queued successfully.');
    }

    public function download(ExportHistory $export_history, Request $request, ReportAccessService $reportAccessService): StreamedResponse
    {
        abort_unless($reportAccessService->canDownload($request->user(), $export_history), 403);
        abort_unless($export_history->status === 'completed' && filled($export_history->file_path), 404);
        abort_unless(str_starts_with((string) $export_history->file_path, 'exports/'), 404);
        abort_unless(Storage::disk('local')->exists($export_history->file_path), 404);

        $filename = now()->format('Ymd_His').'-'.$export_history->report_type.'.'.$export_history->format;

        return Storage::disk('local')->download($export_history->file_path, $filename);
    }
}
