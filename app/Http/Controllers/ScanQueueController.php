<?php

namespace App\Http\Controllers;

use App\Exceptions\DuplicateActiveScanException;
use App\Models\Project;
use App\Models\ScanQueue;
use App\Services\Scan\ScanQueueManagementService;
use App\Services\Scan\ScanTrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScanQueueController extends Controller
{
    public function index(Request $request, ScanTrackingService $scanTrackingService): View
    {
        $this->authorize('viewAny', ScanQueue::class);

        return view('scan-queue.index', [
            'breadcrumbs' => [
                ['label' => 'Scan Queue'],
            ],
            'projects' => Project::query()->orderBy('project_name')->get(),
            'statuses' => ['pending', 'running', 'completed', 'failed', 'cancelled'],
            'failureCategories' => app(ScanQueueManagementService::class)->failureCategories(),
            'timeoutMinutes' => max(1, (int) ceil(config('scan.timeout_seconds', 1800) / 60)),
            'queues' => $scanTrackingService->queueQuery($request->only(['project_id', 'status', 'failure_category', 'branch', 'date_from', 'date_to']))
                ->paginate(12)
                ->withQueryString(),
        ]);
    }

    public function show(ScanQueue $scan_queue): View
    {
        $this->authorize('view', $scan_queue);

        $scan_queue->load(['project', 'queuedBy', 'history.logs']);

        return view('scan-queue.show', [
            'breadcrumbs' => [
                ['label' => 'Scan Queue', 'url' => route('scan-queue.index')],
                ['label' => 'Queue Detail'],
            ],
            'queue' => $scan_queue,
            'timeoutMinutes' => max(1, (int) ceil(config('scan.timeout_seconds', 1800) / 60)),
        ]);
    }

    public function store(Project $project, Request $request, ScanTrackingService $scanTrackingService): RedirectResponse
    {
        $this->authorize('create', ScanQueue::class);

        try {
            $queue = $scanTrackingService->queueScan($project, $request->user());
        } catch (DuplicateActiveScanException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('scan-queue.show', $queue)
            ->with('success', 'Scan has been added to the queue successfully.');
    }

    public function cancel(ScanQueue $scan_queue, Request $request, ScanQueueManagementService $scanQueueManagementService): RedirectResponse
    {
        $this->authorize('cancel', $scan_queue);

        try {
            $scanQueueManagementService->cancel($scan_queue, $request->user());
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Pending scan cancelled successfully.');
    }

    public function retry(ScanQueue $scan_queue, Request $request, ScanQueueManagementService $scanQueueManagementService): RedirectResponse
    {
        $this->authorize('retry', $scan_queue);

        try {
            $queue = $scanQueueManagementService->retryFailed($scan_queue, $request->user());
        } catch (DuplicateActiveScanException|\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('scan-queue.show', $queue)->with('success', 'Failed scan queued for retry.');
    }

    public function requeue(ScanQueue $scan_queue, Request $request, ScanQueueManagementService $scanQueueManagementService): RedirectResponse
    {
        $this->authorize('retry', $scan_queue);

        try {
            $queue = $scanQueueManagementService->requeue($scan_queue, $request->user());
        } catch (DuplicateActiveScanException|\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('scan-queue.show', $queue)->with('success', 'Scan requeued successfully.');
    }

    public function recover(ScanQueue $scan_queue, Request $request, ScanQueueManagementService $scanQueueManagementService): RedirectResponse
    {
        $this->authorize('recover', $scan_queue);

        $timeoutMinutes = max(1, (int) ceil(config('scan.timeout_seconds', 1800) / 60));

        try {
            $scanQueueManagementService->markTimedOut($scan_queue->load('history.logs'), $timeoutMinutes, $request->user());
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Running scan marked as failed due to timeout.');
    }
}
