<x-guest-layout
    title="Verify Email"
    heading="Verify your email address"
    subtitle="Confirm your account email before continuing into the governance workspace."
>
    @if (session('status') === 'verification-link-sent')
        <div class="alert alert-success" role="alert">
            A new verification link has been sent to the email address associated with your account.
        </div>
    @endif

    <div class="mb-4 text-body-secondary">
        Thanks for signing up. Before getting started, please verify your email address by clicking the link we emailed to you. If you did not receive the email, we can send another one.
    </div>

    <div class="d-flex flex-column flex-md-row gap-2">
        <form method="POST" action="{{ route('verification.send') }}" class="flex-fill">
            @csrf
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Resend Verification Email</button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="flex-fill">
            @csrf
            <div class="d-grid">
                <button type="submit" class="btn btn-outline-primary">Log Out</button>
            </div>
        </form>
    </div>
</x-guest-layout>
