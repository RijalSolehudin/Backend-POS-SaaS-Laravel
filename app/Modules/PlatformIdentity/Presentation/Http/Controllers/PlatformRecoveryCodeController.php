<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Presentation\Http\Controllers;

use App\Modules\PlatformIdentity\Application\Actions\RegeneratePlatformRecoveryCodes;
use App\Modules\PlatformIdentity\Domain\Models\PlatformUser;
use App\Modules\PlatformIdentity\Presentation\Http\Support\PlatformAuditDataFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final readonly class PlatformRecoveryCodeController
{
    public function __construct(
        private RegeneratePlatformRecoveryCodes $regenerate,
        private PlatformAuditDataFactory $auditData,
    ) {}

    public function store(Request $request): View|RedirectResponse
    {
        $user = Auth::guard('platform')->user();

        if (! $user instanceof PlatformUser) {
            return new RedirectResponse(route('platform.login'));
        }

        $codes = $this->regenerate->handle(
            $user,
            $this->auditData->fromRequest(
                request: $request,
                eventType: 'platform_recovery_codes.regenerated',
                outcome: 'success',
                subjectId: (string) $user->getKey(),
                sendAlert: true,
            ),
        );

        return view('platform-identity::recovery-codes', ['recoveryCodes' => $codes]);
    }
}
