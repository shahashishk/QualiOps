@extends('layouts.admin')

@section('title', 'Roles & Permissions')
@section('page_title', 'Roles & Permissions')

@section('content')
    <div class="d-flex justify-content-end mb-4">
        @can('create', Spatie\Permission\Models\Role::class)
            <a href="{{ route('roles.create') }}" class="btn btn-primary">Create Role</a>
        @endcan
    </div>

    <div class="card admin-surface border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col" class="px-4 py-3">Role</th>
                            <th scope="col" class="py-3">Users</th>
                            <th scope="col" class="py-3">Permissions</th>
                            <th scope="col" class="py-3 text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($roles as $role)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="fw-semibold">{{ $role->name }}</div>
                                    @if ($role->name === 'Super Admin')
                                        <div class="small text-body-secondary">Protected enterprise role</div>
                                    @endif
                                </td>
                                <td class="py-3">{{ $role->users_count }}</td>
                                <td class="py-3">
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach ($role->permissions as $permission)
                                            <span class="badge rounded-pill admin-badge admin-badge-muted">{{ $permission->name }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="py-3 text-end pe-4">
                                    <div class="d-flex justify-content-end flex-wrap gap-2">
                                        @can('update', $role)
                                            <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                        @endcan

                                        @can('delete', $role)
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#confirmActionModal"
                                                data-confirm-form="delete-role-{{ $role->id }}"
                                                data-confirm-message="Delete this role? Assigned-user checks will be enforced before removal."
                                            >
                                                Delete
                                            </button>
                                        @endcan
                                    </div>

                                    @can('delete', $role)
                                        <form id="delete-role-{{ $role->id }}" action="{{ route('roles.destroy', $role) }}" method="POST" class="d-none">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-5 text-center text-body-secondary">No roles available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">
        {{ $roles->links() }}
    </div>

    @include('partials.confirm-modal')
@endsection

@push('scripts')
    @include('partials.confirm-modal-script')
@endpush
