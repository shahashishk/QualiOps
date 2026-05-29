@extends('layouts.admin')

@section('title', 'System Health')
@section('page_title', 'System Health')

@section('content')
    <section class="mb-4">
        <div class="card admin-surface border-0">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div>
                        <h2 class="h4 mb-2">Production Readiness Dashboard</h2>
                        <p class="text-body-secondary mb-0">
                            Deployment-oriented visibility across application runtime, queue execution, storage, scheduler readiness, and external governance integrations.
                        </p>
                    </div>
                    <span class="small text-body-secondary">Generated at {{ $report['generated_at']->format('d M Y H:i:s') }}</span>
                </div>
            </div>
        </div>
    </section>

    <section class="mb-4">
        <div class="row g-3">
            @foreach ($report['overview'] as $item)
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card admin-surface border-0 h-100">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                <h3 class="h6 mb-0">{{ $item['label'] }}</h3>
                                <span class="badge rounded-pill {{ $item['status'] ? 'text-bg-success' : 'text-bg-danger' }}">
                                    {{ $item['status'] ? 'Healthy' : 'Attention' }}
                                </span>
                            </div>
                            <div class="health-value mb-2">{{ $item['value'] }}</div>
                            <p class="text-body-secondary mb-0">{{ $item['details'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="mb-4">
        <div class="card admin-surface border-0">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h2 class="h5 mb-1">Final Verification</h2>
                        <p class="text-body-secondary mb-0">Release-focused validation for the key MVP runtime dependencies and operations signals.</p>
                    </div>
                </div>
                <div class="row g-3">
                    @foreach ($report['final_verification'] as $item)
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="card admin-surface border h-100">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                        <h3 class="h6 mb-0">{{ $item['label'] }}</h3>
                                        <span class="badge rounded-pill {{ $item['status'] ? 'text-bg-success' : 'text-bg-danger' }}">
                                            {{ $item['status'] ? 'Verified' : 'Blocked' }}
                                        </span>
                                    </div>
                                    <div class="health-value mb-2">{{ $item['value'] }}</div>
                                    <p class="text-body-secondary mb-0">{{ $item['details'] }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    @if ($report['warnings'] !== [])
        <section class="mb-4">
            <div class="card admin-surface border-0">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Runtime Warnings</h2>
                    <ul class="list-group list-group-flush">
                        @foreach ($report['warnings'] as $warning)
                            <li class="list-group-item px-0 text-body-secondary">{{ $warning }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </section>
    @endif

    <section class="mb-4">
        <div class="row g-3">
            @foreach ([
                'application' => 'Application & Toolchain',
                'database' => 'Database',
                'queue' => 'Queue',
                'scheduler' => 'Scheduler',
                'storage' => 'Storage & Workspace',
            ] as $sectionKey => $sectionTitle)
                <div class="col-12 col-xl-6">
                    <div class="card admin-surface border-0 h-100">
                        <div class="card-body p-4">
                            <h2 class="h5 mb-4">{{ $sectionTitle }}</h2>
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
                                        @foreach ($report[$sectionKey] as $item)
                                            <tr>
                                                <td class="py-3">
                                                    <div class="fw-semibold">{{ $item['label'] }}</div>
                                                    <div class="small text-body-secondary">{{ $item['details'] }}</div>
                                                </td>
                                                <td class="py-3">
                                                    <span class="badge rounded-pill {{ $item['status'] ? 'text-bg-success' : 'text-bg-danger' }}">
                                                        {{ $item['status'] ? 'OK' : 'Fail' }}
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
            @endforeach
        </div>
    </section>

    <section class="mb-4">
        <div class="card admin-surface border-0">
            <div class="card-body p-4">
                <h2 class="h5 mb-4">SonarQube Connection Reachability</h2>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="py-3">Connection</th>
                                <th class="py-3">Status</th>
                                <th class="py-3">Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($report['connections']['sonar'] as $item)
                                <tr>
                                    <td class="py-3 fw-semibold">{{ $item['name'] }}</td>
                                    <td class="py-3">
                                        <span class="badge rounded-pill {{ $item['status'] ? 'text-bg-success' : 'text-bg-danger' }}">
                                            {{ $item['status'] ? 'Reachable' : 'Unreachable' }}
                                        </span>
                                    </td>
                                    <td class="py-3">{{ $item['details'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-4 text-center text-body-secondary">No active SonarQube connections configured.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <section>
        <div class="card admin-surface border-0">
            <div class="card-body p-4">
                <h2 class="h5 mb-4">Git Connection Reachability</h2>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="py-3">Connection</th>
                                <th class="py-3">Status</th>
                                <th class="py-3">Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($report['connections']['git'] as $item)
                                <tr>
                                    <td class="py-3 fw-semibold">{{ $item['name'] }}</td>
                                    <td class="py-3">
                                        <span class="badge rounded-pill {{ $item['status'] ? 'text-bg-success' : 'text-bg-danger' }}">
                                            {{ $item['status'] ? 'Reachable' : 'Unreachable' }}
                                        </span>
                                    </td>
                                    <td class="py-3">{{ $item['details'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-4 text-center text-body-secondary">No active Git connections configured.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection
