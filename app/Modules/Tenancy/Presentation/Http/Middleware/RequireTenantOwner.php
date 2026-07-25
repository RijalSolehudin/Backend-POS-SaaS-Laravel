<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Presentation\Http\Middleware;

use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireTenantOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        $context = $request->attributes->get(ResolveTenantContext::ATTRIBUTE);
        abort_unless($context instanceof TenantRequestContext && $context->isOwner(), 403);

        return $next($request);
    }
}
