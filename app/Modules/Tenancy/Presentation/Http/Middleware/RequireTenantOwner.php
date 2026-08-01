<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Presentation\Http\Middleware;

use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Exceptions\TenancyException;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class RequireTenantOwner
{
    public function __construct(private TenantPermissionGuard $guard) {}

    public function handle(Request $request, Closure $next): Response
    {
        $context = $request->attributes->get(ResolveTenantContext::ATTRIBUTE);

        if (! $context instanceof TenantRequestContext) {
            abort(403);
        }

        try {
            $this->guard->authorizeManageOutlets($context);
        } catch (TenancyException) {
            abort(403);
        }

        return $next($request);
    }
}
