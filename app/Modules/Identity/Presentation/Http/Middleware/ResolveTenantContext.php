<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Http\Middleware;

use App\Modules\Identity\Application\Contracts\TenantAccessResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final readonly class ResolveTenantContext
{
    public function __construct(private TenantAccessResolver $access) {}

    public function handle(Request $request, Closure $next): Response
    {
        $userId = Auth::guard('web')->id();
        abort_unless(is_string($userId), 401);

        $access = $this->access->forUser($userId);
        $routeTenant = (string) $request->route('tenant');

        if ($access === null || ! $access->tenantActive) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('tenant.login');
        }

        abort_unless(hash_equals($access->tenantId, $routeTenant), 404);

        $request->attributes->set('tenant_id', $access->tenantId);

        return $next($request);
    }
}
