<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Infrastructure\Persistence;

use App\Modules\PlatformIdentity\Application\Contracts\PlatformSessionRepository;
use App\Modules\PlatformIdentity\Application\Data\ActivePlatformSession;
use DateTimeImmutable;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class DatabasePlatformSessionRepository implements PlatformSessionRepository
{
    public function activeFor(string $platformUserId): array
    {
        $idleCutoff = now()->subMinutes((int) config('platform_identity.session.idle_minutes', 15))->getTimestamp();
        $absoluteCutoff = now()->subMinutes((int) config('platform_identity.session.absolute_minutes', 240));

        $sessions = DB::table('platform_sessions')
            ->where('user_id', $platformUserId)
            ->where('last_activity', '>=', $idleCutoff)
            ->where('created_at', '>=', $absoluteCutoff)
            ->orderByDesc('last_activity')
            ->get()
            ->map(static fn (object $session): ActivePlatformSession => new ActivePlatformSession(
                id: (string) $session->id,
                ipAddress: is_string($session->ip_address) ? $session->ip_address : null,
                userAgent: is_string($session->user_agent) ? $session->user_agent : null,
                createdAt: new DateTimeImmutable((string) $session->created_at),
                lastActivityAt: (new DateTimeImmutable)->setTimestamp((int) $session->last_activity),
            ))
            ->all();

        return array_values($sessions);
    }

    public function revoke(string $platformUserId, string $sessionId): bool
    {
        return DB::table('platform_sessions')
            ->where('user_id', $platformUserId)
            ->where('id', $sessionId)
            ->delete() === 1;
    }

    public function revokeAll(string $platformUserId): int
    {
        return DB::table('platform_sessions')
            ->where('user_id', $platformUserId)
            ->delete();
    }

    public function reserve(
        string $platformUserId,
        string $sessionId,
        ?string $ipAddress,
        ?string $userAgent,
    ): bool {
        try {
            return Cache::lock('platform-session-slot:'.$platformUserId, 10)->block(
                5,
                function () use ($platformUserId, $sessionId, $ipAddress, $userAgent): bool {
                    return DB::transaction(function () use ($platformUserId, $sessionId, $ipAddress, $userAgent): bool {
                        if (count($this->activeFor($platformUserId)) >= (int) config('platform_identity.session.max_active', 2)) {
                            return false;
                        }

                        DB::table('platform_sessions')->updateOrInsert(
                            ['id' => $sessionId],
                            [
                                'user_id' => $platformUserId,
                                'ip_address' => $ipAddress,
                                'user_agent' => $userAgent !== null ? mb_substr($userAgent, 0, 500) : null,
                                'payload' => '',
                                'last_activity' => now()->getTimestamp(),
                                'created_at' => now(),
                            ],
                        );

                        return true;
                    });
                },
            );
        } catch (LockTimeoutException) {
            return false;
        }
    }
}
