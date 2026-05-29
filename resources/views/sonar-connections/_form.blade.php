@php
    $connection = $connection ?? null;
    $hasSavedToken = filled($connection?->getRawOriginal('token'));
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
                        <label class="form-label d-block">Connection Status</label>
                        <div class="form-check form-switch pt-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="active" name="active" value="1" @checked(old('active', $connection?->active ?? true))>
                            <label class="form-check-label" for="active">Connection is available for future project use</label>
                        </div>
                    </div>

                    <div class="col-12">
                        <label for="sonar_url" class="form-label">SonarQube URL</label>
                        <input type="url" id="sonar_url" name="sonar_url" class="form-control" value="{{ old('sonar_url', $connection?->sonar_url) }}" required>
                    </div>

                    <div class="col-12">
                        <label for="token" class="form-label">Token</label>
                        <input type="password" id="token" name="token" class="form-control" placeholder="{{ $hasSavedToken ? 'Saved token is masked. Enter a new value only to replace it.' : '' }}">
                        <div class="form-text">
                            {{ $hasSavedToken ? 'A token is already stored and remains unchanged unless you provide a new one.' : 'A token is required to validate the SonarQube API.' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="card admin-surface border-0">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Security Notes</h2>
                <ul class="mb-0 ps-3">
                    <li>Stored tokens are encrypted using Laravel encryption.</li>
                    <li>Saved tokens are never displayed back in plain text.</li>
                    <li>Connection test failures are shown without exposing secrets.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="d-flex align-items-center gap-2 mt-4">
    <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
    <a href="{{ route('sonar-connections.index') }}" class="btn btn-outline-primary">Cancel</a>
</div>
