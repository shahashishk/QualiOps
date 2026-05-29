@php
    $currentRoute = request()->route()?->getName();
    $user = auth()->user();
    $menuItems = [
        [
            'label' => 'Dashboard',
            'route' => 'dashboard',
            'permissions' => [],
        ],
        [
            'label' => 'Users',
            'route' => 'users.index',
            'permissions' => ['users.view', 'users.create', 'users.edit', 'users.delete'],
        ],
        [
            'label' => 'Roles & Permissions',
            'route' => 'roles.index',
            'permissions' => ['roles.view', 'roles.manage'],
        ],
        [
            'label' => 'Git Connections',
            'route' => 'git-connections.index',
            'permissions' => ['git_connections.view', 'git_connections.manage'],
        ],
        [
            'label' => 'SonarQube Connections',
            'route' => 'sonar-connections.index',
            'permissions' => ['sonar_connections.view', 'sonar_connections.manage'],
        ],
        [
            'label' => 'Projects',
            'route' => 'projects.index',
            'permissions' => ['projects.view', 'projects.create', 'projects.edit', 'projects.delete'],
        ],
        [
            'label' => 'Scan Queue',
            'route' => 'scan-queue.index',
            'permissions' => ['scans.view', 'scans.queue.manage', 'scans.trigger'],
        ],
        [
            'label' => 'Scan History',
            'route' => 'scan-history.index',
            'permissions' => ['scans.view', 'scans.logs'],
        ],
        [
            'label' => 'Scheduler',
            'route' => 'scheduler.index',
            'permissions' => ['scans.schedule'],
        ],
        [
            'label' => 'Audit Logs',
            'route' => 'audit-logs.index',
            'permissions' => ['audit.view'],
        ],
        [
            'label' => 'Reports',
            'route' => 'reports.index',
            'permissions' => ['reports.view', 'reports.export'],
        ],
        [
            'label' => 'System Health',
            'route' => 'system-health.index',
            'permissions' => ['system.health.view'],
        ],
        [
            'label' => 'Notifications',
            'route' => 'notifications.history.index',
            'permissions' => ['notifications.view', 'notifications.manage'],
        ],
        [
            'label' => 'System Settings',
            'route' => 'system-maintenance.index',
            'permissions' => ['system.maintenance.manage'],
        ],
    ];
@endphp

<div class="admin-sidebar d-flex flex-column h-100 p-3 p-md-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <a href="{{ route('dashboard') }}" class="text-decoration-none">
            <span class="sidebar-brand d-block">QualiOps</span>
            <span class="sidebar-subtitle d-block">Governance Portal</span>
        </a>

        <button class="btn btn-outline-primary d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-expanded="false" aria-controls="sidebarMenu">
            Menu
        </button>
    </div>

    <div class="collapse d-md-block" id="sidebarMenu">
        <nav class="nav flex-column gap-2">
            @foreach ($menuItems as $item)
                @php
                    $canView = blank($item['permissions']) || $user?->canany($item['permissions']);
                    $isActive = $item['route'] && str_starts_with($currentRoute ?? '', explode('.', $item['route'])[0].'.');
                @endphp

                @if ($canView)
                    @if ($item['route'] && Route::has($item['route']))
                        <a href="{{ route($item['route']) }}" @class([
                            'nav-link admin-nav-link',
                            'active' => $isActive,
                        ])>
                            {{ $item['label'] }}
                        </a>
                    @else
                        <span class="nav-link admin-nav-link admin-nav-link-placeholder">
                            {{ $item['label'] }}
                        </span>
                    @endif
                @endif
            @endforeach
        </nav>
    </div>
</div>
