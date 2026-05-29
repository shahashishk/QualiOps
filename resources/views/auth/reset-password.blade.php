<x-guest-layout
    title="Create New Password"
    heading="Choose a new password"
    subtitle="Set a strong password to regain secure access to the governance portal."
>
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.store') }}" class="auth-form">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email', $request->email) }}"
                class="form-control"
                required
                autofocus
                autocomplete="username"
            >
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">New Password</label>
            <input
                id="password"
                type="password"
                name="password"
                class="form-control"
                required
                autocomplete="new-password"
            >
        </div>

        <div class="mb-4">
            <label for="password_confirmation" class="form-label">Confirm New Password</label>
            <input
                id="password_confirmation"
                type="password"
                name="password_confirmation"
                class="form-control"
                required
                autocomplete="new-password"
            >
        </div>

        <div class="d-grid">
            <button type="submit" class="btn btn-primary btn-lg">Reset Password</button>
        </div>
    </form>
</x-guest-layout>
