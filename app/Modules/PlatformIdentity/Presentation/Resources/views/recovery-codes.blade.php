<x-platform.app-layout
    title="Recovery codes"
    heading="Save your recovery codes"
    description="Each recovery code can replace TOTP for one sign-in and is consumed immediately."
>
    <section
        class="platform-card max-w-3xl"
        x-data="{
            copied: false,
            async copyCodes() {
                await navigator.clipboard.writeText(@js(implode(PHP_EOL, $recoveryCodes)));
                this.copied = true;
                setTimeout(() => this.copied = false, 2500);
            }
        }"
    >
        <div class="platform-card-header">
            <div class="flex gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-950">
                <svg class="mt-0.5 size-5 shrink-0 text-amber-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.887c.673 1.166-.17 2.623-1.515 2.623H3.72c-1.346 0-2.188-1.457-1.515-2.623l6.28-10.887ZM10 6a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 6Zm0 7a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/>
                </svg>
                <p><strong>These codes are shown once.</strong> Store them in a password manager before continuing.</p>
            </div>
        </div>

        <div class="platform-card-body">
            <ul class="grid gap-3 sm:grid-cols-2" aria-label="Recovery codes">
                @foreach ($recoveryCodes as $recoveryCode)
                    <li><code class="block rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-center font-mono text-sm font-bold tracking-wide text-slate-900">{{ $recoveryCode }}</code></li>
                @endforeach
            </ul>

            <div class="mt-6 flex flex-wrap gap-3">
                <button class="platform-button platform-button-secondary" type="button" @click="copyCodes">
                    <span x-text="copied ? 'Copied' : 'Copy all codes'">Copy all codes</span>
                </button>
                <a class="platform-button platform-button-primary" href="{{ route('platform.security') }}">I have stored the codes</a>
            </div>
        </div>
    </section>
</x-platform.app-layout>
