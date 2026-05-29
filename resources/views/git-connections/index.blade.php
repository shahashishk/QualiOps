@extends('layouts.admin')

@section('title', 'Git Connections')
@section('page_title', 'Git Connections')

@section('content')
    <div class="d-flex justify-content-end mb-4">
        @can('create', App\Models\GitConnection::class)
            <a href="{{ route('git-connections.create') }}" class="btn btn-primary">Create Connection</a>
        @endcan
    </div>

    <div class="card admin-surface border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col" class="px-4 py-3">Connection</th>
                            <th scope="col" class="py-3">Provider</th>
                            <th scope="col" class="py-3">Auth Type</th>
                            <th scope="col" class="py-3">Status</th>
                            <th scope="col" class="py-3">Last Tested</th>
                            <th scope="col" class="py-3">Last Result</th>
                            <th scope="col" class="py-3 text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($connections as $connection)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="fw-semibold">{{ $connection->connection_name }}</div>
                                    <div class="small text-body-secondary">{{ $connection->base_url }}</div>
                                </td>
                                <td class="py-3 text-capitalize">{{ $connection->provider_type }}</td>
                                <td class="py-3">{{ str($connection->auth_type)->replace('_', ' ')->title() }}</td>
                                <td class="py-3">
                                    <span class="badge rounded-pill {{ $connection->active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                        {{ $connection->active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="py-3">{{ $connection->last_tested_at?->format('d M Y H:i') ?? 'Not tested' }}</td>
                                <td class="py-3">
                                    @if ($connection->last_tested_at)
                                        <span class="badge rounded-pill {{ $connection->last_test_status ? 'text-bg-success' : 'text-bg-danger' }}">
                                            {{ $connection->last_test_result }}
                                        </span>
                                    @else
                                        <span class="text-body-secondary">Pending</span>
                                    @endif
                                </td>
                                <td class="py-3 text-end pe-4">
                                    <div class="d-flex justify-content-end flex-wrap gap-2">
                                        @can('update', $connection)
                                            <a href="{{ route('git-connections.edit', $connection) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                        @endcan

                                        @can('test', $connection)
                                            <form action="{{ route('git-connections.test', $connection) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-primary">Test Connection</button>
                                            </form>
                                        @endcan

                                        @can('update', $connection)
                                            <button
                                                type="button"
                                                class="btn btn-sm {{ $connection->active ? 'btn-outline-primary' : 'btn-outline-success' }}"
                                                data-bs-toggle="modal"
                                                data-bs-target="#confirmActionModal"
                                                data-confirm-form="toggle-git-{{ $connection->id }}"
                                                data-confirm-message="{{ $connection->active ? 'Deactivate this Git connection?' : 'Activate this Git connection?' }}"
                                            >
                                                {{ $connection->active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        @endcan

                                        @can('delete', $connection)
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#confirmActionModal"
                                                data-confirm-form="delete-git-{{ $connection->id }}"
                                                data-confirm-message="Delete this Git connection? The system will block deletion if it is assigned to a project."
                                            >
                                                Delete
                                            </button>
                                        @endcan
                                    </div>

                                    @can('update', $connection)
                                        <form id="toggle-git-{{ $connection->id }}" action="{{ route('git-connections.status', $connection) }}" method="POST" class="d-none">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="active" value="{{ $connection->active ? '0' : '1' }}">
                                        </form>
                                    @endcan

                                    @can('delete', $connection)
                                        <form id="delete-git-{{ $connection->id }}" action="{{ route('git-connections.destroy', $connection) }}" method="POST" class="d-none">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-5 text-center text-body-secondary">No Git connections have been configured yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">
        {{ $connections->links() }}
    </div>

    @include('partials.confirm-modal')
@endsection

@push('scripts')
    @include('partials.confirm-modal-script')
@endpush
