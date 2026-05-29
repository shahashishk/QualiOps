<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', 'System Message') | {{ config('app.name', 'QualiOps') }}</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
        <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
    </head>
    <body data-theme="{{ session('theme', 'corporate-blue') }}">
        <main class="min-vh-100 d-flex align-items-center py-5">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-12 col-md-10 col-lg-8 col-xl-6">
                        <div class="card admin-surface border-0 shadow-sm">
                            <div class="card-body p-4 p-md-5 text-center">
                                <div class="error-code mb-3">@yield('code')</div>
                                <h1 class="h3 mb-3">@yield('heading')</h1>
                                <p class="text-body-secondary mb-4">@yield('message')</p>
                                <div class="d-flex flex-column flex-md-row justify-content-center gap-2">
                                    <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="btn btn-primary">Return to Portal</a>
                                    <a href="{{ url()->previous() }}" class="btn btn-outline-primary">Go Back</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </body>
</html>
