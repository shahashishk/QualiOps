@props(['items', 'emptyMessage' => 'No activities found.'])

<div class="activity-timeline d-flex flex-column gap-3">
    @forelse ($items as $item)
        <div class="card admin-surface border-0">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-3">
                    <div>
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <span class="badge rounded-pill text-bg-{{ $item['badge'] }}">{{ $item['action_label'] }}</span>
                            <span class="small text-body-secondary">{{ $item['entity_label'] }}</span>
                        </div>
                        <div class="fw-semibold mb-1">{{ $item['user_name'] }}</div>
                        <div class="text-body-secondary small">{{ $item['summary'] }}</div>
                    </div>
                    <div class="small text-body-secondary">{{ $item['timestamp']?->format('d M Y H:i') }}</div>
                </div>
            </div>
        </div>
    @empty
        <div class="card admin-surface border-0">
            <div class="card-body p-4 text-center text-body-secondary">{{ $emptyMessage }}</div>
        </div>
    @endforelse
</div>
