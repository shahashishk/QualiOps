@extends('layouts.admin')

@section('title', 'Reports')
@section('page_title', 'Reports')

@section('content')
    <section class="mb-4">
        <div class="card admin-surface border-0">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('reports.export') }}" class="row g-3">
                    @csrf
                    <div class="col-12 col-md-4">
                        <label for="report_type" class="form-label">Report Type</label>
                        <select id="report_type" name="report_type" class="form-select" required>
                            <option value="">Select report</option>
                            @foreach ($reportTypes as $key => $label)
                                <option value="{{ $key }}" @selected(old('report_type') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <label for="format" class="form-label">Format</label>
                        <select id="format" name="format" class="form-select" required>
                            <option value="csv" @selected(old('format') === 'csv')>CSV</option>
                            <option value="xls" @selected(old('format') === 'xls')>Excel</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="project_id" class="form-label">Project</label>
                        <select id="project_id" name="project_id" class="form-select">
                            <option value="">All accessible projects</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}" @selected((string) old('project_id') === (string) $project->id)>{{ $project->project_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="branch" class="form-label">Branch</label>
                        <input type="text" id="branch" name="branch" class="form-control" value="{{ old('branch') }}">
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="scan_status" class="form-label">Scan Status</label>
                        <select id="scan_status" name="scan_status" class="form-select">
                            <option value="">All scan statuses</option>
                            @foreach ($scanStatuses as $scanStatus)
                                <option value="{{ $scanStatus }}" @selected(old('scan_status') === $scanStatus)>{{ str($scanStatus)->title() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="quality_gate_status" class="form-label">Quality Gate</label>
                        <select id="quality_gate_status" name="quality_gate_status" class="form-select">
                            <option value="">All gate statuses</option>
                            @foreach ($qualityGateStatuses as $qualityGateStatus)
                                <option value="{{ $qualityGateStatus }}" @selected(old('quality_gate_status') === $qualityGateStatus)>{{ $qualityGateStatus }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="triggered_by" class="form-label">Triggered By</label>
                        <select id="triggered_by" name="triggered_by" class="form-select">
                            <option value="">Any trigger source</option>
                            @foreach ($triggerUsers as $triggerUser)
                                <option value="{{ $triggerUser->id }}" @selected((string) old('triggered_by') === (string) $triggerUser->id)>{{ $triggerUser->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="date_from" class="form-label">From</label>
                        <input type="date" id="date_from" name="date_from" class="form-control" value="{{ old('date_from') }}">
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="date_to" class="form-label">To</label>
                        <input type="date" id="date_to" name="date_to" class="form-control" value="{{ old('date_to') }}">
                    </div>
                    <div class="col-12 d-flex align-items-center gap-2">
                        <button type="submit" class="btn btn-primary">Queue Export</button>
                        <span class="text-body-secondary small">Large exports are generated in the background and appear below when completed.</span>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <section>
        <div class="card admin-surface border-0">
            <div class="card-body p-0">
                <div class="px-4 pt-4">
                    <h2 class="h5 mb-1">Export History</h2>
                    <p class="text-body-secondary mb-0">Completed exports can be downloaded from the portal without exposing internal storage paths.</p>
                </div>
                <div class="table-responsive mt-4">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="px-4 py-3">Requested</th>
                                <th class="py-3">Report</th>
                                <th class="py-3">Format</th>
                                <th class="py-3">Requested By</th>
                                <th class="py-3">Status</th>
                                <th class="py-3">Generated At</th>
                                <th class="py-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($exports as $export)
                                <tr>
                                    <td class="px-4 py-3">{{ $export->created_at?->format('d M Y H:i') }}</td>
                                    <td class="py-3">
                                        <div class="fw-semibold">{{ $reportTypes[$export->report_type] ?? str($export->report_type)->replace('_', ' ')->title() }}</div>
                                        @if (! empty($export->filters))
                                            <div class="small text-body-secondary">Filtered export</div>
                                        @endif
                                    </td>
                                    <td class="py-3">{{ strtoupper($export->format) }}</td>
                                    <td class="py-3">{{ $export->user->name }}</td>
                                    <td class="py-3">
                                        <span class="badge rounded-pill {{ $export->status === 'completed' ? 'text-bg-success' : ($export->status === 'failed' ? 'text-bg-danger' : 'text-bg-secondary') }}">
                                            {{ str($export->status)->title() }}
                                        </span>
                                    </td>
                                    <td class="py-3">{{ $export->generated_at?->format('d M Y H:i') ?? 'Pending' }}</td>
                                    <td class="py-3">
                                        @if ($export->status === 'completed')
                                            <a href="{{ route('reports.download', $export) }}" class="btn btn-sm btn-outline-primary">Download</a>
                                        @elseif ($export->status === 'failed')
                                            <span class="text-danger small">Generation failed</span>
                                        @else
                                            <span class="text-body-secondary small">Waiting for worker</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-5 text-center text-body-secondary">No export history is available yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-4">
            {{ $exports->links() }}
        </div>
    </section>
@endsection
