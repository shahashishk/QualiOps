<x-guest-layout
    title="Sign In"
    heading="Sign in to QualiOps"
    subtitle="Access project governance, scan operations, SonarQube insights, and release controls from one secure workspace."
>
    @if (session('status'))
        <div class="alert alert-success" role="alert">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <div class="fw-semibold mb-2">We could not sign you in.</div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="auth-form">
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                class="form-control"
                required
                autofocus
                autocomplete="username"
                placeholder="name@company.com"
            >
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input
                id="password"
                type="password"
                name="password"
                class="form-control"
                required
                autocomplete="current-password"
                placeholder="Enter your password"
            >
        </div>

        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div class="form-check">
                <input id="remember_me" type="checkbox" class="form-check-input" name="remember">
                <label class="form-check-label" for="remember_me">Remember me</label>
            </div>

            @if (Route::has('password.request'))
                <a class="auth-link small fw-semibold text-decoration-none" href="{{ route('password.request') }}">
                    Forgot your password?
                </a>
            @endif
        </div>

        <div class="d-grid">
            <button type="submit" class="btn btn-primary btn-lg">Log In</button>
        </div>
    </form>
</x-guest-layout>
