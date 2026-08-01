<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Presentation\Http\Web\Controllers;

use App\Modules\Tenancy\Application\Actions\ReassignPosDevice;
use App\Modules\Tenancy\Application\Actions\RegisterPosDevice;
use App\Modules\Tenancy\Application\Actions\RevokePosDevice;
use App\Modules\Tenancy\Application\Exceptions\TenancyException;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;
use App\Modules\Tenancy\Domain\Models\Outlet;
use App\Modules\Tenancy\Domain\Models\OutletUserAssignment;
use App\Modules\Tenancy\Domain\Models\PosDevice;
use App\Modules\Tenancy\Domain\Models\Tenant;
use App\Modules\Tenancy\Presentation\Http\Web\Support\TenantRequestSupport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class TenantPosDeviceController extends Controller
{
    public function __construct(
        private TenantRequestSupport $requestSupport,
        private TenantPermissionGuard $guard,
    ) {}

    public function index(Request $request): View
    {
        $context = $this->requestSupport->context($request);
        abort_unless($this->guard->canManageDevices($context), 403);

        return view('tenancy::tenant.devices.index', [
            'tenant' => Tenant::query()->findOrFail($context->tenantId),
            'context' => $context,
            'devices' => PosDevice::query()
                ->where('tenant_id', $context->tenantId)
                ->orderByDesc('created_at')
                ->get(),
            'outlets' => $this->outletsForContext($context->tenantId, $context->userId, $context->isOwner()),
        ]);
    }

    public function store(Request $request, RegisterPosDevice $register): RedirectResponse
    {
        $input = $request->validate([
            'outlet_id' => ['required', 'string', 'size:26'],
            'installation_id' => ['required', 'string', 'size:26'],
            'name' => ['required', 'string', 'max:120'],
            'platform' => ['required', 'string', 'max:40'],
            'app_version' => ['nullable', 'string', 'max:40'],
        ]);
        $context = $this->requestSupport->context($request);

        try {
            $register->handle(
                $context,
                (string) $input['outlet_id'],
                (string) $input['installation_id'],
                (string) $input['name'],
                (string) $input['platform'],
                $input['app_version'] ?? null,
                $this->requestSupport->actor($context),
            );
        } catch (TenancyException $exception) {
            if ($exception->errorCode() === 'TENANCY_FORBIDDEN') {
                abort(403);
            }

            throw ValidationException::withMessages(['installation_id' => [$exception->getMessage()]]);
        }

        return back()->with('status', 'POS device registered.');
    }

    public function reassign(
        Request $request,
        string $tenant,
        string $device,
        ReassignPosDevice $reassign,
    ): RedirectResponse {
        $input = $request->validate([
            'outlet_id' => ['required', 'string', 'size:26'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);
        $context = $this->requestSupport->context($request);

        try {
            $reassign->handle(
                $context,
                $device,
                (string) $input['outlet_id'],
                (string) $input['reason'],
                $this->requestSupport->actor($context),
            );
        } catch (TenancyException $exception) {
            if ($exception->errorCode() === 'TENANCY_FORBIDDEN') {
                abort(403);
            }

            throw ValidationException::withMessages(['outlet_id' => [$exception->getMessage()]]);
        }

        return back()->with('status', 'POS device reassigned.');
    }

    public function revoke(
        Request $request,
        string $tenant,
        string $device,
        RevokePosDevice $revoke,
    ): RedirectResponse {
        $input = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:500']]);
        $context = $this->requestSupport->context($request);

        try {
            $revoke->handle($context, $device, (string) $input['reason'], $this->requestSupport->actor($context));
        } catch (TenancyException $exception) {
            if ($exception->errorCode() === 'TENANCY_FORBIDDEN') {
                abort(403);
            }

            throw ValidationException::withMessages(['reason' => [$exception->getMessage()]]);
        }

        return back()->with('status', 'POS device revoked.');
    }

    /**
     * @return list<Outlet>
     */
    private function outletsForContext(string $tenantId, string $userId, bool $isOwner): array
    {
        $query = Outlet::query()->where('tenant_id', $tenantId)->orderBy('name');

        if (! $isOwner) {
            $query->whereIn(
                'id',
                OutletUserAssignment::query()
                    ->where('tenant_id', $tenantId)
                    ->where('user_id', $userId)
                    ->select('outlet_id'),
            );
        }

        return array_values($query->get()->all());
    }
}
