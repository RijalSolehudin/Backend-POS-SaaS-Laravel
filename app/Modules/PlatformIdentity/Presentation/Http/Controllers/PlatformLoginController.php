<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Presentation\Http\Controllers;

use App\Modules\PlatformIdentity\Application\Actions\AuthenticatePlatformPassword;
use App\Modules\PlatformIdentity\Application\Contracts\SecurityAuditRecorder;
use App\Modules\PlatformIdentity\Application\Services\PlatformLoginThrottle;
use App\Modules\PlatformIdentity\Presentation\Http\Support\PlatformAuditDataFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final readonly class PlatformLoginController
{
    public function __construct(
        private AuthenticatePlatformPassword $authenticate,
        private PlatformLoginThrottle $throttle,
        private SecurityAuditRecorder $audit,
        private PlatformAuditDataFactory $auditData,
    ) {}

    public function create(): View|RedirectResponse
    {
        if (Auth::guard('platform')->check()) {
            return new RedirectResponse(route('platform.security'));
        }

        return view('platform-identity::login');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:254'],
            'password' => ['required', 'string', 'max:128'],
        ]);

        $email = mb_strtolower(trim($validated['email']));
        $ipAddress = (string) $request->ip();
        $retryAfter = $this->throttle->retryAfter($email, $ipAddress);

        if ($retryAfter > 0) {
            return back()
                ->withErrors(['email' => 'Unable to sign in. Try again later.'])
                ->with('retry_after', $retryAfter);
        }

        $user = $this->authenticate->handle($email, $validated['password']);

        if ($user === null) {
            $cooldown = $this->throttle->recordFailure($email, $ipAddress);
            $this->audit->record($this->auditData->fromRequest(
                request: $request,
                eventType: 'platform_login.password_failed',
                outcome: 'failure',
                metadata: ['cooldown_seconds' => $cooldown],
                sendAlert: $cooldown > 0,
            ));

            return back()
                ->withErrors(['email' => 'The supplied credentials are invalid.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->session()->put('platform.login_challenge', [
            'platform_user_id' => (string) $user->getKey(),
            'issued_at' => now()->getTimestamp(),
            'email' => $email,
            'mfa_failures' => 0,
        ]);

        return new RedirectResponse(
            $user->requiresMfaSetup()
                ? route('platform.mfa.enroll')
                : route('platform.mfa.challenge'),
        );
    }
}
