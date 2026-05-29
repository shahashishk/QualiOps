@extends('layouts.admin')

@section('title', 'Logs Viewer')
@section('page_title', 'Logs Viewer')

@section('content')
    <div class="card admin-surface border-0 mb-4">
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <div class="detail-label">Project</div>
                    <div class="fw-semibold">{{ $history->project->project_name }}</div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="detail-label">Branch</div>
                    <div class="fw-semibold">{{ $history->branch_name }}</div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="detail-label">Status</div>
                    <div class="fw-semibold">{{ str($history->status)->title() }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="accordion" id="scanLogsAccordion">
        @forelse ($history->logs as $log)
            <div class="accordion-item border-0 admin-surface mb-3">
                <h2 class="accordion-header" id="log-heading-{{ $log->id }}">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#log-collapse-{{ $log->id }}" aria-expanded="false" aria-controls="log-collapse-{{ $log->id }}">
                        <span class="me-3 badge rounded-pill {{ match($log->log_level) {
                            'error' => 'text-bg-danger',
                            'warning' => 'text-bg-warning',
                            'info' => 'text-bg-info',
                            default => 'text-bg-secondary',
                        } }}">
                            {{ strtoupper($log->log_level) }}
                        </span>
                        <span>{{ \Illuminate\Support\Str::limit($log->message, 120) }}</span>
                    </button>
                </h2>
                <div id="log-collapse-{{ $log->id }}" class="accordion-collapse collapse" aria-labelledby="log-heading-{{ $log->id }}" data-bs-parent="#scanLogsAccordion">
                    <div class="accordion-body">
                        <p class="mb-3">{{ $log->message }}</p>
                        @if (! empty($log->context))
                            <pre class="log-context mb-0">{{ json_encode($log->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        @else
                            <p class="text-body-secondary mb-0">No additional context recorded.</p>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="card admin-surface border-0">
                <div class="card-body p-5 text-center text-body-secondary">
                    No logs are available for this scan history record yet.
                </div>
            </div>
        @endforelse
    </div>
@endsection
