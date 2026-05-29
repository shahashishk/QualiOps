@props([
    'title' => config('app.name', 'QualiOps'),
    'heading' => null,
    'subtitle' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title }} | {{ config('app.name', 'QualiOps') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
        <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
    </head>
    <body data-theme="{{ session('theme', 'corporate-blue') }}">
        <main class="auth-shell min-vh-100 d-flex align-items-center py-4">
            <div class="container auth-container">
                <div class="row justify-content-center">
                    <div class="col-12 col-md-10 col-lg-8 col-xl-5">
                        <div class="card admin-surface border-0 shadow-sm auth-card overflow-hidden">
                            <div class="card-body p-4 p-md-5">
                                <div class="text-center mb-4">
                                    <a href="{{ route('login') }}" class="auth-brand text-decoration-none d-inline-flex flex-column align-items-center gap-3">
                                        <span class="auth-brand-mark d-inline-flex align-items-center justify-content-center">
                                            <x-application-logo class="auth-logo" />
                                        </span>
                                        <span class="auth-brand-copy">
                                            <span class="auth-brand-name d-block">{{ config('app.name', 'QualiOps') }}</span>
                                            <span class="auth-brand-tagline d-block">Code Quality Governance Portal</span>
                                        </span>
                                    </a>
                                </div>

                                @if (filled($heading) || filled($subtitle))
                                    <div class="text-center mb-4">
                                        @if (filled($heading))
                                            <h1 class="h3 mb-2">{{ $heading }}</h1>
                                        @endif
                                        @if (filled($subtitle))
                                            <p class="text-body-secondary mb-0">{{ $subtitle }}</p>
                                        @endif
                                    </div>
                                @endif

                                {{ $slot }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </body>
</html>
