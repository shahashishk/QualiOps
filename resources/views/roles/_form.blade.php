@php
    $managedRole = $managedRole ?? null;
@endphp

<div class="row g-4">
    <div class="col-12 col-md-4">
        <div class="card admin-surface border-0 h-100">
            <div class="card-body p-4">
                <label for="name" class="form-label">Role Name</label>
                <input type="text" id="name" name="name" class="form-control mb-3" value="{{ old('name', $managedRole?->name) }}" required>
                <p class="text-body-secondary mb-0">
                    Assign permissions carefully. The Super Admin role is protected and can only be modified by a Super Admin.
                </p>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-8">
        <div class="card admin-surface border-0">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                    <h2 class="h5 mb-0">Permissions</h2>
                    <span class="small text-body-secondary">Select one or more permissions</span>
                </div>

                <div class="row g-3">
                    @foreach ($permissionGroups as $group => $permissions)
                        <div class="col-12 col-md-6">
                            <div class="permission-group h-100">
                                <h3 class="h6 mb-3">{{ $group }}</h3>
                                <div class="d-flex flex-column gap-2">
                                    @foreach ($permissions as $permission)
                                        <div class="form-check">
                                            <input
                                                class="form-check-input"
                                                type="checkbox"
                                                value="{{ $permission->name }}"
                                                id="permission-{{ $permission->id }}"
                                                name="permissions[]"
                                                @checked(in_array($permission->name, old('permissions', $managedRole?->permissions->pluck('name')->all() ?? []), true))
                                            >
                                            <label class="form-check-label" for="permission-{{ $permission->id }}">
                                                {{ $permission->name }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex align-items-center gap-2 mt-4">
    <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
    <a href="{{ route('roles.index') }}" class="btn btn-outline-primary">Cancel</a>
</div>
