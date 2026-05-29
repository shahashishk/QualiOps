<x-guest-layout
    title="Confirm Password"
    heading="Confirm your password"
    subtitle="This secure action requires password confirmation before you continue."
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

    <form method="POST" action="{{ route('password.confirm') }}" class="auth-form">
        @csrf

        <div class="mb-4">
            <label for="password" class="form-label">Password</label>
            <input
                id="password"
                type="password"
                name="password"
                class="form-control"
                required
                autocomplete="current-password"
                autofocus
            >
        </div>

        <div class="d-grid">
            <button type="submit" class="btn btn-primary btn-lg">Confirm</button>
        </div>
    </form>
</x-guest-layout>
