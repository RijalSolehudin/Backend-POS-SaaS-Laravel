<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Application\Contracts;

interface TotpAuthenticator
{
    public function generateSecret(): string;

    public function provisioningUri(string $secret, string $email): string;

    public function matchingTimeStep(string $secret, string $code): ?int;
}
