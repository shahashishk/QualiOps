@extends('layouts.admin')

@section('title', 'System Maintenance')
@section('page_title', 'System Maintenance')

@section('content')
    <section class="mb-4">
        <div class="card admin-surface border-0">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div>
                        <h2 class="h4 mb-2">Maintenance Readiness</h2>
                        <p class="text-body-secondary mb-0">
                            Review deployment health, preflight validation, retention cleanup preview, and operational warnings before making production changes.
                        </p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge rounded-pill {{ $preflightReport['passed'] ? 'text-bg-success' : 'text-bg-danger' }}">
                            {{ $preflightReport['passed'] ? 'Preflight Passed' : 'Preflight Needs Attention' }}
                        </span>
                        <span class="badge rounded-pill {{ count($healthReport['warnings']) === 0 ? 'text-bg-success' : 'text-bg-warning' }}">
                            {{ count($healthReport['warnings']) === 0 ? 'No Runtime Warnings' : count($healthReport['warnings']).' Runtime Warning(s)' }}
                        </span>
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
                        <p class="metric-label mb-2">Health Check Result</p>
                        <div class="fw-semibold">{{ collect($healthReport['overview'])->every(fn ($item) => $item['status']) ? 'Healthy' : 'Attention Required' }}</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card admin-surface border-0 h-100">
                    <div class="card-body p-4">
                        <p class="metric-label mb-2">Preflight Result</p>
                        <div class="fw-semibold">{{ $preflightReport['passed'] ? 'Ready' : 'Blocked' }}</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card admin-surface border-0 h-100">
                    <div class="card-body p-4">
                        <p class="metric-label mb-2">Cleanup Preview</p>
                        <div class="fw-semibold">{{ number_format($cleanupPreview['total_affected']) }} record(s) eligible</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mb-4">
        <div class="row g-3">
            <div class="col-12 col-xl-6">
                <div class="card admin-surface border-0 h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h2 class="h5 mb-0">Preflight Checks</h2>
                            <code>php artisan system:preflight</code>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="py-3">Check</th>
                                        <th class="py-3">Status</th>
                                        <th class="py-3">Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($preflightReport['checks'] as $item)
                                        <tr>
                                            <td class="py-3">
                                                <div class="fw-semibold">{{ $item['label'] }}</div>
                                                <div class="small text-body-secondary">{{ $item['details'] }}</div>
                                            </td>
                                            <td class="py-3">
                                                <span class="badge rounded-pill {{ $item['status'] ? 'text-bg-success' : 'text-bg-danger' }}">
                                                    {{ $item['status'] ? 'Pass' : 'Fail' }}
                                                </span>
                                            </td>
                                            <td class="py-3">{{ $item['value'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-6">
                <div class="card admin-surface border-0 h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h2 class="h5 mb-0">Cleanup Preview</h2>
                            <code>php artisan system:cleanup-old-data --dry-run</code>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="py-3">Target</th>
                                        <th class="py-3">Retention</th>
                                        <th class="py-3">Affected</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($cleanupPreview['targets'] as $item)
                                        <tr>
                                            <td class="py-3">
                                                <div class="fw-semibold">{{ $item['label'] }}</div>
                                                <div class="small text-body-secondary">{{ $item['details'] }}</div>
                                            </td>
                                            <td class="py-3">{{ $item['retention_days'] }} day(s)</td>
                                            <td class="py-3">{{ number_format($item['count']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mb-4">
        <div class="card admin-surface border-0">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h2 class="h5 mb-0">Runtime Warnings</h2>
                    <a href="{{ route('system-health.index') }}" class="btn btn-sm btn-outline-primary">Open Full Health Dashboard</a>
                </div>
                @if ($healthReport['warnings'] !== [] || $preflightReport['warnings'] !== [])
                    <ul class="list-group list-group-flush">
                        @foreach (array_merge($healthReport['warnings'], $preflightReport['warnings']) as $warning)
                            <li class="list-group-item px-0 text-body-secondary">{{ $warning }}</li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-body-secondary mb-0">No current runtime or preflight warnings were detected.</p>
                @endif
            </div>
        </div>
    </section>

    <section>
        <div class="card admin-surface border-0">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Operational Guidance</h2>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <tbody>
                            <tr>
                                <th class="py-3 maintenance-guidance-label">Maintenance Mode</th>
                                <td class="py-3"><code>php artisan down</code> before risky maintenance and <code>php artisan up</code> after validation.</td>
                            </tr>
                            <tr>
                                <th class="py-3 maintenance-guidance-label">Queue Restart</th>
                                <td class="py-3"><code>php artisan queue:restart</code> after deployments so workers reload updated code safely.</td>
                            </tr>
                            <tr>
                                <th class="py-3 maintenance-guidance-label">Supervisor</th>
                                <td class="py-3">Use the documentation in <code>docs/supervisor-setup.md</code> to keep queue workers running continuously.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection
