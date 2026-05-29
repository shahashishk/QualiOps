<x-guest-layout
    title="Reset Password"
    heading="Reset your password"
    subtitle="Enter your account email and we will send a secure password reset link."
>
    @if (session('status'))
        <div class="alert alert-success" role="alert">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="auth-form">
        @csrf

        <div class="mb-4">
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

        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary btn-lg">Email Password Reset Link</button>
            <a href="{{ route('login') }}" class="btn btn-outline-primary">Back to Sign In</a>
        </div>
    </form>
</x-guest-layout>
