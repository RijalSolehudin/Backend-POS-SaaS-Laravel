<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Http\Middleware;

use App\Modules\Identity\Domain\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class EnforceTenantSessionPolicy
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('web')->user();

        if (! $user instanceof User || ! $user->isActive()) {
            return $this->expire($request);
        }

        $now = now()->getTimestamp();
        $authenticatedAt = (int) $request->session()->get('tenant.authenticated_at', 0);
        $lastActivityAt = (int) $request->session()->get('tenant.last_activity_at', 0);
        $idle = (int) config('identity.session.idle_minutes', 30) * 60;
        $absolute = (int) config('identity.session.absolute_minutes', 480) * 60;

        if (
            $authenticatedAt === 0
            || $lastActivityAt === 0
            || ($now - $lastActivityAt) > $idle
            || ($now - $authenticatedAt) > $absolute
        ) {
            return $this->expire($request);
        }

        if ($request->route()?->getAction('tenant_passive') !== true) {
            $request->session()->put('tenant.last_activity_at', $now);
        }

        return $next($request);
    }

    private function expire(Request $request): Response
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('tenant.login')->with('status', 'Your session expired. Please sign in again.');
    }
}
