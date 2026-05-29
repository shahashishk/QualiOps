@php
    $managedUser = $managedUser ?? null;
    $selectedRole = old('role', $managedUser?->roles->first()?->name);
@endphp

<div class="row g-4">
    <div class="col-12 col-md-8">
        <div class="card admin-surface border-0">
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-12">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $managedUser?->name) }}" required>
                    </div>

                    <div class="col-12">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" value="{{ old('email', $managedUser?->email) }}" required>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="password" class="form-label">{{ $managedUser ? 'Reset Password' : 'Password' }}</label>
                        <input type="password" id="password" name="password" class="form-control" {{ $managedUser ? '' : 'required' }}>
                        <div class="form-text">
                            {{ $managedUser ? 'Leave blank to keep the current password.' : 'Password is required for new users.' }}
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="password_confirmation" class="form-label">{{ $managedUser ? 'Confirm New Password' : 'Confirm Password' }}</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" {{ $managedUser ? '' : 'required' }}>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="role" class="form-label">Assigned Role</label>
                        <select id="role" name="role" class="form-select" required>
                            <option value="">Select role</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->name }}" @selected($selectedRole === $role->name)>{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label d-block">Account Status</label>
                        <div class="form-check form-switch pt-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked(old('is_active', $managedUser?->is_active ?? true))>
                            <label class="form-check-label" for="is_active">User can sign in to the portal</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="card admin-surface border-0 mb-4">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Project Access</h2>
                <div class="alert alert-info mb-0" role="alert">
                    Project-level access assignment will be connected in a later step. This section is a placeholder only for now.
                </div>
            </div>
        </div>

        <div class="card admin-surface border-0">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Security Notes</h2>
                <ul class="mb-0 ps-3">
                    <li>Password hashes are never exposed in the interface.</li>
                    <li>Role assignment is required before saving the user.</li>
                    <li>Inactive users cannot sign in.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="d-flex align-items-center gap-2 mt-4">
    <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
    <a href="{{ route('users.index') }}" class="btn btn-outline-primary">Cancel</a>
</div>
