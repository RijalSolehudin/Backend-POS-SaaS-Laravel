<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Presentation\Http\Web\Controllers;

use App\Modules\Tenancy\Application\Actions\AssignUserToOutlet;
use App\Modules\Tenancy\Application\Actions\CreateOutlet;
use App\Modules\Tenancy\Application\Actions\DisableOutlet;
use App\Modules\Tenancy\Application\Actions\RemoveUserFromOutlet;
use App\Modules\Tenancy\Application\Actions\UpdateOutlet;
use App\Modules\Tenancy\Application\Contracts\TenantUserDirectory;
use App\Modules\Tenancy\Application\Exceptions\TenancyException;
use App\Modules\Tenancy\Domain\Models\Outlet;
use App\Modules\Tenancy\Domain\Models\OutletUserAssignment;
use App\Modules\Tenancy\Domain\Models\Tenant;
use App\Modules\Tenancy\Presentation\Http\Web\Support\TenantRequestSupport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class TenantOutletController extends Controller
{
    public function __construct(
        private readonly TenantRequestSupport $requestSupport,
        private readonly TenantUserDirectory $users,
    ) {}

    public function index(Request $request): View
    {
        $context = $this->requestSupport->context($request);

        return view('tenancy::tenant.outlets.index', [
            'tenant' => Tenant::query()->findOrFail($context->tenantId),
            'context' => $context,
            'outlets' => Outlet::query()->where('tenant_id', $context->tenantId)->orderBy('name')->get(),
            'preferredOutletId' => $request->session()->get('tenant.navigation.last_outlet_id'),
        ]);
    }

    public function create(Request $request): View
    {
        $context = $this->requestSupport->context($request);

        return view('tenancy::tenant.outlets.create', [
            'tenant' => Tenant::query()->findOrFail($context->tenantId),
            'context' => $context,
        ]);
    }

    public function store(Request $request, CreateOutlet $create): RedirectResponse
    {
        $input = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:32'],
        ]);
        $context = $this->requestSupport->context($request);

        try {
            $outlet = $create->handle(
                $context,
                (string) $input['name'],
                (string) $input['code'],
                $this->requestSupport->actor($context),
            );
        } catch (TenancyException $exception) {
            throw ValidationException::withMessages(['code' => [$exception->getMessage()]]);
        }

        return redirect()->route('tenant.outlets.edit', [
            'tenant' => $context->tenantId,
            'outlet' => $outlet->id,
        ])->with('status', 'Outlet created.');
    }

    public function edit(Request $request, string $tenant, string $outlet): View
    {
        $context = $this->requestSupport->context($request);
        $outletModel = $this->outlet($context->tenantId, $outlet);
        $request->session()->put('tenant.navigation.last_outlet_id', $outletModel->id);

        return view('tenancy::tenant.outlets.edit', [
            'tenant' => Tenant::query()->findOrFail($context->tenantId),
            'context' => $context,
            'outlet' => $outletModel,
            'users' => $this->users->listForTenant($context->tenantId),
            'assignedUserIds' => OutletUserAssignment::query()
                ->where('tenant_id', $context->tenantId)
                ->where('outlet_id', $outletModel->id)
                ->pluck('user_id')
                ->all(),
        ]);
    }

    public function update(
        Request $request,
        string $tenant,
        string $outlet,
        UpdateOutlet $update,
    ): RedirectResponse {
        $input = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:32'],
        ]);
        $context = $this->requestSupport->context($request);

        try {
            $update->handle(
                $context,
                $outlet,
                (string) $input['name'],
                (string) $input['code'],
                $this->requestSupport->actor($context),
            );
        } catch (TenancyException $exception) {
            if ($exception->errorCode() === 'OUTLET_NOT_FOUND') {
                abort(404);
            }

            throw ValidationException::withMessages(['code' => [$exception->getMessage()]]);
        }

        return back()->with('status', 'Outlet updated.');
    }

    public function disable(
        Request $request,
        string $tenant,
        string $outlet,
        DisableOutlet $disable,
    ): RedirectResponse {
        $input = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:500']]);
        $context = $this->requestSupport->context($request);

        try {
            $disable->handle(
                $context,
                $outlet,
                (string) $input['reason'],
                $this->requestSupport->actor($context),
            );
        } catch (TenancyException) {
            abort(404);
        }

        return back()->with('status', 'Outlet disabled.');
    }

    public function assign(
        Request $request,
        string $tenant,
        string $outlet,
        AssignUserToOutlet $assign,
    ): RedirectResponse {
        $input = $request->validate(['user_id' => ['required', 'string', 'size:26']]);
        $context = $this->requestSupport->context($request);

        try {
            $assign->handle(
                $context,
                $outlet,
                (string) $input['user_id'],
                $this->requestSupport->actor($context),
            );
        } catch (TenancyException) {
            abort(404);
        }

        return back()->with('status', 'User assigned to outlet.');
    }

    public function remove(
        Request $request,
        string $tenant,
        string $outlet,
        string $user,
        RemoveUserFromOutlet $remove,
    ): RedirectResponse {
        $context = $this->requestSupport->context($request);

        try {
            $remove->handle(
                $context,
                $outlet,
                $user,
                $this->requestSupport->actor($context),
            );
        } catch (TenancyException) {
            abort(404);
        }

        return back()->with('status', 'User removed from outlet.');
    }

    private function outlet(string $tenantId, string $outletId): Outlet
    {
        return Outlet::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($outletId)
            ->firstOrFail();
    }
}
