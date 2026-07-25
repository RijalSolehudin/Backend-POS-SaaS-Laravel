<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Application\Data;

final readonly class TotpEnrollmentData
{
    public function __construct(
        public string $secret,
        public string $provisioningUri,
        public string $qrSvg,
    ) {}
}
