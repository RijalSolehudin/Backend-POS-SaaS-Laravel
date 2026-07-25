<x-platform.guest-layout
    title="Authentication code"
    heading="Verify it’s you"
    description="Enter the authenticator code for {{ $email }}, or use one unused recovery code."
>
    <form method="post" action="{{ route('platform.mfa.challenge.store') }}" class="space-y-5">
        @csrf

        <div>
            <label class="platform-label" for="code">Authentication or recovery code</label>
            <input
                class="platform-input font-mono tracking-widest"
                id="code"
                name="code"
                type="text"
                inputmode="text"
                autocomplete="one-time-code"
                maxlength="32"
                @error('code') aria-invalid="true" aria-describedby="code-error" @enderror
                required
                autofocus
            >
            @error('code')
                <p id="code-error" class="mt-2 text-sm text-rose-700">{{ $message }}</p>
            @enderror
        </div>

        <button class="platform-button platform-button-primary w-full" type="submit">Verify and sign in</button>
    </form>
</x-platform.guest-layout>
