@extends('layouts.admin')

@section('title', 'Project Details')
@section('page_title', 'Project Details')

@section('content')
    @php
        $latestSnapshot = $project->latestSonarMetricSnapshot;
        $roleRecipients = collect([
            $project->notify_pm ? 'PM' : null,
            $project->notify_tl ? 'TL' : null,
            $project->notify_qa ? 'QA' : null,
            $project->notify_developer ? 'Developer' : null,
        ])->filter()->implode(', ');
        $customEmails = collect(preg_split('/[\s,;]+/', (string) $project->notification_custom_emails, -1, PREG_SPLIT_NO_EMPTY) ?: [])->implode(', ');
    @endphp
    <div class="row g-4">
        <div class="col-12 col-md-8">
            <div class="card admin-surface border-0">
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-12 col-md-6">
                            <div class="detail-label">Project Name</div>
                            <div class="fw-semibold">{{ $project->project_name }}</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="detail-label">Git Provider</div>
                            <div class="fw-semibold">{{ strtoupper($project->gitConnection->provider_type) }}</div>
                        </div>
                        <div class="col-12">
                            <div class="detail-label">Git URL</div>
                            <div class="fw-semibold text-break">{{ $project->git_url }}</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="detail-label">Selected Branch</div>
                            <div class="fw-semibold">{{ $project->branch_name }}</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="detail-label">Scan Type</div>
                            <div class="fw-semibold">{{ str($project->scan_type)->title() }}</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="detail-label">SonarQube Project Key</div>
                            <div class="fw-semibold">{{ $project->sonar_project_key }}</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="detail-label">SonarQube Project Name</div>
                            <div class="fw-semibold">{{ $project->sonar_project_name }}</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="detail-label">Schedule Status</div>
                            <div class="fw-semibold">
                                @if ($project->schedule_enabled)
                                    {{ str($project->schedule_frequency)->title() }}
                                    @if ($project->schedule_frequency === 'weekly' && filled($project->schedule_day))
                                        on {{ str($project->schedule_day)->title() }}
                                    @endif
                                    @if (filled($project->schedule_time))
                                        @ {{ str($project->schedule_time)->substr(0, 5) }}
                                    @endif
                                @else
                                    Manual only
                                @endif
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="detail-label">Last Scan Status</div>
                            <div class="fw-semibold text-body-secondary">Placeholder until scan history is implemented</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="detail-label">Quality Gate</div>
                            @if ($latestSnapshot)
                                <span class="badge rounded-pill {{ $latestSnapshot->quality_gate_status === 'OK' ? 'text-bg-success' : 'text-bg-danger' }}">
                                    {{ $latestSnapshot->quality_gate_status }}
                                </span>
                            @else
                                <span class="text-body-secondary">Not synced yet</span>
                            @endif
                        </div>
                        @if (filled($project->description))
                            <div class="col-12">
                                <div class="detail-label">Description</div>
                                <div>{{ $project->description }}</div>
                            </div>
                        @endif
                        <div class="col-12">
                            <div class="detail-label">Notification Recipients</div>
                            <div class="fw-semibold">
                                {{ $roleRecipients !== '' ? $roleRecipients : 'No role recipients selected' }}
                                @if ($customEmails !== '')
                                    <div class="small text-body-secondary mt-1">{{ $customEmails }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card admin-surface border-0 mb-4">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Current Status</h2>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="badge rounded-pill {{ $project->active ? 'text-bg-success' : 'text-bg-secondary' }}">
                            {{ $project->active ? 'Active' : 'Inactive' }}
                        </span>
                        <span class="badge rounded-pill admin-badge admin-badge-muted">
                            {{ $project->gitConnection->connection_name }}
                        </span>
                        @if ($latestSnapshot)
                            <span class="badge rounded-pill {{ $latestSnapshot->quality_gate_status === 'OK' ? 'text-bg-success' : 'text-bg-danger' }}">
                                Quality Gate {{ $latestSnapshot->quality_gate_status }}
                            </span>
                        @endif
                    </div>
                    <div class="d-grid gap-2">
                        @can('create', App\Models\ScanQueue::class)
                            <form action="{{ route('projects.scan-now', $project) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary w-100">Scan Now</button>
                            </form>
                        @else
                            <button type="button" class="btn btn-outline-primary w-100" disabled>Scan Now Unavailable</button>
                        @endcan

                        @can('syncSonarMetrics', $project)
                            <form action="{{ route('projects.sync-sonar-metrics', $project) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-outline-primary w-100">Sync Sonar Metrics</button>
                            </form>
                        @endcan
                    </div>
                </div>
            </div>

            <div class="card admin-surface border-0">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Actions</h2>
                    <div class="d-grid gap-2">
                        @can('update', $project)
                            <a href="{{ route('projects.edit', $project) }}" class="btn btn-primary">Edit Project</a>
                        @endcan
                        <a href="{{ route('projects.index') }}" class="btn btn-outline-primary">Back to Projects</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @can('viewSonarMetrics', $project)
        <div class="row g-4 mt-1">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card admin-surface border-0 h-100">
                    <div class="card-body p-4">
                        <div class="metric-label mb-2">Bugs</div>
                        <div class="display-6 fw-semibold mb-0">{{ number_format($latestSnapshot?->bugs ?? 0) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card admin-surface border-0 h-100">
                    <div class="card-body p-4">
                        <div class="metric-label mb-2">Vulnerabilities</div>
                        <div class="display-6 fw-semibold mb-0">{{ number_format($latestSnapshot?->vulnerabilities ?? 0) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card admin-surface border-0 h-100">
                    <div class="card-body p-4">
                        <div class="metric-label mb-2">Code Smells</div>
                        <div class="display-6 fw-semibold mb-0">{{ number_format($latestSnapshot?->code_smells ?? 0) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card admin-surface border-0 h-100">
                    <div class="card-body p-4">
                        <div class="metric-label mb-2">Coverage</div>
                        <div class="display-6 fw-semibold mb-0">{{ $latestSnapshot?->coverage !== null ? $latestSnapshot->coverage.'%' : 'N/A' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card admin-surface border-0 mt-4">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
                    <div>
                        <h2 class="h5 mb-1">Latest SonarQube Snapshot</h2>
                        <p class="text-body-secondary mb-0">
                            Most recent synchronized metrics from the configured SonarQube project.
                        </p>
                    </div>
                    <div class="small text-body-secondary">
                        {{ $latestSnapshot?->synced_at?->format('d M Y H:i') ?? 'No snapshot available' }}
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <div class="detail-label">Security Hotspots</div>
                        <div class="fw-semibold">{{ $latestSnapshot?->security_hotspots ?? 'N/A' }}</div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="detail-label">Duplicated Lines Density</div>
                        <div class="fw-semibold">{{ $latestSnapshot?->duplicated_lines_density !== null ? $latestSnapshot->duplicated_lines_density.'%' : 'N/A' }}</div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="detail-label">Technical Debt</div>
                        <div class="fw-semibold">{{ $latestSnapshot?->technical_debt !== null ? number_format($latestSnapshot->technical_debt).' min' : 'N/A' }}</div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="detail-label">Reliability Rating</div>
                        <div class="fw-semibold">{{ $latestSnapshot?->reliability_rating ?? 'N/A' }}</div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="detail-label">Security Rating</div>
                        <div class="fw-semibold">{{ $latestSnapshot?->security_rating ?? 'N/A' }}</div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="detail-label">Maintainability Rating</div>
                        <div class="fw-semibold">{{ $latestSnapshot?->maintainability_rating ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card admin-surface border-0 mt-4">
            <div class="card-body p-0">
                <div class="px-4 pt-4">
                    <h2 class="h5 mb-1">Historical Metric Snapshots</h2>
                    <p class="text-body-secondary mb-0">Stored API snapshots for this project, newest first.</p>
                </div>
                <div class="table-responsive mt-4">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="px-4 py-3">Synced At</th>
                                <th class="py-3">Quality Gate</th>
                                <th class="py-3">Bugs</th>
                                <th class="py-3">Vulnerabilities</th>
                                <th class="py-3">Code Smells</th>
                                <th class="py-3">Coverage</th>
                                <th class="py-3">Duplication</th>
                                <th class="py-3">Ratings</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($metricSnapshots as $snapshot)
                                <tr>
                                    <td class="px-4 py-3">{{ $snapshot->synced_at?->format('d M Y H:i') }}</td>
                                    <td class="py-3">
                                        <span class="badge rounded-pill {{ $snapshot->quality_gate_status === 'OK' ? 'text-bg-success' : 'text-bg-danger' }}">
                                            {{ $snapshot->quality_gate_status ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="py-3">{{ $snapshot->bugs ?? 'N/A' }}</td>
                                    <td class="py-3">{{ $snapshot->vulnerabilities ?? 'N/A' }}</td>
                                    <td class="py-3">{{ $snapshot->code_smells ?? 'N/A' }}</td>
                                    <td class="py-3">{{ $snapshot->coverage !== null ? $snapshot->coverage.'%' : 'N/A' }}</td>
                                    <td class="py-3">{{ $snapshot->duplicated_lines_density !== null ? $snapshot->duplicated_lines_density.'%' : 'N/A' }}</td>
                                    <td class="py-3">
                                        {{ $snapshot->reliability_rating ?? 'N/A' }}/{{ $snapshot->security_rating ?? 'N/A' }}/{{ $snapshot->maintainability_rating ?? 'N/A' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-5 text-center text-body-secondary">No SonarQube metric snapshots have been synchronized yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-4">
            {{ $metricSnapshots->links() }}
        </div>

    @endcan

    <div class="card admin-surface border-0 mt-4">
        <div class="card-body p-4">
            <h2 class="h5 mb-3">Activity Timeline</h2>
            <x-activity-timeline :items="$activityTimeline" empty-message="No project activity has been recorded yet." />
        </div>
    </div>
@endsection
