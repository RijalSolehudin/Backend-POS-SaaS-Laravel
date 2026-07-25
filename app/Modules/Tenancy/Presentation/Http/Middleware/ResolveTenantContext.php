<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Presentation\Http\Middleware;

use App\Modules\Tenancy\Application\Actions\ResolveTenantRequestContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final readonly class ResolveTenantContext
{
    public const ATTRIBUTE = 'tenant_context';

    public function __construct(private ResolveTenantRequestContext $resolve) {}

    public function handle(Request $request, Closure $next): Response
    {
        $userId = Auth::guard('web')->id();
        abort_unless(is_string($userId), 401);

        $context = $this->resolve->handle($userId);

        if ($context === null) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('tenant.login');
        }

        abort_unless(hash_equals($context->tenantId, (string) $request->route('tenant')), 404);

        $request->attributes->set(self::ATTRIBUTE, $context);
        $request->session()->put('tenant.navigation.last_tenant_id', $context->tenantId);

        return $next($request);
    }
}
