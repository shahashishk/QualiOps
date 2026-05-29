@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@section('content')
    <section class="mb-4">
        <div class="card admin-surface border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div>
                        <p class="text-uppercase small fw-semibold mb-2 section-kicker">Code Quality Governance</p>
                        <h2 class="h3 mb-2">Command center for repository quality oversight</h2>
                        <p class="text-body-secondary mb-0">
                            Monitor project coverage, review scan activity, and manage governance workflows from a single secured workspace.
                        </p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge rounded-pill admin-badge">Authenticated Access</span>
                        <span class="badge rounded-pill admin-badge admin-badge-muted">RBAC Enabled</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mb-4">
        <div class="row g-3">
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card admin-metric-card border-0 h-100">
                    <div class="card-body">
                        <p class="metric-label mb-2">Total Projects</p>
                        <h3 class="display-6 fw-semibold mb-0">{{ number_format($metrics['total_projects']) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card admin-metric-card border-0 h-100">
                    <div class="card-body">
                        <p class="metric-label mb-2">Active Projects</p>
                        <h3 class="display-6 fw-semibold mb-0">{{ number_format($metrics['active_projects']) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card admin-metric-card border-0 h-100">
                    <div class="card-body">
                        <p class="metric-label mb-2">Today's Scans</p>
                        <h3 class="display-6 fw-semibold mb-0">{{ number_format($metrics['todays_scans']) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card admin-metric-card border-0 h-100">
                    <div class="card-body">
                        <p class="metric-label mb-2">Running Scans</p>
                        <h3 class="display-6 fw-semibold mb-0">{{ number_format($metrics['running_scans']) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card admin-metric-card border-0 h-100">
                    <div class="card-body">
                        <p class="metric-label mb-2">Successful Scans</p>
                        <h3 class="display-6 fw-semibold mb-0">{{ number_format($metrics['successful_scans']) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card admin-metric-card border-0 h-100">
                    <div class="card-body">
                        <p class="metric-label mb-2">Failed Scans</p>
                        <h3 class="display-6 fw-semibold mb-0">{{ number_format($metrics['failed_scans']) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mb-4">
        <div class="row g-3">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card admin-surface border-0 h-100">
                    <div class="card-body p-4">
                        <p class="metric-label mb-2">Pending Queue</p>
                        <h3 class="display-6 fw-semibold mb-0">{{ number_format($metrics['pending_queue']) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card admin-surface border-0 h-100">
                    <div class="card-body p-4">
                        <p class="metric-label mb-2">Running Queue</p>
                        <h3 class="display-6 fw-semibold mb-0">{{ number_format($metrics['running_queue']) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card admin-surface border-0 h-100">
                    <div class="card-body p-4">
                        <p class="metric-label mb-2">Failed Today</p>
                        <h3 class="display-6 fw-semibold mb-0">{{ number_format($metrics['failed_today']) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card admin-surface border-0 h-100">
                    <div class="card-body p-4">
                        <p class="metric-label mb-2">Successful Today</p>
                        <h3 class="display-6 fw-semibold mb-0">{{ number_format($metrics['successful_today']) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mb-4">
        <div class="row g-3">
            <div class="col-12 col-md-4">
                <div class="card admin-surface border-0 h-100">
                    <div class="card-body p-4">
                        <p class="metric-label mb-2">Next Scheduled Scan</p>
                        <div class="fw-semibold">{{ $metrics['next_scheduled_scan'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card admin-surface border-0 h-100">
                    <div class="card-body p-4">
                        <p class="metric-label mb-2">Scheduled Projects</p>
                        <h3 class="display-6 fw-semibold mb-0">{{ number_format($metrics['scheduled_projects_count']) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card admin-surface border-0 h-100">
                    <div class="card-body p-4">
                        <p class="metric-label mb-2">Last Scheduler Run</p>
                        <div class="fw-semibold">{{ $metrics['last_scheduler_run'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mb-4">
        <div class="row g-3">
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card admin-surface border-0 h-100">
                    <div class="card-body p-4">
                        <p class="metric-label mb-2">Total Bugs</p>
                        <h3 class="display-6 fw-semibold mb-0">{{ number_format($metrics['total_bugs']) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card admin-surface border-0 h-100">
                    <div class="card-body p-4">
                        <p class="metric-label mb-2">Total Vulnerabilities</p>
                        <h3 class="display-6 fw-semibold mb-0">{{ number_format($metrics['total_vulnerabilities']) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card admin-surface border-0 h-100">
                    <div class="card-body p-4">
                        <p class="metric-label mb-2">Total Code Smells</p>
                        <h3 class="display-6 fw-semibold mb-0">{{ number_format($metrics['total_code_smells']) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-6">
                <div class="card admin-surface border-0 h-100">
                    <div class="card-body p-4">
                        <p class="metric-label mb-2">Quality Gates Passed</p>
                        <h3 class="display-6 fw-semibold mb-0">{{ number_format($metrics['quality_gate_passed_count']) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-6">
                <div class="card admin-surface border-0 h-100">
                    <div class="card-body p-4">
                        <p class="metric-label mb-2">Quality Gates Failed</p>
                        <h3 class="display-6 fw-semibold mb-0">{{ number_format($metrics['quality_gate_failed_count']) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section>
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <div class="card admin-surface border-0 h-100">
                    <div class="card-body p-4">
                        <h3 class="h5 mb-3">Access-aware navigation</h3>
                        <p class="text-body-secondary mb-0">
                            Sidebar items are filtered using the currently authenticated user's permissions so each role sees only the modules relevant to their governance responsibilities.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="card admin-surface border-0 h-100">
                    <div class="card-body p-4">
                        <h3 class="h5 mb-3">Theme-ready foundation</h3>
                        <p class="text-body-secondary mb-0">
                            The dashboard is powered by CSS variables, allowing all five required themes to switch instantly without changing markup or using inline styles.
                        </p>
                    </div>
                </div>
            </div>
            @can('audit.view')
                <div class="col-12">
                    <div class="card admin-surface border-0">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h3 class="h5 mb-0">Recent Activities</h3>
                                <a href="{{ route('audit-logs.index') }}" class="btn btn-sm btn-outline-primary">View Audit Logs</a>
                            </div>
                            <x-activity-timeline :items="$recentActivities" empty-message="No recent governance activity has been captured yet." />
                        </div>
                    </div>
                </div>
            @endcan
        </div>
    </section>
@endsection
