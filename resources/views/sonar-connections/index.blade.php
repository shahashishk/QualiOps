@extends('layouts.admin')

@section('title', 'SonarQube Connections')
@section('page_title', 'SonarQube Connections')

@section('content')
    <div class="d-flex justify-content-end mb-4">
        @can('create', App\Models\SonarConnection::class)
            <a href="{{ route('sonar-connections.create') }}" class="btn btn-primary">Create Connection</a>
        @endcan
    </div>

    <div class="card admin-surface border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col" class="px-4 py-3">Connection</th>
                            <th scope="col" class="py-3">Endpoint</th>
                            <th scope="col" class="py-3">Status</th>
                            <th scope="col" class="py-3">Last Tested</th>
                            <th scope="col" class="py-3">Last Result</th>
                            <th scope="col" class="py-3 text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($connections as $connection)
                            <tr>
                                <td class="px-4 py-3 fw-semibold">{{ $connection->connection_name }}</td>
                                <td class="py-3">{{ $connection->sonar_url }}</td>
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
                                            <a href="{{ route('sonar-connections.edit', $connection) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                        @endcan

                                        @can('test', $connection)
                                            <form action="{{ route('sonar-connections.test', $connection) }}" method="POST">
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
                                                data-confirm-form="toggle-sonar-{{ $connection->id }}"
                                                data-confirm-message="{{ $connection->active ? 'Deactivate this SonarQube connection?' : 'Activate this SonarQube connection?' }}"
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
                                                data-confirm-form="delete-sonar-{{ $connection->id }}"
                                                data-confirm-message="Delete this SonarQube connection? The system will block deletion if it is assigned to a project."
                                            >
                                                Delete
                                            </button>
                                        @endcan
                                    </div>

                                    @can('update', $connection)
                                        <form id="toggle-sonar-{{ $connection->id }}" action="{{ route('sonar-connections.status', $connection) }}" method="POST" class="d-none">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="active" value="{{ $connection->active ? '0' : '1' }}">
                                        </form>
                                    @endcan

                                    @can('delete', $connection)
                                        <form id="delete-sonar-{{ $connection->id }}" action="{{ route('sonar-connections.destroy', $connection) }}" method="POST" class="d-none">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-5 text-center text-body-secondary">No SonarQube connections have been configured yet.</td>
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
