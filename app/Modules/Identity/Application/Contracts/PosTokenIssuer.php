<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Contracts;

use App\Modules\Identity\Application\Data\IssuedPosToken;
use Carbon\CarbonImmutable;

interface PosTokenIssuer
{
    public function replaceForDevice(string $userId, string $deviceId, CarbonImmutable $expiresAt): IssuedPosToken;
}
