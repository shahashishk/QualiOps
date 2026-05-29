@extends('layouts.admin')

@section('title', 'User Detail')
@section('page_title', 'User Detail')

@section('content')
    <div class="row g-4">
        <div class="col-12 col-md-8">
            <div class="card admin-surface border-0 mb-4">
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-12 col-md-6">
                            <div class="detail-label">Name</div>
                            <div class="fw-semibold">{{ $managedUser->name }}</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="detail-label">Email</div>
                            <div class="fw-semibold">{{ $managedUser->email }}</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="detail-label">Status</div>
                            <span class="badge rounded-pill {{ $managedUser->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                {{ $managedUser->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="detail-label">Roles</div>
                            <div class="fw-semibold">{{ $managedUser->roles->pluck('name')->join(', ') ?: 'No role assigned' }}</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="detail-label">Created At</div>
                            <div class="fw-semibold">{{ $managedUser->created_at?->format('d M Y H:i') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card admin-surface border-0">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Activity Timeline</h2>
                    <x-activity-timeline :items="$activityTimeline" empty-message="No user activity has been recorded yet." />
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card admin-surface border-0">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Actions</h2>
                    <div class="d-grid gap-2">
                        @can('update', $managedUser)
                            <a href="{{ route('users.edit', $managedUser) }}" class="btn btn-primary">Edit User</a>
                        @endcan
                        <a href="{{ route('users.index') }}" class="btn btn-outline-primary">Back to Users</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
