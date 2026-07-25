<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Application\Contracts;

use App\Modules\PlatformIdentity\Application\Data\ActivePlatformSession;

interface PlatformSessionRepository
{
    /**
     * @return list<ActivePlatformSession>
     */
    public function activeFor(string $platformUserId): array;

    public function revoke(string $platformUserId, string $sessionId): bool;

    public function revokeAll(string $platformUserId): int;

    public function reserve(
        string $platformUserId,
        string $sessionId,
        ?string $ipAddress,
        ?string $userAgent,
    ): bool;
}
