<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Presentation\Http\Controllers;

use App\Modules\PlatformIdentity\Application\Actions\VerifyPlatformSecondFactor;
use App\Modules\PlatformIdentity\Application\Contracts\PlatformSessionRepository;
use App\Modules\PlatformIdentity\Application\Contracts\SecurityAuditRecorder;
use App\Modules\PlatformIdentity\Domain\Enums\SecondFactorMethod;
use App\Modules\PlatformIdentity\Domain\Models\PlatformUser;
use App\Modules\PlatformIdentity\Presentation\Http\Support\PlatformAuditDataFactory;
use App\Modules\PlatformIdentity\Presentation\Http\Support\PlatformAuthenticationSession;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final readonly class PlatformMfaChallengeController
{
    public function __construct(
        private VerifyPlatformSecondFactor $verify,
        private PlatformSessionRepository $sessions,
        private SecurityAuditRecorder $audit,
        private PlatformAuditDataFactory $auditData,
        private PlatformAuthenticationSession $authenticationSession,
    ) {}

    public function create(Request $request): View|RedirectResponse
    {
        $user = $this->challengedUser($request);

        if (! $user instanceof PlatformUser || ! $user->isActive()) {
            return $this->expiredChallenge($request);
        }

        return view('platform-identity::mfa-challenge', ['email' => $user->email]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32'],
        ]);
        $user = $this->challengedUser($request);

        if (! $user instanceof PlatformUser || ! $user->isActive()) {
            return $this->expiredChallenge($request);
        }

        $method = $this->verify->handle((string) $user->getKey(), $validated['code']);

        if (! $method instanceof SecondFactorMethod) {
            $challenge = $request->session()->get('platform.login_challenge', []);
            $failures = (int) ($challenge['mfa_failures'] ?? 0) + 1;
            $challenge['mfa_failures'] = $failures;
            $request->session()->put('platform.login_challenge', $challenge);

            $this->audit->record($this->auditData->fromRequest(
                request: $request,
                eventType: 'platform_login.mfa_failed',
                outcome: 'failure',
                subjectId: (string) $user->getKey(),
                metadata: ['challenge_cancelled' => $failures >= 5],
                sendAlert: $failures >= 5,
            ));

            if ($failures >= 5) {
                return $this->expiredChallenge($request);
            }

            return back()->withErrors(['code' => 'The supplied authentication code is invalid.']);
        }

        $activeSessions = $this->sessions->activeFor((string) $user->getKey());

        if (count($activeSessions) >= (int) config('platform_identity.session.max_active', 2)) {
            $choices = [];

            foreach ($activeSessions as $session) {
                $token = Str::random(40);
                $choices[$token] = [
                    'session_id' => $session->id,
                    'ip_address' => $session->ipAddress,
                    'user_agent' => $session->userAgent,
                    'last_activity_at' => $session->lastActivityAt->format(DATE_ATOM),
                ];
            }

            $request->session()->put('platform.session_replacement', [
                'platform_user_id' => (string) $user->getKey(),
                'verified_at' => now()->getTimestamp(),
                'second_factor' => $method->value,
                'choices' => $choices,
            ]);

            return new RedirectResponse(route('platform.session-replacement'));
        }

        if (! $this->authenticationSession->establish($request, $user, $method)) {
            return new RedirectResponse(route('platform.login'), 302, [
                'X-Platform-Auth-Reason' => 'session-limit-changed',
            ]);
        }

        return new RedirectResponse(route('platform.security'));
    }

    private function challengedUser(Request $request): ?PlatformUser
    {
        $challenge = $request->session()->get('platform.login_challenge');

        if (! is_array($challenge)) {
            return null;
        }

        $issuedAt = (int) ($challenge['issued_at'] ?? 0);
        $ttl = (int) config('platform_identity.challenge_ttl_seconds', 300);

        if ($issuedAt === 0 || now()->getTimestamp() - $issuedAt > $ttl) {
            return null;
        }

        $userId = $challenge['platform_user_id'] ?? null;

        return is_string($userId) ? PlatformUser::query()->find($userId) : null;
    }

    private function expiredChallenge(Request $request): RedirectResponse
    {
        $request->session()->forget(['platform.login_challenge', 'platform.session_replacement']);

        return new RedirectResponse(route('platform.login'), 302, [
            'X-Platform-Auth-Reason' => 'challenge-expired',
        ]);
    }
}
