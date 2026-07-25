<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireRecentPlatformConfirmation
{
    public function handle(Request $request, Closure $next): Response
    {
        $confirmedAt = (int) $request->session()->get('platform.sensitive_confirmed_at', 0);
        $validFor = (int) config('platform_identity.sensitive_confirmation_seconds', 600);

        if ($confirmedAt === 0 || now()->getTimestamp() - $confirmedAt > $validFor) {
            $request->session()->put('url.intended', $this->returnUrl($request));

            return new RedirectResponse(route('platform.confirm-sensitive'));
        }

        return $next($request);
    }

    private function returnUrl(Request $request): string
    {
        if ($request->isMethodSafe()) {
            return $request->fullUrl();
        }

        $referer = $request->headers->get('referer');

        if (! is_string($referer) || ! str_starts_with($referer, $request->getSchemeAndHttpHost())) {
            return route('platform.home');
        }

        $path = parse_url($referer, PHP_URL_PATH);

        if (! is_string($path) || ($path !== '/platform' && ! str_starts_with($path, '/platform/'))) {
            return route('platform.home');
        }

        return $referer;
    }
}
