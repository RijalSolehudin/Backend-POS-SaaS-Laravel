<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Presentation\Http\Web\Controllers;

use App\Modules\Tenancy\Application\Actions\DisableTenant;
use App\Modules\Tenancy\Application\Actions\ProvisionTenant;
use App\Modules\Tenancy\Application\Data\ProvisionTenantData;
use App\Modules\Tenancy\Application\Exceptions\TenantProvisioningException;
use App\Modules\Tenancy\Domain\Models\Outlet;
use App\Modules\Tenancy\Domain\Models\Tenant;
use App\Modules\Tenancy\Presentation\Http\Web\Requests\DisableTenantRequest;
use App\Modules\Tenancy\Presentation\Http\Web\Requests\ProvisionTenantRequest;
use App\Shared\Application\Context\ActorContext;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

final readonly class PlatformTenantController
{
    public function __construct(
        private ProvisionTenant $provisionTenant,
        private DisableTenant $disableTenant,
    ) {}

    public function index(): View
    {
        return view('tenancy::platform.tenants.index', [
            'tenants' => Tenant::query()->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('tenancy::platform.tenants.create', [
            'idempotencyKey' => strtolower((string) Str::ulid()),
            'currencies' => config('tenancy.currencies', ['IDR']),
            'timezones' => config('tenancy.timezones', ['Asia/Jakarta']),
            'defaults' => config('tenancy.defaults', []),
        ]);
    }

    public function store(ProvisionTenantRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $result = $this->provisionTenant->handle(
                new ProvisionTenantData(
                    idempotencyKey: (string) $validated['idempotency_key'],
                    tenantName: (string) $validated['tenant_name'],
                    tenantCode: (string) $validated['tenant_code'],
                    outletName: (string) $validated['outlet_name'],
                    outletCode: (string) $validated['outlet_code'],
                    ownerName: (string) $validated['owner_name'],
                    ownerEmail: (string) $validated['owner_email'],
                    ownerPassword: (string) $validated['password'],
                    currency: (string) $validated['currency'],
                    timezone: (string) $validated['timezone'],
                    reason: (string) $validated['reason'],
                ),
                $this->actorContext(),
            );
        } catch (TenantProvisioningException $exception) {
            return back()
                ->withErrors(['tenant_code' => $exception->getMessage()])
                ->withInput($request->safe()->except(['password', 'password_confirmation']));
        }

        $message = $result->wasReplayed
            ? 'The original provisioning result was returned safely.'
            : "Tenant provisioned. Deliver the initial credential securely to {$result->ownerEmail}.";

        return redirect()
            ->route('platform.tenants.show', ['tenant' => $result->tenantId])
            ->with('status', $message);
    }

    public function show(string $tenant): View
    {
        $tenantModel = Tenant::query()->whereKey($tenant)->firstOrFail();

        return view('tenancy::platform.tenants.show', [
            'tenant' => $tenantModel,
            'outlets' => Outlet::query()->where('tenant_id', $tenantModel->getKey())->orderBy('name')->get(),
        ]);
    }

    public function disable(DisableTenantRequest $request, string $tenant): RedirectResponse
    {
        try {
            $result = $this->disableTenant->handle(
                tenantId: $tenant,
                reason: (string) $request->validated('reason'),
                actor: $this->actorContext(),
            );
        } catch (TenantProvisioningException $exception) {
            if ($exception->errorCode() === 'TENANT_NOT_FOUND') {
                abort(404);
            }

            return back()->withErrors(['reason' => $exception->getMessage()]);
        }

        return back()->with(
            'status',
            $result->wasChanged ? 'Tenant disabled.' : 'Tenant was already disabled.',
        );
    }

    private function actorContext(): ActorContext
    {
        $user = Auth::guard('platform')->user();

        abort_unless($user instanceof Authenticatable, 403);

        return new ActorContext(
            actorType: 'platform_user',
            actorId: (string) $user->getAuthIdentifier(),
            correlationId: strtolower((string) Str::ulid()),
        );
    }
}
