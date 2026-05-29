@extends('layouts.admin')

@section('title', 'Scan Queue')
@section('page_title', 'Scan Queue')

@section('content')
    <form method="GET" action="{{ route('scan-queue.index') }}" class="card admin-surface border-0 mb-4">
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-12 col-md-3">
                    <label for="project_id" class="form-label">Project</label>
                    <select id="project_id" name="project_id" class="form-select">
                        <option value="">All projects</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" @selected(request('project_id') == $project->id)>{{ $project->project_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">All statuses</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->title() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label for="failure_category" class="form-label">Failure Reason</label>
                    <select id="failure_category" name="failure_category" class="form-select">
                        <option value="">All reasons</option>
                        @foreach ($failureCategories as $failureCategory)
                            <option value="{{ $failureCategory }}" @selected(request('failure_category') === $failureCategory)>{{ str($failureCategory)->replace('_', ' ')->title() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label for="branch" class="form-label">Branch</label>
                    <input type="text" id="branch" name="branch" value="{{ request('branch') }}" class="form-control">
                </div>
                <div class="col-12 col-md-1">
                    <label for="date_from" class="form-label">Date From</label>
                    <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}" class="form-control">
                </div>
                <div class="col-12 col-md-1">
                    <label for="date_to" class="form-label">Date To</label>
                    <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}" class="form-control">
                </div>
                <div class="col-12 col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </div>
        </div>
    </form>

    <div class="card admin-surface border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="px-4 py-3">Project</th>
                            <th class="py-3">Branch</th>
                            <th class="py-3">Scan Type</th>
                            <th class="py-3">Status</th>
                            <th class="py-3">Failure Reason</th>
                            <th class="py-3">Queued By</th>
                            <th class="py-3">Created</th>
                            <th class="py-3 text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($queues as $queue)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="fw-semibold">{{ $queue->project->project_name }}</div>
                                    <div class="small text-body-secondary">{{ $queue->project->sonar_project_key }}</div>
                                </td>
                                <td class="py-3">{{ $queue->branch_name }}</td>
                                <td class="py-3">{{ str($queue->scan_type)->title() }}</td>
                                <td class="py-3">
                                    <div class="d-flex flex-column gap-2">
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
                                </td>
                                <td class="py-3">
                                    @if ($queue->failure_category)
                                        <span class="badge rounded-pill text-bg-secondary">
                                            {{ str($queue->failure_category)->replace('_', ' ')->title() }}
                                        </span>
                                    @else
                                        <span class="text-body-secondary">N/A</span>
                                    @endif
                                </td>
                                <td class="py-3">{{ $queue->queuedBy?->name ?? 'Scheduler automation' }}</td>
                                <td class="py-3">{{ $queue->created_at?->format('d M Y H:i') }}</td>
                                <td class="py-3 text-end pe-4">
                                    <div class="d-flex justify-content-end flex-wrap gap-2">
                                        <a href="{{ route('scan-queue.show', $queue) }}" class="btn btn-sm btn-outline-primary">View</a>
                                        @can('cancel', $queue)
                                            @if ($queue->queue_status === 'pending')
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-danger"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#confirmActionModal"
                                                    data-confirm-form="cancel-queue-{{ $queue->id }}"
                                                    data-confirm-message="Cancel this pending scan?"
                                                >
                                                    Cancel
                                                </button>
                                                <form id="cancel-queue-{{ $queue->id }}" action="{{ route('scan-queue.cancel', $queue) }}" method="POST" class="d-none">
                                                    @csrf
                                                </form>
                                            @endif
                                        @endcan
                                        @can('retry', $queue)
                                            @if ($queue->queue_status === 'failed')
                                                <form action="{{ route('scan-queue.retry', $queue) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-primary">Retry</button>
                                                </form>
                                            @elseif (in_array($queue->queue_status, ['completed', 'cancelled'], true))
                                                <form action="{{ route('scan-queue.requeue', $queue) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-primary">Requeue</button>
                                                </form>
                                            @endif
                                        @endcan
                                        @can('recover', $queue)
                                            @if ($queue->isStuck($timeoutMinutes))
                                                <form action="{{ route('scan-queue.recover', $queue) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Mark Failed</button>
                                                </form>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-5 text-center text-body-secondary">No scan queue records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">
        {{ $queues->links() }}
    </div>

    @include('partials.confirm-modal')
@endsection

@push('scripts')
    @include('partials.confirm-modal-script')
@endpush
