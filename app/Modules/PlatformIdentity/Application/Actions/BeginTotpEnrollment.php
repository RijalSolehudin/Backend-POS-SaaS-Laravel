<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Application\Actions;

use App\Modules\PlatformIdentity\Application\Contracts\QrCodeRenderer;
use App\Modules\PlatformIdentity\Application\Contracts\TotpAuthenticator;
use App\Modules\PlatformIdentity\Application\Data\TotpEnrollmentData;

final readonly class BeginTotpEnrollment
{
    public function __construct(
        private TotpAuthenticator $totp,
        private QrCodeRenderer $qrCode,
    ) {}

    public function handle(string $email, ?string $existingSecret = null): TotpEnrollmentData
    {
        $secret = $existingSecret ?? $this->totp->generateSecret();
        $uri = $this->totp->provisioningUri($secret, $email);

        return new TotpEnrollmentData(
            secret: $secret,
            provisioningUri: $uri,
            qrSvg: $this->qrCode->asSvg($uri),
        );
    }
}
