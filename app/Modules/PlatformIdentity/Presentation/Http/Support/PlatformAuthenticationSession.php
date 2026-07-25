<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Presentation\Http\Support;

use App\Modules\PlatformIdentity\Application\Contracts\PlatformSessionRepository;
use App\Modules\PlatformIdentity\Application\Contracts\SecurityAuditRecorder;
use App\Modules\PlatformIdentity\Application\Services\PlatformLoginThrottle;
use App\Modules\PlatformIdentity\Domain\Enums\SecondFactorMethod;
use App\Modules\PlatformIdentity\Domain\Models\PlatformUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

final readonly class PlatformAuthenticationSession
{
    public function __construct(
        private SecurityAuditRecorder $audit,
        private PlatformAuditDataFactory $auditData,
        private PlatformLoginThrottle $throttle,
        private PlatformSessionRepository $sessions,
    ) {}

    public function establish(
        Request $request,
        PlatformUser $user,
        SecondFactorMethod $method,
    ): bool {
        Auth::guard('platform')->login($user);
        $request->session()->regenerate(true);

        if (! $this->sessions->reserve(
            platformUserId: (string) $user->getKey(),
            sessionId: $request->session()->getId(),
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        )) {
            Auth::guard('platform')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return false;
        }

        try {
            $now = now()->getTimestamp();
            $request->session()->forget([
                'platform.login_challenge',
                'platform.session_replacement',
                'platform.totp_enrollment_secret',
            ]);
            $request->session()->put([
                'platform.authenticated_at' => $now,
                'platform.last_activity_at' => $now,
                'platform.sensitive_confirmed_at' => $now,
            ]);

            $user->forceFill(['last_login_at' => now()])->save();

            $this->throttle->clearAfterFullAuthentication(
                $user->email,
                (string) $request->ip(),
            );

            $this->audit->record($this->auditData->fromRequest(
                request: $request,
                eventType: 'platform_login.succeeded',
                outcome: 'success',
                subjectId: (string) $user->getKey(),
                metadata: ['second_factor' => $method->value],
                sendAlert: true,
            ));
        } catch (Throwable $exception) {
            $this->sessions->revoke((string) $user->getKey(), $request->session()->getId());
            Auth::guard('platform')->logout();
            $request->session()->invalidate();

            throw $exception;
        }

        return true;
    }
}
