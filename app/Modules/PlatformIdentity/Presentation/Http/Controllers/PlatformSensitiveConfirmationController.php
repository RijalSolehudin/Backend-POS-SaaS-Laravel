<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Presentation\Http\Controllers;

use App\Modules\PlatformIdentity\Application\Actions\ConfirmSensitivePlatformAction;
use App\Modules\PlatformIdentity\Application\Contracts\SecurityAuditRecorder;
use App\Modules\PlatformIdentity\Application\Exceptions\PlatformIdentityException;
use App\Modules\PlatformIdentity\Domain\Models\PlatformUser;
use App\Modules\PlatformIdentity\Presentation\Http\Support\PlatformAuditDataFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final readonly class PlatformSensitiveConfirmationController
{
    public function __construct(
        private ConfirmSensitivePlatformAction $confirm,
        private PlatformAuditDataFactory $auditData,
        private SecurityAuditRecorder $audit,
    ) {}

    public function create(): View
    {
        return view('platform-identity::sensitive-confirmation');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'max:128'],
            'code' => ['required', 'string', 'max:32'],
        ]);
        $user = Auth::guard('platform')->user();

        if (! $user instanceof PlatformUser) {
            return new RedirectResponse(route('platform.login'));
        }

        try {
            $this->confirm->handle(
                user: $user,
                password: (string) $validated['password'],
                secondFactor: (string) $validated['code'],
                auditData: $this->auditData->fromRequest(
                    request: $request,
                    eventType: 'platform_sensitive_confirmation.succeeded',
                    outcome: 'success',
                    subjectId: (string) $user->getKey(),
                ),
            );
        } catch (PlatformIdentityException) {
            $this->audit->record($this->auditData->fromRequest(
                request: $request,
                eventType: 'platform_sensitive_confirmation.failed',
                outcome: 'failure',
                subjectId: (string) $user->getKey(),
            ));

            return back()->withErrors([
                'password' => 'The supplied confirmation credentials are invalid.',
            ]);
        }

        $request->session()->put('platform.sensitive_confirmed_at', now()->getTimestamp());

        return new RedirectResponse(
            $request->session()->pull('url.intended', route('platform.security')),
        );
    }
}
