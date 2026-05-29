<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', 'Dashboard') | {{ config('app.name', 'QualiOps') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
        <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
    </head>
    <body data-theme="{{ session('theme', 'corporate-blue') }}">
        <div class="admin-shell min-vh-100">
            <div class="container-fluid">
                <div class="row">
                    <aside class="col-12 col-md-3 col-xl-2 admin-sidebar-column px-0">
                        @include('partials.sidebar')
                    </aside>

                    <div class="col-12 col-md-9 col-xl-10 px-0">
                        @include('partials.topbar')

                        <main class="admin-content px-3 px-md-4 py-4">
                            @include('partials.flash')
                            @include('partials.breadcrumb', ['breadcrumbs' => $breadcrumbs ?? []])

                            @yield('content')
                        </main>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
        @stack('scripts')
    </body>
</html>
