@extends('layouts.admin')

@section('title', 'History Detail')
@section('page_title', 'History Detail')

@section('content')
    <div class="row g-4">
        <div class="col-12 col-md-8">
            <div class="card admin-surface border-0 mb-4">
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-12 col-md-6">
                            <div class="detail-label">Project</div>
                            <div class="fw-semibold">{{ $history->project->project_name }}</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="detail-label">Status</div>
                            <span class="badge rounded-pill {{ match($history->status) {
                                'pending' => 'text-bg-warning',
                                'running' => 'text-bg-info',
                                'completed' => 'text-bg-success',
                                'failed' => 'text-bg-danger',
                                default => 'text-bg-secondary',
                            } }}">
                                {{ str($history->status)->title() }}
                            </span>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="detail-label">Branch</div>
                            <div class="fw-semibold">{{ $history->branch_name }}</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="detail-label">Triggered By</div>
                            <div class="fw-semibold">{{ $history->triggeredBy?->name ?? 'Scheduler automation' }}</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="detail-label">Sonar Project Key</div>
                            <div class="fw-semibold">{{ $history->sonar_project_key }}</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="detail-label">Dashboard URL</div>
                            <div class="fw-semibold text-break">{{ $history->sonar_dashboard_url ?: 'Pending' }}</div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="detail-label">Duration</div>
                            <div class="fw-semibold">{{ $history->duration_seconds ? $history->duration_seconds.' sec' : 'Pending' }}</div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="detail-label">Coverage</div>
                            <div class="fw-semibold">{{ $history->coverage !== null ? $history->coverage.'%' : 'Pending' }}</div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="detail-label">Duplicated Lines</div>
                            <div class="fw-semibold">{{ $history->duplicated_lines !== null ? $history->duplicated_lines.'%' : 'Pending' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card admin-surface border-0">
                <div class="card-body p-4">
                    <h2 class="h5 mb-4">Timeline</h2>
                    <div class="scan-timeline">
                        <div class="scan-timeline-item is-complete">
                            <div class="scan-timeline-title">Queued</div>
                            <div class="small text-body-secondary">{{ $history->queue?->created_at?->format('d M Y H:i') ?: 'Unavailable' }}</div>
                        </div>
                        <div class="scan-timeline-item {{ $history->started_at ? 'is-complete' : '' }}">
                            <div class="scan-timeline-title">Started</div>
                            <div class="small text-body-secondary">{{ $history->started_at?->format('d M Y H:i') ?: 'Pending' }}</div>
                        </div>
                        <div class="scan-timeline-item {{ $history->completed_at ? 'is-complete' : '' }}">
                            <div class="scan-timeline-title">Completed</div>
                            <div class="small text-body-secondary">{{ $history->completed_at?->format('d M Y H:i') ?: 'Pending' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card admin-surface border-0 mb-4">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Quality Placeholders</h2>
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="detail-label">Bugs</div>
                            <div class="fw-semibold">{{ $history->total_bugs ?? 'Pending' }}</div>
                        </div>
                        <div class="col-12">
                            <div class="detail-label">Vulnerabilities</div>
                            <div class="fw-semibold">{{ $history->total_vulnerabilities ?? 'Pending' }}</div>
                        </div>
                        <div class="col-12">
                            <div class="detail-label">Code Smells</div>
                            <div class="fw-semibold">{{ $history->total_code_smells ?? 'Pending' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card admin-surface border-0">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Logs Viewer</h2>
                    @can('viewLogs', $history)
                        <a href="{{ route('scan-history.logs', $history) }}" class="btn btn-primary w-100">Open Logs</a>
                    @else
                        <p class="text-body-secondary mb-0">Log access is restricted for your role.</p>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <div class="card admin-surface border-0 mt-4">
        <div class="card-body p-4">
            <h2 class="h5 mb-3">Activity Timeline</h2>
            <x-activity-timeline :items="$activityTimeline" empty-message="No scan history activity has been recorded yet." />
        </div>
    </div>
@endsection
