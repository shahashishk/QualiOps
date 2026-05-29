@extends('layouts.admin')

@section('title', 'Queue Detail')
@section('page_title', 'Queue Detail')

@section('content')
    <div class="row g-4">
        <div class="col-12 col-md-8">
            <div class="card admin-surface border-0">
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-12 col-md-6">
                            <div class="detail-label">Project</div>
                            <div class="fw-semibold">{{ $queue->project->project_name }}</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="detail-label">Queue Status</div>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge rounded-pill {{ match($queue->queue_status) {
                                    'pending' => 'text-bg-warning',
                                    'running' => 'text-bg-info',
                                    'completed' => 'text-bg-success',
                                    'failed' => 'text-bg-danger',
                                    default => 'text-bg-secondary',
                                } }}">
                                    {{ str($queue->queue_status)->title() }}
                                </span>
                                @if ($queue->isStuck($timeoutMinutes))
                                    <span class="badge rounded-pill text-bg-danger">Stuck Running</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="detail-label">Branch</div>
                            <div class="fw-semibold">{{ $queue->branch_name }}</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="detail-label">Scan Type</div>
                            <div class="fw-semibold">{{ str($queue->scan_type)->title() }}</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="detail-label">Queued By</div>
                            <div class="fw-semibold">{{ $queue->queuedBy?->name ?? 'Scheduler automation' }}</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="detail-label">Worker ID</div>
                            <div class="fw-semibold text-body-secondary">{{ $queue->worker_id ?: 'Pending assignment' }}</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="detail-label">Started At</div>
                            <div class="fw-semibold text-body-secondary">{{ $queue->started_at?->format('d M Y H:i') ?: 'Not started' }}</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="detail-label">Completed At</div>
                            <div class="fw-semibold text-body-secondary">{{ $queue->completed_at?->format('d M Y H:i') ?: 'Not completed' }}</div>
                        </div>
                        <div class="col-12">
                            <div class="detail-label">Failure Reason</div>
                            <div class="fw-semibold">
                                @if ($queue->failure_category)
                                    <span class="badge rounded-pill text-bg-secondary">
                                        {{ str($queue->failure_category)->replace('_', ' ')->title() }}
                                    </span>
                                @else
                                    <span class="text-body-secondary">Not applicable</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="detail-label">Remarks</div>
                            <div>{{ $queue->remarks ?: 'No remarks captured yet.' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card admin-surface border-0 mb-4">
                <div class="card-body p-4">
                    @if ($queue->isStuck($timeoutMinutes))
                        <div class="alert alert-danger d-flex align-items-start gap-2" role="alert">
                            <div class="fw-semibold">This running scan appears stuck.</div>
                            <div class="small">It has exceeded the configured timeout window and can be marked failed from the recovery controls.</div>
                        </div>
                    @endif

                    <h2 class="h5 mb-3">Linked History</h2>
                    @if ($queue->history)
                        <a href="{{ route('scan-history.show', $queue->history) }}" class="btn btn-primary w-100">Open History Record</a>
                    @else
                        <p class="text-body-secondary mb-0">No scan history has been created for this queue entry yet.</p>
                    @endif
                </div>
            </div>

            <div class="card admin-surface border-0">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Queue Controls</h2>
                    <div class="d-grid gap-2 mb-4">
                        @can('cancel', $queue)
                            @if ($queue->queue_status === 'pending')
                                <button
                                    type="button"
                                    class="btn btn-outline-danger"
                                    data-bs-toggle="modal"
                                    data-bs-target="#confirmActionModal"
                                    data-confirm-form="cancel-queue-detail-{{ $queue->id }}"
                                    data-confirm-message="Cancel this pending scan?"
                                >
                                    Cancel Pending Scan
                                </button>
                                <form id="cancel-queue-detail-{{ $queue->id }}" action="{{ route('scan-queue.cancel', $queue) }}" method="POST" class="d-none">
                                    @csrf
                                </form>
                            @endif
                        @endcan
                        @can('retry', $queue)
                            @if ($queue->queue_status === 'failed')
                                <form action="{{ route('scan-queue.retry', $queue) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-primary w-100">Retry Failed Scan</button>
                                </form>
                            @elseif (in_array($queue->queue_status, ['completed', 'cancelled'], true))
                                <form action="{{ route('scan-queue.requeue', $queue) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-primary w-100">Requeue Scan</button>
                                </form>
                            @endif
                        @endcan
                        @can('recover', $queue)
                            @if ($queue->isStuck($timeoutMinutes))
                                <form action="{{ route('scan-queue.recover', $queue) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-danger w-100">Mark Stuck Scan as Failed</button>
                                </form>
                            @endif
                        @endcan
                    </div>

                    <h2 class="h5 mb-3">Queue Timeline</h2>
                    <div class="scan-timeline">
                        <div class="scan-timeline-item is-complete">
                            <div class="scan-timeline-title">Queued</div>
                            <div class="small text-body-secondary">{{ $queue->created_at?->format('d M Y H:i') }}</div>
                        </div>
                        <div class="scan-timeline-item {{ $queue->started_at ? 'is-complete' : '' }}">
                            <div class="scan-timeline-title">Started</div>
                            <div class="small text-body-secondary">{{ $queue->started_at?->format('d M Y H:i') ?: 'Pending' }}</div>
                        </div>
                        <div class="scan-timeline-item {{ $queue->completed_at ? 'is-complete' : '' }}">
                            <div class="scan-timeline-title">Completed</div>
                            <div class="small text-body-secondary">{{ $queue->completed_at?->format('d M Y H:i') ?: 'Pending' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('partials.confirm-modal')
@endsection

@push('scripts')
    @include('partials.confirm-modal-script')
@endpush
