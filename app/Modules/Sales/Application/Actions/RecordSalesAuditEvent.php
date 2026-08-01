<?php

declare(strict_types=1);

namespace App\Modules\Sales\Application\Actions;

use App\Modules\Sales\Domain\Models\SalesAuditEvent;
use Illuminate\Support\Str;

final readonly class RecordSalesAuditEvent
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function handle(
        string $tenantId,
        ?string $outletId,
        ?string $actorUserId,
        string $eventType,
        ?string $targetType,
        ?string $targetId,
        ?string $outcome,
        ?string $reason = null,
        ?string $correlationId = null,
        ?array $metadata = null,
    ): string {
        $event = SalesAuditEvent::query()->create([
            'tenant_id' => $tenantId,
            'outlet_id' => $outletId,
            'actor_user_id' => $actorUserId,
            'event_type' => $eventType,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'outcome' => $outcome,
            'reason' => $reason,
            'correlation_id' => $correlationId ?? (string) Str::uuid(),
            'metadata' => $this->redact($metadata),
            'occurred_at' => now(),
        ]);

        return (string) $event->getKey();
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     * @return array<string, mixed>|null
     */
    private function redact(?array $metadata): ?array
    {
        if ($metadata === null) {
            return null;
        }

        $redacted = [];

        foreach ($metadata as $key => $value) {
            $normalized = strtolower((string) $key);

            if (str_contains($normalized, 'password')
                || str_contains($normalized, 'token')
                || str_contains($normalized, 'secret')
                || str_contains($normalized, 'recovery')
                || str_contains($normalized, 'card')) {
                $redacted[$key] = '[REDACTED]';

                continue;
            }

            $redacted[$key] = is_array($value) ? $this->redact($value) : $value;
        }

        return $redacted;
    }
}
