@php
    $themes = [
        'corporate-blue' => 'Corporate Blue',
        'emerald-governance' => 'Emerald Governance',
        'slate-professional' => 'Slate Professional',
        'indigo-tech' => 'Indigo Tech',
        'neutral-minimal' => 'Neutral Minimal',
    ];
@endphp

<header class="admin-topbar border-bottom">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 px-3 px-md-4 py-3">
        <div>
            <p class="text-uppercase small fw-semibold mb-1 topbar-kicker">Internal Portal</p>
            <h1 class="h4 mb-0">@yield('page_title', 'Dashboard')</h1>
        </div>

        <div class="d-flex flex-column flex-md-row align-items-md-center gap-3">
            <form action="{{ route('theme.update') }}" method="POST" class="d-flex align-items-center gap-2">
                @csrf
                <label for="theme" class="small fw-semibold mb-0">Theme</label>
                <select id="theme" name="theme" class="form-select form-select-sm" aria-label="Select theme">
                    @foreach ($themes as $key => $label)
                        <option value="{{ $key }}" @selected(session('theme', 'corporate-blue') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary btn-sm">Apply</button>
            </form>

            <div class="dropdown">
                <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    {{ auth()->user()?->name }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <span class="dropdown-item-text small text-body-secondary">
                            {{ auth()->user()?->email }}
                        </span>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item">Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>
