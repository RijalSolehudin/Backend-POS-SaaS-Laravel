<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Presentation\Http\Web\Controllers;

use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;
use App\Modules\Tenancy\Domain\Models\Tenant;
use App\Modules\Tenancy\Presentation\Http\Web\Support\TenantRequestSupport;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class TenantHomeController extends Controller
{
    public function __invoke(
        Request $request,
        TenantRequestSupport $requestSupport,
        TenantPermissionGuard $guard,
    ): View {
        $context = $requestSupport->context($request);

        return view('tenancy::tenant.home', [
            'tenant' => Tenant::query()->findOrFail($context->tenantId),
            'context' => $context,
            'canManageDevices' => $guard->canManageDevices($context),
        ]);
    }
}
