@extends('layouts.admin')

@section('title', 'Users')
@section('page_title', 'Users')

@section('content')
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <form method="GET" action="{{ route('users.index') }}" class="d-flex flex-column flex-md-row align-items-md-center gap-2">
            <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search users by name or email">
            <button type="submit" class="btn btn-outline-primary">Search</button>
        </form>

        @can('create', App\Models\User::class)
            <a href="{{ route('users.create') }}" class="btn btn-primary">Create User</a>
        @endcan
    </div>

    <div class="card admin-surface border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col" class="px-4 py-3">Name</th>
                            <th scope="col" class="py-3">Email</th>
                            <th scope="col" class="py-3">Role</th>
                            <th scope="col" class="py-3">Status</th>
                            <th scope="col" class="py-3">Created</th>
                            <th scope="col" class="py-3 text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $managedUser)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="fw-semibold">{{ $managedUser->name }}</div>
                                    @if ($managedUser->is(auth()->user()))
                                        <div class="small text-body-secondary">Current account</div>
                                    @endif
                                </td>
                                <td class="py-3">{{ $managedUser->email }}</td>
                                <td class="py-3">
                                    <span class="badge rounded-pill admin-badge admin-badge-muted">
                                        {{ $managedUser->roles->pluck('name')->join(', ') ?: 'No role assigned' }}
                                    </span>
                                </td>
                                <td class="py-3">
                                    <span class="badge rounded-pill {{ $managedUser->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                        {{ $managedUser->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="py-3">{{ $managedUser->created_at?->format('d M Y') }}</td>
                                <td class="py-3 text-end pe-4">
                                    <div class="d-flex justify-content-end flex-wrap gap-2">
                                        @can('view', $managedUser)
                                            <a href="{{ route('users.show', $managedUser) }}" class="btn btn-sm btn-outline-primary">View</a>
                                        @endcan
                                        @can('update', $managedUser)
                                            <a href="{{ route('users.edit', $managedUser) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                        @endcan

                                        @can('delete', $managedUser)
                                            <button
                                                type="button"
                                                class="btn btn-sm {{ $managedUser->is_active ? 'btn-outline-primary' : 'btn-outline-success' }}"
                                                data-bs-toggle="modal"
                                                data-bs-target="#confirmActionModal"
                                                data-confirm-form="toggle-status-{{ $managedUser->id }}"
                                                data-confirm-message="{{ $managedUser->is_active ? 'Deactivate this account? The user will no longer be able to log in.' : 'Activate this account and restore login access?' }}"
                                                @disabled($managedUser->is(auth()->user()))
                                            >
                                                {{ $managedUser->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        @endcan

                                        @can('delete', $managedUser)
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#confirmActionModal"
                                                data-confirm-form="delete-user-{{ $managedUser->id }}"
                                                data-confirm-message="Delete this user permanently? This action cannot be undone."
                                                @disabled($managedUser->is(auth()->user()))
                                            >
                                                Delete
                                            </button>
                                        @endcan
                                    </div>

                                    @can('delete', $managedUser)
                                        <form id="toggle-status-{{ $managedUser->id }}" action="{{ route('users.status', $managedUser) }}" method="POST" class="d-none">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="is_active" value="{{ $managedUser->is_active ? '0' : '1' }}">
                                        </form>

                                        <form id="delete-user-{{ $managedUser->id }}" action="{{ route('users.destroy', $managedUser) }}" method="POST" class="d-none">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-5 text-center text-body-secondary">No users found for the current filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">
        {{ $users->links() }}
    </div>

    @include('partials.confirm-modal')
@endsection

@push('scripts')
    @include('partials.confirm-modal-script')
@endpush
