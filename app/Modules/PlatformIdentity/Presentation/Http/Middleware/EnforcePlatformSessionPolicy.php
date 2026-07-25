<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Presentation\Http\Middleware;

use App\Modules\PlatformIdentity\Application\Contracts\SecurityAuditRecorder;
use App\Modules\PlatformIdentity\Domain\Models\PlatformUser;
use App\Modules\PlatformIdentity\Presentation\Http\Support\PlatformAuditDataFactory;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class EnforcePlatformSessionPolicy
{
    public function __construct(
        private readonly SecurityAuditRecorder $audit,
        private readonly PlatformAuditDataFactory $auditData,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('platform');
        $user = $guard->user();

        if (! $user instanceof PlatformUser || ! $user->isActive()) {
            $guard->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return new RedirectResponse(route('platform.login'));
        }

        $now = now()->getTimestamp();
        $authenticatedAt = (int) $request->session()->get('platform.authenticated_at', 0);
        $lastActivityAt = (int) $request->session()->get('platform.last_activity_at', 0);
        $idleSeconds = (int) config('platform_identity.session.idle_minutes', 15) * 60;
        $absoluteSeconds = (int) config('platform_identity.session.absolute_minutes', 240) * 60;

        if (
            $authenticatedAt === 0
            || $lastActivityAt === 0
            || ($now - $lastActivityAt) > $idleSeconds
            || ($now - $authenticatedAt) > $absoluteSeconds
        ) {
            $this->audit->record($this->auditData->fromRequest(
                request: $request,
                eventType: 'platform_session.expired',
                outcome: 'success',
                subjectId: (string) $user->getKey(),
                metadata: [
                    'idle_expired' => ($now - $lastActivityAt) > $idleSeconds,
                    'absolute_expired' => ($now - $authenticatedAt) > $absoluteSeconds,
                ],
            ));

            $guard->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('platform.login')->withFragment('session-expired');
        }

        if ($request->route()?->getAction('platform_passive') !== true) {
            $request->session()->put('platform.last_activity_at', $now);
        }

        return $next($request);
    }
}
