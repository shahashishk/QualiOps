@php
    $flashMessages = [
        'success' => 'success',
        'error' => 'danger',
        'warning' => 'warning',
        'status' => 'info',
    ];
@endphp

@foreach ($flashMessages as $key => $variant)
    @if (session($key))
        <div class="alert alert-{{ $variant }} alert-dismissible fade show mb-4" role="alert">
            {{ session($key) }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
@endforeach

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <div class="fw-semibold mb-2">Please review the highlighted issues.</div>
        <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
