@php
    $project = $project ?? null;
    $selectedGitConnectionId = old('git_connection_id', $project?->git_connection_id);
    $selectedBranch = old('branch_name', $project?->branch_name);
@endphp

<div class="row g-4">
    <div class="col-12 col-md-8">
        <div class="card admin-surface border-0">
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label for="project_name" class="form-label">Project Name</label>
                        <input type="text" id="project_name" name="project_name" class="form-control" value="{{ old('project_name', $project?->project_name) }}" required>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="scan_type" class="form-label">Scan Type</label>
                        <select id="scan_type" name="scan_type" class="form-select" required>
                            <option value="">Select scan type</option>
                            @foreach ($scanTypes as $scanType)
                                <option value="{{ $scanType }}" @selected(old('scan_type', $project?->scan_type) === $scanType)>{{ str($scanType)->replace('_', ' ')->title() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12">
                        <label for="description" class="form-label">Description</label>
                        <textarea id="description" name="description" rows="4" class="form-control">{{ old('description', $project?->description) }}</textarea>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="git_connection_id" class="form-label">Git Connection</label>
                        <select id="git_connection_id" name="git_connection_id" class="form-select" required>
                            <option value="">Select Git connection</option>
                            @foreach ($gitConnections as $connection)
                                <option value="{{ $connection->id }}" data-provider-type="{{ $connection->provider_type }}" @selected((string) $selectedGitConnectionId === (string) $connection->id)>
                                    {{ $connection->connection_name }} ({{ strtoupper($connection->provider_type) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="git_url" class="form-label">Git Repository URL</label>
                        <input type="url" id="git_url" name="git_url" class="form-control" value="{{ old('git_url', $project?->git_url) }}" required>
                    </div>

                    <div class="col-12">
                        <div class="d-flex flex-column flex-md-row align-items-md-center gap-2">
                            <button type="button" class="btn btn-outline-primary" id="fetchBranchesButton">Fetch Branches</button>
                            <button type="button" class="btn btn-outline-primary" id="testGitUrlButton">Test Git URL</button>
                            <span class="small text-body-secondary" id="branchFetchMessage">Select a Git connection and repository URL to fetch branches.</span>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="branch_name_select" class="form-label">Branch Dropdown</label>
                        <select id="branch_name_select" class="form-select">
                            @if (filled($selectedBranch))
                                <option value="{{ $selectedBranch }}" selected>{{ $selectedBranch }}</option>
                            @else
                                <option value="">Fetch branches to populate this list</option>
                            @endif
                        </select>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="branch_name" class="form-label">Selected / Manual Branch</label>
                        <input type="text" id="branch_name" name="branch_name" class="form-control" value="{{ $selectedBranch }}" required>
                        <div class="form-text">For generic Git or when `uat` is unavailable, enter the branch manually.</div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="sonar_connection_id" class="form-label">SonarQube Connection</label>
                        <select id="sonar_connection_id" name="sonar_connection_id" class="form-select" required>
                            <option value="">Select SonarQube connection</option>
                            @foreach ($sonarConnections as $connection)
                                <option value="{{ $connection->id }}" @selected((string) old('sonar_connection_id', $project?->sonar_connection_id) === (string) $connection->id)>
                                    {{ $connection->connection_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="sonar_project_key" class="form-label">SonarQube Project Key</label>
                        <input type="text" id="sonar_project_key" name="sonar_project_key" class="form-control" value="{{ old('sonar_project_key', $project?->sonar_project_key) }}" required>
                    </div>

                    <div class="col-12">
                        <label for="sonar_project_name" class="form-label">SonarQube Project Name</label>
                        <input type="text" id="sonar_project_name" name="sonar_project_name" class="form-control" value="{{ old('sonar_project_name', $project?->sonar_project_name) }}" required>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label d-block">Schedule Enabled</label>
                        <div class="form-check form-switch pt-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="schedule_enabled" name="schedule_enabled" value="1" @checked(old('schedule_enabled', $project?->schedule_enabled ?? false))>
                            <label class="form-check-label" for="schedule_enabled">Enable scheduled scans</label>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="schedule_frequency" class="form-label">Schedule Frequency</label>
                        <select id="schedule_frequency" name="schedule_frequency" class="form-select" required>
                            @foreach ($scheduleFrequencies as $frequency)
                                <option value="{{ $frequency }}" @selected(old('schedule_frequency', $project?->schedule_frequency ?? 'manual') === $frequency)>{{ str($frequency)->title() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="schedule_day" class="form-label">Schedule Day</label>
                        <select id="schedule_day" name="schedule_day" class="form-select">
                            <option value="">Select day</option>
                            @foreach ($scheduleDays as $day)
                                <option value="{{ $day }}" @selected(old('schedule_day', $project?->schedule_day) === $day)>{{ str($day)->title() }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Required only when the schedule frequency is weekly.</div>
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="schedule_time" class="form-label">Schedule Time</label>
                        <input type="time" id="schedule_time" name="schedule_time" class="form-control" value="{{ old('schedule_time', filled($project?->schedule_time) ? str($project->schedule_time)->substr(0, 5) : null) }}">
                    </div>

                    <div class="col-12">
                        <label class="form-label d-block">Project Status</label>
                        <div class="form-check form-switch pt-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="active" name="active" value="1" @checked(old('active', $project?->active ?? true))>
                            <label class="form-check-label" for="active">Project is active and visible in governance workflows</label>
                        </div>
                    </div>

                    <div class="col-12">
                        <hr class="my-2">
                        <h2 class="h5 mb-3">Notification Recipients</h2>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="form-check form-switch pt-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="notify_pm" name="notify_pm" value="1" @checked(old('notify_pm', $project?->notify_pm ?? false))>
                            <label class="form-check-label" for="notify_pm">Notify PM users</label>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="form-check form-switch pt-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="notify_tl" name="notify_tl" value="1" @checked(old('notify_tl', $project?->notify_tl ?? false))>
                            <label class="form-check-label" for="notify_tl">Notify TL users</label>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="form-check form-switch pt-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="notify_qa" name="notify_qa" value="1" @checked(old('notify_qa', $project?->notify_qa ?? false))>
                            <label class="form-check-label" for="notify_qa">Notify QA users</label>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="form-check form-switch pt-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="notify_developer" name="notify_developer" value="1" @checked(old('notify_developer', $project?->notify_developer ?? false))>
                            <label class="form-check-label" for="notify_developer">Notify Developer users</label>
                        </div>
                    </div>

                    <div class="col-12">
                        <label for="notification_custom_emails" class="form-label">Custom Emails</label>
                        <textarea id="notification_custom_emails" name="notification_custom_emails" rows="4" class="form-control">{{ old('notification_custom_emails', $project?->notification_custom_emails) }}</textarea>
                        <div class="form-text">Use commas, spaces, or new lines to separate additional recipient email addresses.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="card admin-surface border-0 mb-4">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Branch Fetch Rules</h2>
                <ul class="mb-0 ps-3">
                    <li>If `uat` exists, it will be selected automatically.</li>
                    <li>If `uat` is not available, the dropdown stays available and manual branch entry is expected.</li>
                    <li>Generic Git currently supports manual branch entry only.</li>
                    <li>Weekly schedules require both a day and time.</li>
                    <li>Project-level notifications target selected roles and any custom email recipients.</li>
                </ul>
            </div>
        </div>

        <div class="card admin-surface border-0">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Execution Status</h2>
                <p class="text-body-secondary mb-0">
                    Project scheduling feeds the queue foundation. Actual scanner execution still depends on the worker runtime being available.
                </p>
            </div>
        </div>
    </div>
</div>

<div class="d-flex align-items-center gap-2 mt-4">
    <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
    <a href="{{ route('projects.index') }}" class="btn btn-outline-primary">Cancel</a>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const fetchButton = document.getElementById('fetchBranchesButton');
            const testButton = document.getElementById('testGitUrlButton');
            const gitConnection = document.getElementById('git_connection_id');
            const gitUrl = document.getElementById('git_url');
            const branchSelect = document.getElementById('branch_name_select');
            const branchInput = document.getElementById('branch_name');
            const message = document.getElementById('branchFetchMessage');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const setMessage = function (text, state = 'muted') {
                message.textContent = text;
                message.classList.remove('text-body-secondary', 'text-success', 'text-danger');
                message.classList.add(state === 'success' ? 'text-success' : state === 'error' ? 'text-danger' : 'text-body-secondary');
            };

            const selectedProvider = function () {
                return gitConnection.options[gitConnection.selectedIndex]?.dataset.providerType ?? '';
            };

            const resetDropdown = function (placeholder) {
                branchSelect.innerHTML = '';
                const option = document.createElement('option');
                option.value = '';
                option.textContent = placeholder;
                branchSelect.appendChild(option);
            };

            const postJson = async function (url) {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken ?? '',
                    },
                    body: JSON.stringify({
                        git_connection_id: gitConnection.value,
                        git_url: gitUrl.value,
                    }),
                });

                const payload = await response.json();

                if (!response.ok) {
                    throw payload;
                }

                return payload;
            };

            branchSelect.addEventListener('change', function () {
                if (branchSelect.value) {
                    branchInput.value = branchSelect.value;
                }
            });

            fetchButton?.addEventListener('click', async function () {
                if (!gitConnection.value || !gitUrl.value) {
                    setMessage('Select a Git connection and repository URL before fetching branches.', 'error');
                    return;
                }

                fetchButton.disabled = true;
                setMessage('Fetching branches...', 'muted');

                try {
                    const payload = await postJson('{{ route('projects.fetch-branches') }}');
                    resetDropdown(payload.branches.length ? 'Select branch' : 'No branches preselected');

                    payload.branches.forEach(function (branch) {
                        const option = document.createElement('option');
                        option.value = branch;
                        option.textContent = branch;

                        if (payload.default_branch === branch) {
                            option.selected = true;
                            branchInput.value = branch;
                        }

                        branchSelect.appendChild(option);
                    });

                    if (!payload.default_branch) {
                        branchInput.value = '';
                    }

                    if (selectedProvider() === 'generic') {
                        setMessage('Generic Git repositories require manual branch entry.', 'muted');
                    } else {
                        setMessage(payload.message, payload.default_branch ? 'success' : 'muted');
                    }
                } catch (payload) {
                    resetDropdown('Unable to fetch branches');
                    branchInput.value = '';
                    setMessage(payload.message ?? 'Unable to fetch branches for the selected repository.', 'error');
                } finally {
                    fetchButton.disabled = false;
                }
            });

            testButton?.addEventListener('click', async function () {
                if (!gitConnection.value || !gitUrl.value) {
                    setMessage('Select a Git connection and repository URL before testing.', 'error');
                    return;
                }

                testButton.disabled = true;
                setMessage('Testing repository URL...', 'muted');

                try {
                    const payload = await postJson('{{ route('projects.test-git-url') }}');
                    setMessage(payload.message, payload.success ? 'success' : 'error');
                } catch (payload) {
                    setMessage(payload.message ?? 'Repository URL validation failed.', 'error');
                } finally {
                    testButton.disabled = false;
                }
            });
        });
    </script>
@endpush
