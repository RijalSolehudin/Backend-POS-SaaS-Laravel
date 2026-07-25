<x-platform.app-layout
    title="Confirm sensitive action"
    heading="Confirm sensitive action"
    description="Re-enter your password and second factor. Confirmation is valid for ten minutes in this session only."
>
    <section class="platform-card max-w-xl">
        <div class="platform-card-body">
            <div class="mb-6 flex gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-950">
                <svg class="mt-0.5 size-5 shrink-0 text-amber-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.887c.673 1.166-.17 2.623-1.515 2.623H3.72c-1.346 0-2.188-1.457-1.515-2.623l6.28-10.887ZM10 6a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 6Zm0 7a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/>
                </svg>
                <p>This checkpoint protects tenant provisioning, credential recovery, and other cross-tenant operations.</p>
            </div>

            <form method="post" action="{{ route('platform.confirm-sensitive.store') }}" class="space-y-5">
                @csrf

                <div>
                    <label class="platform-label" for="password">Password</label>
                    <input class="platform-input" id="password" name="password" type="password" autocomplete="current-password" maxlength="128" required>
                </div>

                <div>
                    <label class="platform-label" for="code">Authentication or recovery code</label>
                    <input class="platform-input font-mono" id="code" name="code" type="text" autocomplete="one-time-code" maxlength="32" required>
                </div>

                <button class="platform-button platform-button-primary" type="submit">Confirm identity</button>
            </form>
        </div>
    </section>
</x-platform.app-layout>
