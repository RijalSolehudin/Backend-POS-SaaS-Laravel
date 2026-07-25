<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Presentation\Http\Controllers;

use App\Modules\PlatformIdentity\Application\Contracts\SecurityAuditRecorder;
use App\Modules\PlatformIdentity\Domain\Models\PlatformUser;
use App\Modules\PlatformIdentity\Presentation\Http\Support\PlatformAuditDataFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final readonly class PlatformLogoutController
{
    public function __construct(
        private SecurityAuditRecorder $audit,
        private PlatformAuditDataFactory $auditData,
    ) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $user = Auth::guard('platform')->user();

        if ($user instanceof PlatformUser) {
            $this->audit->record($this->auditData->fromRequest(
                request: $request,
                eventType: 'platform_logout.succeeded',
                outcome: 'success',
                subjectId: (string) $user->getKey(),
            ));
        }

        Auth::guard('platform')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return new RedirectResponse(route('platform.login'));
    }
}
