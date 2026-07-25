<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Presentation\Http\Controllers;

use App\Modules\PlatformIdentity\Application\Contracts\PlatformSessionRepository;
use App\Modules\PlatformIdentity\Domain\Enums\SecondFactorMethod;
use App\Modules\PlatformIdentity\Domain\Models\PlatformUser;
use App\Modules\PlatformIdentity\Presentation\Http\Support\PlatformAuthenticationSession;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class PlatformSessionReplacementController
{
    public function __construct(
        private PlatformSessionRepository $sessions,
        private PlatformAuthenticationSession $authenticationSession,
    ) {}

    public function create(Request $request): View|RedirectResponse
    {
        $replacement = $this->replacement($request);

        if ($replacement === null) {
            return new RedirectResponse(route('platform.login'));
        }

        return view('platform-identity::session-replacement', [
            'choices' => $replacement['choices'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(['session_token' => ['required', 'string', 'size:40']]);
        $replacement = $this->replacement($request);

        if ($replacement === null) {
            return new RedirectResponse(route('platform.login'));
        }

        $choice = $replacement['choices'][$validated['session_token']] ?? null;

        if (! is_array($choice) || ! is_string($choice['session_id'] ?? null)) {
            return back()->withErrors(['session_token' => 'Select a valid active session.']);
        }

        $userId = $replacement['platform_user_id'];
        $user = PlatformUser::query()->find($userId);
        $method = SecondFactorMethod::tryFrom($replacement['second_factor']);

        if (! $user instanceof PlatformUser || ! $method instanceof SecondFactorMethod) {
            return new RedirectResponse(route('platform.login'));
        }

        $this->sessions->revoke($userId, $choice['session_id']);
        if (! $this->authenticationSession->establish($request, $user, $method)) {
            return new RedirectResponse(route('platform.login'), 302, [
                'X-Platform-Auth-Reason' => 'session-limit-changed',
            ]);
        }

        return new RedirectResponse(route('platform.security'));
    }

    /**
     * @return array{platform_user_id: string, verified_at: int, second_factor: string, choices: array<string, array<string, mixed>>}|null
     */
    private function replacement(Request $request): ?array
    {
        $replacement = $request->session()->get('platform.session_replacement');

        if (! is_array($replacement)) {
            return null;
        }

        $verifiedAt = (int) ($replacement['verified_at'] ?? 0);

        if (
            $verifiedAt === 0
            || now()->getTimestamp() - $verifiedAt > (int) config('platform_identity.challenge_ttl_seconds', 300)
            || ! is_string($replacement['platform_user_id'] ?? null)
            || ! is_string($replacement['second_factor'] ?? null)
            || ! is_array($replacement['choices'] ?? null)
        ) {
            return null;
        }

        return [
            'platform_user_id' => $replacement['platform_user_id'],
            'verified_at' => $verifiedAt,
            'second_factor' => $replacement['second_factor'],
            'choices' => $replacement['choices'],
        ];
    }
}
