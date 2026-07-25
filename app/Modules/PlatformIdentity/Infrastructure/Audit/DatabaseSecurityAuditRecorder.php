<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Infrastructure\Audit;

use App\Modules\PlatformIdentity\Application\Contracts\SecurityAuditRecorder;
use App\Modules\PlatformIdentity\Application\Data\SecurityAuditData;
use App\Modules\PlatformIdentity\Domain\Models\PlatformSecurityAuditEvent;
use App\Modules\PlatformIdentity\Domain\Models\PlatformUser;
use App\Modules\PlatformIdentity\Infrastructure\Notifications\PlatformSecurityAlert;
use Illuminate\Support\Facades\Notification;

final class DatabaseSecurityAuditRecorder implements SecurityAuditRecorder
{
    public function record(SecurityAuditData $data): string
    {
        $event = PlatformSecurityAuditEvent::query()->create([
            'event_type' => $data->eventType,
            'outcome' => $data->outcome,
            'actor_type' => $data->actorType,
            'actor_id' => $data->actorId,
            'subject_type' => $data->subjectType,
            'subject_id' => $data->subjectId,
            'correlation_id' => $data->correlationId,
            'request_id' => $data->requestId,
            'ip_address' => $data->ipAddress,
            'user_agent' => $data->userAgent !== null ? mb_substr($data->userAgent, 0, 500) : null,
            'session_id_hash' => $data->sessionIdHash,
            'reason' => $data->reason,
            'metadata' => $data->metadata,
            'occurred_at' => now(),
        ]);

        if ($data->sendAlert) {
            $this->sendAlert($data);
        }

        return (string) $event->getKey();
    }

    private function sendAlert(SecurityAuditData $data): void
    {
        $notification = new PlatformSecurityAlert(
            eventType: $data->eventType,
            outcome: $data->outcome,
            occurredAt: now()->toIso8601String(),
            ipAddress: $data->ipAddress,
            correlationId: $data->correlationId,
        );

        if ($data->subjectType === 'platform_user' && $data->subjectId !== null) {
            $user = PlatformUser::query()->find($data->subjectId);
            $user?->notify($notification);
        }

        $securityMailbox = config('platform_identity.security_mailbox');

        if (is_string($securityMailbox) && $securityMailbox !== '') {
            Notification::route('mail', $securityMailbox)->notify($notification);
        }
    }
}
