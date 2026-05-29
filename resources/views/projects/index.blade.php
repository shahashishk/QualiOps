@extends('layouts.admin')

@section('title', 'Projects')
@section('page_title', 'Projects')

@section('content')
    <div class="d-flex flex-column flex-md-row justify-content-md-end gap-2 mb-4">
        @can('scans.schedule')
            <a href="{{ route('scheduler.index') }}" class="btn btn-outline-primary">Preview Scheduled Scans</a>
        @endcan
        @can('create', App\Models\Project::class)
            <a href="{{ route('projects.create') }}" class="btn btn-primary">Create Project</a>
        @endcan
    </div>

    <div class="card admin-surface border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col" class="px-4 py-3">Project</th>
                            <th scope="col" class="py-3">Git Provider</th>
                            <th scope="col" class="py-3">Branch</th>
                            <th scope="col" class="py-3">Scan Type</th>
                            <th scope="col" class="py-3">Schedule</th>
                            <th scope="col" class="py-3">Status</th>
                            <th scope="col" class="py-3 text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($projects as $project)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="fw-semibold">{{ $project->project_name }}</div>
                                    <div class="small text-body-secondary">{{ $project->sonar_project_key }}</div>
                                </td>
                                <td class="py-3">{{ strtoupper($project->gitConnection->provider_type) }}</td>
                                <td class="py-3">{{ $project->branch_name }}</td>
                                <td class="py-3">{{ str($project->scan_type)->title() }}</td>
                                <td class="py-3">
                                    @if ($project->schedule_enabled)
                                        <span class="badge rounded-pill admin-badge admin-badge-muted">
                                            {{ str($project->schedule_frequency)->title() }}
                                            @if ($project->schedule_frequency === 'weekly' && filled($project->schedule_day))
                                                on {{ str($project->schedule_day)->title() }}
                                            @endif
                                            {{ $project->schedule_time ? ' @ '.str($project->schedule_time)->substr(0, 5) : '' }}
                                        </span>
                                    @else
                                        <span class="text-body-secondary">Manual</span>
                                    @endif
                                </td>
                                <td class="py-3">
                                    <span class="badge rounded-pill {{ $project->active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                        {{ $project->active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="py-3 text-end pe-4">
                                    <div class="d-flex justify-content-end flex-wrap gap-2">
                                        @can('view', $project)
                                            <a href="{{ route('projects.show', $project) }}" class="btn btn-sm btn-outline-primary">View</a>
                                        @endcan

                                        @can('update', $project)
                                            <a href="{{ route('projects.edit', $project) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                        @endcan

                                        @can('delete', $project)
                                            <button
                                                type="button"
                                                class="btn btn-sm {{ $project->active ? 'btn-outline-primary' : 'btn-outline-success' }}"
                                                data-bs-toggle="modal"
                                                data-bs-target="#confirmActionModal"
                                                data-confirm-form="toggle-project-{{ $project->id }}"
                                                data-confirm-message="{{ $project->active ? 'Deactivate this project?' : 'Activate this project?' }}"
                                            >
                                                {{ $project->active ? 'Deactivate' : 'Activate' }}
                                            </button>

                                            <form id="toggle-project-{{ $project->id }}" action="{{ route('projects.status', $project) }}" method="POST" class="d-none">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="active" value="{{ $project->active ? '0' : '1' }}">
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-5 text-center text-body-secondary">No projects have been registered yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">
        {{ $projects->links() }}
    </div>

    @include('partials.confirm-modal')
@endsection

@push('scripts')
    @include('partials.confirm-modal-script')
@endpush
