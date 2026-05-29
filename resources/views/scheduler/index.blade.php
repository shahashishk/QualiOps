@extends('layouts.admin')

@section('title', 'Scheduler')
@section('page_title', 'Scheduler')

@section('content')
    <section class="mb-4">
        <div class="card admin-surface border-0">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div>
                        <h2 class="h4 mb-2">Preview Scheduled Scans</h2>
                        <p class="text-body-secondary mb-0">
                            This preview shows which projects are eligible right now. It does not create queue records or dispatch jobs.
                        </p>
                    </div>
                    <a href="{{ route('scheduler.index') }}" class="btn btn-outline-primary">Preview Scheduled Scans</a>
                </div>
            </div>
        </div>
    </section>

    <section class="mb-4">
        <div class="row g-3">
            <div class="col-12 col-md-4">
                <div class="card admin-metric-card border-0 h-100">
                    <div class="card-body">
                        <p class="metric-label mb-2">Eligible Right Now</p>
                        <h3 class="display-6 fw-semibold mb-0">{{ number_format($preview['eligible_projects_count']) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card admin-metric-card border-0 h-100">
                    <div class="card-body">
                        <p class="metric-label mb-2">Would Queue</p>
                        <h3 class="display-6 fw-semibold mb-0">{{ number_format($preview['queued_projects_count']) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card admin-metric-card border-0 h-100">
                    <div class="card-body">
                        <p class="metric-label mb-2">Would Skip</p>
                        <h3 class="display-6 fw-semibold mb-0">{{ number_format($preview['skipped_projects_count']) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mb-4">
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <div class="card admin-surface border-0 h-100">
                    <div class="card-body p-4">
                        <h3 class="h5 mb-3">Runtime Readiness</h3>
                        <span class="badge rounded-pill {{ $preview['runtime_available'] ? 'text-bg-success' : 'text-bg-warning' }}">
                            {{ $preview['runtime_available'] ? 'Worker Runtime Available' : 'Queue Only Mode' }}
                        </span>
                        <p class="text-body-secondary mb-0 mt-3">
                            Scheduled scans can still create queue records when runtime tools are unavailable, but worker jobs are dispatched only after health checks pass.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="card admin-surface border-0 h-100">
                    <div class="card-body p-4">
                        <h3 class="h5 mb-3">Scheduler Timeline</h3>
                        <div class="small text-body-secondary mb-2">Preview generated {{ $preview['scheduled_at']->format('d M Y H:i') }}</div>
                        <div class="small text-body-secondary mb-2">
                            Last scheduler run:
                            {{ $lastRun?->scheduled_at?->format('d M Y H:i') ?? 'No scheduler runs logged yet' }}
                        </div>
                        <div class="small text-body-secondary">
                            Next scheduled scan:
                            @if ($nextRun)
                                {{ $nextRun['project']->project_name }} on {{ $nextRun['next_run_at']->format('d M Y H:i') }}
                            @else
                                No active project schedules configured
                            @endif
                        </div>
                    </div>
                </div>
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
                                <th class="px-4 py-3">Project</th>
                                <th class="py-3">Branch</th>
                                <th class="py-3">Schedule</th>
                                <th class="py-3">Status</th>
                                <th class="py-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($preview['eligible_projects'] as $item)
                                @php($project = $item['project'])
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="fw-semibold">{{ $project->project_name }}</div>
                                        <div class="small text-body-secondary">{{ $project->sonar_project_key }}</div>
                                    </td>
                                    <td class="py-3">{{ $project->branch_name }}</td>
                                    <td class="py-3">
                                        {{ str($project->schedule_frequency)->title() }}
                                        @if ($project->schedule_frequency === 'weekly' && filled($project->schedule_day))
                                            on {{ str($project->schedule_day)->title() }}
                                        @endif
                                        @if (filled($project->schedule_time))
                                            @ {{ str($project->schedule_time)->substr(0, 5) }}
                                        @endif
                                    </td>
                                    <td class="py-3">
                                        <span class="badge rounded-pill {{ $item['duplicate_pending'] ? 'text-bg-secondary' : 'text-bg-success' }}">
                                            {{ $item['duplicate_pending'] ? 'Already queued' : 'Eligible' }}
                                        </span>
                                    </td>
                                    <td class="py-3">
                                        {{ $item['will_queue'] ? 'Would queue on scheduler run' : 'Skipped due to existing pending/running queue' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-5 text-center text-body-secondary">No projects are eligible for scheduling at the current time.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection
