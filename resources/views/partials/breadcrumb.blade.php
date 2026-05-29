@if (! empty($breadcrumbs))
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb mb-0">
            @foreach ($breadcrumbs as $breadcrumb)
                <li @class([
                    'breadcrumb-item',
                    'active' => $loop->last,
                ]) @if($loop->last) aria-current="page" @endif>
                    @if (! $loop->last && ! empty($breadcrumb['url']))
                        <a href="{{ $breadcrumb['url'] }}" class="text-decoration-none">{{ $breadcrumb['label'] }}</a>
                    @else
                        {{ $breadcrumb['label'] }}
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
