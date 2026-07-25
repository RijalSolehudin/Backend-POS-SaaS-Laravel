<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Presentation\Http\Controllers;

use App\Modules\PlatformIdentity\Application\Contracts\PlatformSessionRepository;
use App\Modules\PlatformIdentity\Application\Contracts\SecurityAuditRecorder;
use App\Modules\PlatformIdentity\Domain\Models\PlatformUser;
use App\Modules\PlatformIdentity\Presentation\Http\Support\PlatformAuditDataFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final readonly class PlatformSecurityController
{
    public function __construct(
        private PlatformSessionRepository $sessions,
        private SecurityAuditRecorder $audit,
        private PlatformAuditDataFactory $auditData,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $user = Auth::guard('platform')->user();

        if (! $user instanceof PlatformUser) {
            return new RedirectResponse(route('platform.login'));
        }

        return view('platform-identity::security', [
            'user' => $user,
            'sessions' => $this->sessions->activeFor((string) $user->getKey()),
            'currentSessionId' => $request->session()->getId(),
        ]);
    }

    public function revoke(Request $request, string $session): RedirectResponse
    {
        $user = Auth::guard('platform')->user();

        if (! $user instanceof PlatformUser) {
            return new RedirectResponse(route('platform.login'));
        }

        $revoked = $this->sessions->revoke((string) $user->getKey(), $session);

        $this->audit->record($this->auditData->fromRequest(
            request: $request,
            eventType: 'platform_session.revoked',
            outcome: $revoked ? 'success' : 'not_found',
            subjectId: (string) $user->getKey(),
            metadata: ['revoked_session_hash' => hash('sha256', $session)],
            sendAlert: $revoked,
        ));

        if (hash_equals($request->session()->getId(), $session)) {
            Auth::guard('platform')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return new RedirectResponse(route('platform.login'));
        }

        return back()->with('status', $revoked ? 'Session revoked.' : 'Session was already unavailable.');
    }
}
