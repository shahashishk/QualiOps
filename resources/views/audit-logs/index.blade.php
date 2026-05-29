@extends('layouts.admin')

@section('title', 'Audit Logs')
@section('page_title', 'Audit Logs')

@section('content')
    <form method="GET" action="{{ route('audit-logs.index') }}" class="card admin-surface border-0 mb-4">
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-12 col-md-2">
                    <label for="user_id" class="form-label">User</label>
                    <select id="user_id" name="user_id" class="form-select">
                        <option value="">All users</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label for="entity_type" class="form-label">Entity Type</label>
                    <select id="entity_type" name="entity_type" class="form-select">
                        <option value="">All entities</option>
                        @foreach ($entityTypes as $entityType)
                            <option value="{{ $entityType }}" @selected(request('entity_type') === $entityType)>{{ class_basename($entityType) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label for="action" class="form-label">Action</label>
                    <select id="action" name="action" class="form-select">
                        <option value="">All actions</option>
                        @foreach ($actions as $action)
                            <option value="{{ $action }}" @selected(request('action') === $action)>{{ str($action)->replace(['.', '_'], ' ')->title() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label for="date_from" class="form-label">Date From</label>
                    <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}" class="form-control">
                </div>
                <div class="col-12 col-md-2">
                    <label for="date_to" class="form-label">Date To</label>
                    <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}" class="form-control">
                </div>
            </div>
            <div class="d-flex justify-content-end mt-3">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </div>
    </form>

    <div class="card admin-surface border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="px-4 py-3">Action</th>
                            <th class="py-3">Entity</th>
                            <th class="py-3">User</th>
                            <th class="py-3">Summary</th>
                            <th class="py-3">Created</th>
                            <th class="py-3 text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $entry)
                            <tr>
                                <td class="px-4 py-3">
                                    <span class="badge rounded-pill text-bg-{{ $entry['badge'] }}">{{ $entry['action_label'] }}</span>
                                </td>
                                <td class="py-3">{{ $entry['entity_label'] }}</td>
                                <td class="py-3">{{ $entry['user_name'] }}</td>
                                <td class="py-3">{{ $entry['summary'] }}</td>
                                <td class="py-3">{{ $entry['timestamp']?->format('d M Y H:i') }}</td>
                                <td class="py-3 text-end pe-4">
                                    <a href="{{ route('audit-logs.show', $entry['model']) }}" class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-5 text-center text-body-secondary">No audit logs found for the current filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">
        {{ $logs->links() }}
    </div>
@endsection
