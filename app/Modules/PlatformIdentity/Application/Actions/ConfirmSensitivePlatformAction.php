<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Application\Actions;

use App\Modules\PlatformIdentity\Application\Contracts\SecurityAuditRecorder;
use App\Modules\PlatformIdentity\Application\Data\SecurityAuditData;
use App\Modules\PlatformIdentity\Application\Exceptions\PlatformIdentityException;
use App\Modules\PlatformIdentity\Domain\Enums\SecondFactorMethod;
use App\Modules\PlatformIdentity\Domain\Models\PlatformUser;
use Illuminate\Support\Facades\Hash;

final readonly class ConfirmSensitivePlatformAction
{
    public function __construct(
        private VerifyPlatformSecondFactor $verifySecondFactor,
        private SecurityAuditRecorder $audit,
    ) {}

    public function handle(
        PlatformUser $user,
        string $password,
        string $secondFactor,
        SecurityAuditData $auditData,
    ): SecondFactorMethod {
        if (! Hash::check($password, $user->password)) {
            throw PlatformIdentityException::invalidCredentials();
        }

        $method = $this->verifySecondFactor->handle((string) $user->getKey(), $secondFactor);

        if (! $method instanceof SecondFactorMethod) {
            throw PlatformIdentityException::invalidSecondFactor();
        }

        $this->audit->record($auditData);

        return $method;
    }
}
