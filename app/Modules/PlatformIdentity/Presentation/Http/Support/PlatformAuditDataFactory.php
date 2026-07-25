<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Presentation\Http\Support;

use App\Modules\PlatformIdentity\Application\Data\SecurityAuditData;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class PlatformAuditDataFactory
{
    /**
     * @param  array<string, bool|int|string|null>  $metadata
     */
    public function fromRequest(
        Request $request,
        string $eventType,
        string $outcome,
        ?string $subjectId = null,
        array $metadata = [],
        bool $sendAlert = false,
    ): SecurityAuditData {
        $correlationId = $this->correlationId($request);
        $sessionId = $request->hasSession() ? $request->session()->getId() : null;

        return new SecurityAuditData(
            eventType: $eventType,
            outcome: $outcome,
            correlationId: $correlationId,
            actorType: $subjectId !== null ? 'platform_user' : null,
            actorId: $subjectId,
            subjectType: $subjectId !== null ? 'platform_user' : null,
            subjectId: $subjectId,
            requestId: $request->headers->get('X-Request-ID'),
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
            sessionIdHash: is_string($sessionId) && $sessionId !== '' ? hash('sha256', $sessionId) : null,
            metadata: $metadata,
            sendAlert: $sendAlert,
        );
    }

    public function correlationId(Request $request): string
    {
        $existing = $request->attributes->get('platform_correlation_id');

        if (is_string($existing)) {
            return $existing;
        }

        $correlationId = strtolower((string) Str::ulid());
        $request->attributes->set('platform_correlation_id', $correlationId);

        return $correlationId;
    }
}
