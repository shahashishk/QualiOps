@extends('layouts.admin')

@section('title', 'Notification History')
@section('page_title', 'Notification History')

@section('content')
    <section class="mb-4">
        <div class="card admin-surface border-0">
            <div class="card-body p-4">
                <form method="GET" action="{{ route('notifications.history.index') }}" class="row g-3">
                    <div class="col-12 col-md-3">
                        <label for="project_id" class="form-label">Project</label>
                        <select id="project_id" name="project_id" class="form-select">
                            <option value="">All projects</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}" @selected((string) request('project_id') === (string) $project->id)>{{ $project->project_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="event_type" class="form-label">Event Type</label>
                        <select id="event_type" name="event_type" class="form-select">
                            <option value="">All events</option>
                            @foreach ($eventTypes as $eventType)
                                <option value="{{ $eventType }}" @selected(request('event_type') === $eventType)>{{ str($eventType)->replace('_', ' ')->title() }}</option>
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
                        <label for="date_from" class="form-label">From</label>
                        <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}" class="form-control">
                    </div>
                    <div class="col-12 col-md-2">
                        <label for="date_to" class="form-label">To</label>
                        <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}" class="form-control">
                    </div>
                    <div class="col-12 d-flex align-items-center gap-2">
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                        <a href="{{ route('notifications.history.index') }}" class="btn btn-outline-primary">Reset</a>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <section>
        <div class="card admin-surface border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="px-4 py-3">Created</th>
                                <th class="py-3">Project</th>
                                <th class="py-3">Event</th>
                                <th class="py-3">Recipient</th>
                                <th class="py-3">Status</th>
                                <th class="py-3">Sent At</th>
                                <th class="py-3">Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($histories as $history)
                                <tr>
                                    <td class="px-4 py-3">{{ $history->created_at?->format('d M Y H:i') }}</td>
                                    <td class="py-3">
                                        @if ($history->project)
                                            <a href="{{ route('projects.show', $history->project) }}" class="text-decoration-none fw-semibold">{{ $history->project->project_name }}</a>
                                        @else
                                            <span class="text-body-secondary">System</span>
                                        @endif
                                    </td>
                                    <td class="py-3">{{ str($history->event_type)->replace('_', ' ')->title() }}</td>
                                    <td class="py-3 text-break">{{ $history->recipient }}</td>
                                    <td class="py-3">
                                        <span class="badge rounded-pill {{ $history->status === 'sent' ? 'text-bg-success' : ($history->status === 'failed' ? 'text-bg-danger' : 'text-bg-secondary') }}">
                                            {{ str($history->status)->title() }}
                                        </span>
                                    </td>
                                    <td class="py-3">{{ $history->sent_at?->format('d M Y H:i') ?? 'Pending' }}</td>
                                    <td class="py-3">
                                        @if (filled($history->error_message))
                                            <span class="text-danger">{{ $history->error_message }}</span>
                                        @elseif ($history->scanHistory)
                                            <a href="{{ route('scan-history.show', $history->scanHistory) }}" class="text-decoration-none">Open scan</a>
                                        @else
                                            <span class="text-body-secondary">Delivered without linked scan</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-5 text-center text-body-secondary">No notification history has been recorded yet.</td>
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
    </section>
@endsection
