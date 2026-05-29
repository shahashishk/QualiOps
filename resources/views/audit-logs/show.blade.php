@extends('layouts.admin')

@section('title', 'Audit Detail')
@section('page_title', 'Audit Detail')

@section('content')
    <div class="row g-4">
        <div class="col-12 col-md-8">
            <div class="card admin-surface border-0">
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-12 col-md-6">
                            <div class="detail-label">Action</div>
                            <div class="fw-semibold">{{ $entry['action_label'] }}</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="detail-label">User</div>
                            <div class="fw-semibold">{{ $entry['user_name'] }}</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="detail-label">Entity Type</div>
                            <div class="fw-semibold">{{ $entry['entity_label'] }}</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="detail-label">Timestamp</div>
                            <div class="fw-semibold">{{ $entry['timestamp']?->format('d M Y H:i') }}</div>
                        </div>
                        <div class="col-12">
                            <div class="detail-label">Summary</div>
                            <div>{{ $entry['summary'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card admin-surface border-0 mb-4">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Old Values</h2>
                    <pre class="audit-json-block mb-0">{{ json_encode($entry['model']->old_values, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            </div>
            <div class="card admin-surface border-0">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">New Values</h2>
                    <pre class="audit-json-block mb-0">{{ json_encode($entry['model']->new_values, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            </div>
        </div>
    </div>
@endsection
