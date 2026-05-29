@php
    $connection = $connection ?? null;
    $hasSavedToken = filled($connection?->getRawOriginal('token'));
    $hasSavedSshKey = filled($connection?->getRawOriginal('ssh_private_key'));
@endphp

<div class="row g-4">
    <div class="col-12 col-md-8">
        <div class="card admin-surface border-0">
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label for="connection_name" class="form-label">Connection Name</label>
                        <input type="text" id="connection_name" name="connection_name" class="form-control" value="{{ old('connection_name', $connection?->connection_name) }}" required>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="provider_type" class="form-label">Provider Type</label>
                        <select id="provider_type" name="provider_type" class="form-select" required>
                            <option value="">Select provider</option>
                            @foreach (['gitlab' => 'GitLab', 'github' => 'GitHub', 'bitbucket' => 'Bitbucket', 'generic' => 'Generic Git'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('provider_type', $connection?->provider_type) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12">
                        <label for="base_url" class="form-label">Base URL</label>
                        <input type="url" id="base_url" name="base_url" class="form-control" value="{{ old('base_url', $connection?->base_url) }}" required>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="auth_type" class="form-label">Authentication Type</label>
                        <select id="auth_type" name="auth_type" class="form-select" required>
                            <option value="">Select authentication type</option>
                            @foreach (['token' => 'Token', 'username_password' => 'Username / Password', 'ssh' => 'SSH'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('auth_type', $connection?->auth_type) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" id="username" name="username" class="form-control" value="{{ old('username', $connection?->username) }}">
                        <div class="form-text">Required only for username/password authentication.</div>
                    </div>

                    <div class="col-12">
                        <label for="token" class="form-label">Token / Password</label>
                        <input type="password" id="token" name="token" class="form-control" placeholder="{{ $hasSavedToken ? 'Saved secret is masked. Enter a new value only to replace it.' : '' }}">
                        <div class="form-text">
                            {{ $hasSavedToken ? 'A credential is already stored and remains unchanged unless you provide a new value.' : 'Used for token or username/password authentication.' }}
                        </div>
                    </div>

                    <div class="col-12">
                        <label for="ssh_private_key" class="form-label">SSH Private Key</label>
                        <textarea id="ssh_private_key" name="ssh_private_key" rows="6" class="form-control" placeholder="{{ $hasSavedSshKey ? 'Saved SSH key is masked. Paste a new key only to replace it.' : '' }}">{{ old('ssh_private_key') }}</textarea>
                        <div class="form-text">
                            {{ $hasSavedSshKey ? 'An SSH key is already stored and remains unchanged unless you provide a new one.' : 'Required only for SSH authentication.' }}
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label d-block">Connection Status</label>
                        <div class="form-check form-switch pt-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="active" name="active" value="1" @checked(old('active', $connection?->active ?? true))>
                            <label class="form-check-label" for="active">Connection is available for future project use</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="card admin-surface border-0 mb-4">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Security Notes</h2>
                <ul class="mb-0 ps-3">
                    <li>Stored secrets are encrypted using Laravel encryption.</li>
                    <li>Saved tokens and SSH keys are never displayed back in plain text.</li>
                    <li>Connection test errors are sanitized before they reach the UI.</li>
                </ul>
            </div>
        </div>

        <div class="card admin-surface border-0">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Provider Guidance</h2>
                <p class="text-body-secondary mb-0">
                    GitLab, GitHub, and Bitbucket tests use provider-aware API checks. Generic Git connections validate the URL only until project-specific workflows are added.
                </p>
            </div>
        </div>
    </div>
</div>

<div class="d-flex align-items-center gap-2 mt-4">
    <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
    <a href="{{ route('git-connections.index') }}" class="btn btn-outline-primary">Cancel</a>
</div>
