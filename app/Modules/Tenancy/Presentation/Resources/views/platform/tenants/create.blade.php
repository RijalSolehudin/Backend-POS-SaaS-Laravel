<x-platform.app-layout
    title="Provision tenant"
    heading="Provision tenant"
    description="Create the tenant, initial outlet, owner identity, membership, and owner role in one atomic operation."
>
    <form method="post" action="{{ route('platform.tenants.store') }}" class="max-w-4xl space-y-6">
        @csrf
        <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', $idempotencyKey) }}">

        <section class="platform-card">
            <div class="platform-card-header">
                <h2 class="text-lg font-black tracking-tight text-slate-950">Business identity</h2>
                <p class="mt-1 text-sm text-slate-600">The tenant code is a stable, globally unique platform identifier.</p>
            </div>
            <div class="platform-card-body grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="platform-label" for="tenant_name">Tenant name</label>
                    <input class="platform-input" id="tenant_name" name="tenant_name" value="{{ old('tenant_name') }}" maxlength="160" autocomplete="organization" required autofocus>
                    @error('tenant_name') <p class="mt-2 text-sm text-rose-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="platform-label" for="tenant_code">Tenant code</label>
                    <input class="platform-input font-mono" id="tenant_code" name="tenant_code" value="{{ old('tenant_code') }}" maxlength="64" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" placeholder="kopi-nusantara" required>
                    @error('tenant_code') <p class="mt-2 text-sm text-rose-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="platform-label" for="currency">Currency</label>
                    <select class="platform-input" id="currency" name="currency" required>
                        @foreach ($currencies as $currency)
                            <option value="{{ $currency }}" @selected(old('currency', $defaults['currency'] ?? 'IDR') === $currency)>{{ $currency }}</option>
                        @endforeach
                    </select>
                    @error('currency') <p class="mt-2 text-sm text-rose-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="platform-label" for="timezone">Timezone</label>
                    <select class="platform-input" id="timezone" name="timezone" required>
                        @foreach ($timezones as $timezone)
                            <option value="{{ $timezone }}" @selected(old('timezone', $defaults['timezone'] ?? 'Asia/Jakarta') === $timezone)>{{ $timezone }}</option>
                        @endforeach
                    </select>
                    @error('timezone') <p class="mt-2 text-sm text-rose-700">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <section class="platform-card">
            <div class="platform-card-header">
                <h2 class="text-lg font-black tracking-tight text-slate-950">Initial outlet</h2>
                <p class="mt-1 text-sm text-slate-600">The first operating location created for this tenant.</p>
            </div>
            <div class="platform-card-body grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="platform-label" for="outlet_name">Outlet name</label>
                    <input class="platform-input" id="outlet_name" name="outlet_name" value="{{ old('outlet_name') }}" maxlength="120" required>
                    @error('outlet_name') <p class="mt-2 text-sm text-rose-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="platform-label" for="outlet_code">Outlet code</label>
                    <input class="platform-input font-mono uppercase" id="outlet_code" name="outlet_code" value="{{ old('outlet_code', $defaults['outlet_code'] ?? 'MAIN') }}" maxlength="32" pattern="[A-Z0-9][A-Z0-9_-]*" required>
                    @error('outlet_code') <p class="mt-2 text-sm text-rose-700">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <section class="platform-card">
            <div class="platform-card-header">
                <h2 class="text-lg font-black tracking-tight text-slate-950">Initial Tenant Owner</h2>
                <p class="mt-1 text-sm text-slate-600">The owner is active immediately and must change this password on first sign-in.</p>
            </div>
            <div class="platform-card-body grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="platform-label" for="owner_name">Owner name</label>
                    <input class="platform-input" id="owner_name" name="owner_name" value="{{ old('owner_name') }}" maxlength="120" autocomplete="name" required>
                    @error('owner_name') <p class="mt-2 text-sm text-rose-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="platform-label" for="owner_email">Owner email</label>
                    <input class="platform-input" id="owner_email" name="owner_email" type="email" value="{{ old('owner_email') }}" maxlength="254" autocomplete="email" required>
                    @error('owner_email') <p class="mt-2 text-sm text-rose-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="platform-label" for="password">Initial password</label>
                    <input class="platform-input" id="password" name="password" type="password" minlength="12" maxlength="128" autocomplete="new-password" required>
                    @error('password') <p class="mt-2 text-sm text-rose-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="platform-label" for="password_confirmation">Confirm initial password</label>
                    <input class="platform-input" id="password_confirmation" name="password_confirmation" type="password" minlength="12" maxlength="128" autocomplete="new-password" required>
                </div>
            </div>
        </section>

        <section class="platform-card">
            <div class="platform-card-header">
                <h2 class="text-lg font-black tracking-tight text-slate-950">Audit context</h2>
                <p class="mt-1 text-sm text-slate-600">Record why this tenant is being provisioned. Do not include credentials.</p>
            </div>
            <div class="platform-card-body">
                <label class="platform-label" for="reason">Reason or ticket reference</label>
                <textarea class="platform-input min-h-28 resize-y" id="reason" name="reason" maxlength="500" required>{{ old('reason') }}</textarea>
                @error('reason') <p class="mt-2 text-sm text-rose-700">{{ $message }}</p> @enderror
            </div>
        </section>

        <div class="flex flex-wrap items-center gap-3">
            <button class="platform-button platform-button-primary" type="submit">Provision atomically</button>
            <a class="platform-button platform-button-secondary" href="{{ route('platform.tenants.index') }}">Cancel</a>
            <p class="w-full text-xs text-slate-500">Submitting again with the same request key safely returns the original result.</p>
        </div>
    </form>
</x-platform.app-layout>
