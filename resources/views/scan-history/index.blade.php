@extends('layouts.admin')

@section('title', 'Scan History')
@section('page_title', 'Scan History')

@section('content')
    <form method="GET" action="{{ route('scan-history.index') }}" class="card admin-surface border-0 mb-4">
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
                    <label for="branch" class="form-label">Branch</label>
                    <input type="text" id="branch" name="branch" value="{{ request('branch') }}" class="form-control">
                </div>
                <div class="col-12 col-md-2">
                    <label for="date_from" class="form-label">Date From</label>
                    <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}" class="form-control">
                </div>
                <div class="col-12 col-md-2">
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
                            <th class="py-3">Status</th>
                            <th class="py-3">Triggered By</th>
                            <th class="py-3">Started</th>
                            <th class="py-3">Completed</th>
                            <th class="py-3 text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($histories as $history)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="fw-semibold">{{ $history->project->project_name }}</div>
                                    <div class="small text-body-secondary">{{ $history->sonar_project_key }}</div>
                                </td>
                                <td class="py-3">{{ $history->branch_name }}</td>
                                <td class="py-3">
                                    <span class="badge rounded-pill {{ match($history->status) {
                                        'pending' => 'text-bg-warning',
                                        'running' => 'text-bg-info',
                                        'completed' => 'text-bg-success',
                                        'failed' => 'text-bg-danger',
                                        default => 'text-bg-secondary',
                                    } }}">
                                        {{ str($history->status)->title() }}
                                    </span>
                                </td>
                                <td class="py-3">{{ $history->triggeredBy->name }}</td>
                                <td class="py-3">{{ $history->started_at?->format('d M Y H:i') ?: 'Pending' }}</td>
                                <td class="py-3">{{ $history->completed_at?->format('d M Y H:i') ?: 'Pending' }}</td>
                                <td class="py-3 text-end pe-4">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="{{ route('scan-history.show', $history) }}" class="btn btn-sm btn-outline-primary">View</a>
                                        @can('viewLogs', $history)
                                            <a href="{{ route('scan-history.logs', $history) }}" class="btn btn-sm btn-outline-primary">Logs</a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-5 text-center text-body-secondary">No scan history records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">
        {{ $histories->links() }}
    </div>
@endsection
