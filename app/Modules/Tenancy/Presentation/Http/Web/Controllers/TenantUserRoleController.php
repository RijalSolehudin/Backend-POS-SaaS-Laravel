<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Presentation\Http\Web\Controllers;

use App\Modules\Tenancy\Application\Actions\AssignPredefinedRole;
use App\Modules\Tenancy\Application\Actions\RemovePredefinedRole;
use App\Modules\Tenancy\Application\Contracts\TenantUserDirectory;
use App\Modules\Tenancy\Application\Exceptions\TenancyException;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;
use App\Modules\Tenancy\Application\Services\TenantRoleCatalog;
use App\Modules\Tenancy\Domain\Models\Tenant;
use App\Modules\Tenancy\Presentation\Http\Web\Support\TenantRequestSupport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class TenantUserRoleController extends Controller
{
    public function __construct(
        private readonly TenantRequestSupport $requestSupport,
        private readonly TenantUserDirectory $users,
        private readonly TenantRoleCatalog $roles,
        private readonly TenantPermissionGuard $guard,
    ) {}

    public function index(Request $request): View
    {
        $context = $this->requestSupport->context($request);
        $this->guard->authorizeManageTenantRoles($context);

        return view('tenancy::tenant.users.index', [
            'tenant' => Tenant::query()->findOrFail($context->tenantId),
            'context' => $context,
            'users' => $this->users->listForTenant($context->tenantId),
            'roles' => $this->roles->options(),
        ]);
    }

    public function store(
        Request $request,
        string $tenant,
        string $user,
        AssignPredefinedRole $assign,
    ): RedirectResponse {
        $roleValues = array_column($this->roles->options(), 'value');
        $input = $request->validate(['role' => ['required', 'string', Rule::in($roleValues)]]);
        $context = $this->requestSupport->context($request);

        try {
            $assign->handle($context, $user, (string) $input['role'], $this->requestSupport->actor($context));
        } catch (TenancyException $exception) {
            throw ValidationException::withMessages(['role' => [$exception->getMessage()]]);
        }

        return back()->with('status', 'Role assigned.');
    }

    public function destroy(
        Request $request,
        string $tenant,
        string $user,
        string $role,
        RemovePredefinedRole $remove,
    ): RedirectResponse {
        $context = $this->requestSupport->context($request);

        try {
            $remove->handle($context, $user, $role, $this->requestSupport->actor($context));
        } catch (TenancyException $exception) {
            throw ValidationException::withMessages(['role' => [$exception->getMessage()]]);
        }

        return back()->with('status', 'Role removed.');
    }
}
