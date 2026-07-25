<x-platform.guest-layout
    title="Sign in"
    heading="Sign in to Platform Admin"
    description="Use your dedicated platform account. Tenant credentials are not accepted here."
>
    <form method="post" action="{{ route('platform.login.store') }}" class="space-y-5">
        @csrf

        <div>
            <label class="platform-label" for="email">Email address</label>
            <input
                class="platform-input"
                id="email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                autocomplete="username"
                maxlength="254"
                @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                required
                autofocus
            >
            @error('email')
                <p id="email-error" class="mt-2 text-sm text-rose-700">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="platform-label" for="password">Password</label>
            <input
                class="platform-input"
                id="password"
                name="password"
                type="password"
                autocomplete="current-password"
                maxlength="128"
                @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                required
            >
            @error('password')
                <p id="password-error" class="mt-2 text-sm text-rose-700">{{ $message }}</p>
            @enderror
        </div>

        <button class="platform-button platform-button-primary w-full" type="submit">
            Continue securely
            <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M3.25 10a.75.75 0 0 1 .75-.75h10.19l-3.22-3.22a.75.75 0 0 1 1.06-1.06l4.5 4.5a.75.75 0 0 1 0 1.06l-4.5 4.5a.75.75 0 1 1-1.06-1.06l3.22-3.22H4a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd"/>
            </svg>
        </button>
    </form>
</x-platform.guest-layout>
