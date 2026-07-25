<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Presentation\Http\Controllers;

use App\Modules\PlatformIdentity\Application\Actions\BeginTotpEnrollment;
use App\Modules\PlatformIdentity\Application\Actions\ConfirmTotpEnrollment;
use App\Modules\PlatformIdentity\Application\Contracts\SecurityAuditRecorder;
use App\Modules\PlatformIdentity\Application\Exceptions\PlatformIdentityException;
use App\Modules\PlatformIdentity\Domain\Enums\SecondFactorMethod;
use App\Modules\PlatformIdentity\Domain\Models\PlatformUser;
use App\Modules\PlatformIdentity\Presentation\Http\Support\PlatformAuditDataFactory;
use App\Modules\PlatformIdentity\Presentation\Http\Support\PlatformAuthenticationSession;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class PlatformMfaEnrollmentController
{
    public function __construct(
        private BeginTotpEnrollment $beginEnrollment,
        private ConfirmTotpEnrollment $confirmEnrollment,
        private PlatformAuditDataFactory $auditData,
        private PlatformAuthenticationSession $authenticationSession,
        private SecurityAuditRecorder $audit,
    ) {}

    public function create(Request $request): View|RedirectResponse
    {
        $user = $this->challengedUser($request);

        if (! $user instanceof PlatformUser || ! $user->requiresMfaSetup()) {
            return new RedirectResponse(route('platform.login'));
        }

        $existingSecret = $request->session()->get('platform.totp_enrollment_secret');
        $enrollment = $this->beginEnrollment->handle(
            $user->email,
            is_string($existingSecret) ? $existingSecret : null,
        );
        $request->session()->put('platform.totp_enrollment_secret', $enrollment->secret);

        return view('platform-identity::mfa-enrollment', [
            'email' => $user->email,
            'secret' => $enrollment->secret,
            'qrSvg' => $enrollment->qrSvg,
        ]);
    }

    public function store(Request $request): View|RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);
        $user = $this->challengedUser($request);
        $secret = $request->session()->get('platform.totp_enrollment_secret');

        if (! $user instanceof PlatformUser || ! $user->requiresMfaSetup() || ! is_string($secret)) {
            return new RedirectResponse(route('platform.login'));
        }

        try {
            $recoveryCodes = $this->confirmEnrollment->handle(
                platformUserId: (string) $user->getKey(),
                secret: $secret,
                code: (string) $validated['code'],
                auditData: $this->auditData->fromRequest(
                    request: $request,
                    eventType: 'platform_mfa.enrolled',
                    outcome: 'success',
                    subjectId: (string) $user->getKey(),
                    sendAlert: true,
                ),
            );
        } catch (PlatformIdentityException) {
            $this->audit->record($this->auditData->fromRequest(
                request: $request,
                eventType: 'platform_mfa.enrollment_failed',
                outcome: 'failure',
                subjectId: (string) $user->getKey(),
            ));

            return back()->withErrors(['code' => 'The supplied authentication code is invalid.']);
        }

        if (! $this->authenticationSession->establish(
            $request,
            $user->fresh() ?? $user,
            SecondFactorMethod::Totp,
        )) {
            return new RedirectResponse(route('platform.login'), 302, [
                'X-Platform-Auth-Reason' => 'session-limit-changed',
            ]);
        }

        return view('platform-identity::recovery-codes', [
            'recoveryCodes' => $recoveryCodes,
        ]);
    }

    private function challengedUser(Request $request): ?PlatformUser
    {
        $challenge = $request->session()->get('platform.login_challenge');

        if (! is_array($challenge)) {
            return null;
        }

        $issuedAt = (int) ($challenge['issued_at'] ?? 0);

        if (
            $issuedAt === 0
            || now()->getTimestamp() - $issuedAt > (int) config('platform_identity.challenge_ttl_seconds', 300)
        ) {
            return null;
        }

        $userId = $challenge['platform_user_id'] ?? null;

        return is_string($userId) ? PlatformUser::query()->find($userId) : null;
    }
}
