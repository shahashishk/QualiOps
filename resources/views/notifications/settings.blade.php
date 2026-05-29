@extends('layouts.admin')

@section('title', 'Notification Settings')
@section('page_title', 'Notification Settings')

@section('content')
    <div class="row g-4">
        <div class="col-12 col-md-8">
            <div class="card admin-surface border-0">
                <div class="card-body p-4">
                    <form action="{{ route('notifications.settings.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="email_enabled" name="email_enabled" value="1" @checked($settings['email_enabled'])>
                                    <label class="form-check-label" for="email_enabled">Enable notification emails</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <hr class="my-2">
                            </div>
                            @foreach ([
                                'notify_on_scan_success' => 'Notify on scan success',
                                'notify_on_scan_failed' => 'Notify on scan failure',
                                'notify_on_quality_gate_failed' => 'Notify on quality gate failure',
                                'notify_on_scheduler_failed' => 'Notify on scheduler failure',
                                'notify_on_worker_down' => 'Notify when worker is down',
                            ] as $field => $label)
                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="{{ $field }}" name="{{ $field }}" value="1" @checked($settings[$field])>
                                        <label class="form-check-label" for="{{ $field }}">{{ $label }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="d-flex align-items-center gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">Save Settings</button>
                            <a href="{{ route('notifications.history.index') }}" class="btn btn-outline-primary">View Notification History</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card admin-surface border-0">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Delivery Notes</h2>
                    <ul class="mb-0 ps-3">
                        <li>Emails are queued through Laravel notifications.</li>
                        <li>Only summary information and portal links are included.</li>
                        <li>Sensitive logs, tokens, and secrets are never included in messages.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
