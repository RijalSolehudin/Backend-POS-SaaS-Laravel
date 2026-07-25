<x-platform.guest-layout
    title="Set up TOTP"
    heading="Protect your account"
    description="Scan the QR code with an RFC 6238 compatible authenticator app, then enter its six-digit code."
>
    <div class="mb-6 grid place-items-center rounded-2xl border border-slate-200 bg-slate-50 p-5 [&_svg]:h-auto [&_svg]:w-full [&_svg]:max-w-52" aria-label="TOTP enrollment QR code">
        {!! $qrSvg !!}
    </div>

    <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4">
        <p class="text-xs font-bold uppercase tracking-wide text-amber-800">Manual setup key for {{ $email }}</p>
        <code class="mt-2 block break-all font-mono text-sm font-bold text-amber-950">{{ $secret }}</code>
    </div>

    <form method="post" action="{{ route('platform.mfa.enroll.store') }}" class="space-y-5">
        @csrf

        <div>
            <label class="platform-label" for="code">Six-digit authentication code</label>
            <input
                class="platform-input font-mono tracking-[0.3em]"
                id="code"
                name="code"
                type="text"
                inputmode="numeric"
                pattern="[0-9]{6}"
                autocomplete="one-time-code"
                maxlength="6"
                @error('code') aria-invalid="true" aria-describedby="code-error" @enderror
                required
                autofocus
            >
            @error('code')
                <p id="code-error" class="mt-2 text-sm text-rose-700">{{ $message }}</p>
            @enderror
        </div>

        <button class="platform-button platform-button-primary w-full" type="submit">Confirm and activate</button>
    </form>
</x-platform.guest-layout>
