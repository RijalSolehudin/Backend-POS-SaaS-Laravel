<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Infrastructure\Audit;

use App\Modules\Tenancy\Application\Contracts\TenancyAuditRecorder;
use App\Modules\Tenancy\Application\Data\TenancyAuditData;
use App\Modules\Tenancy\Domain\Models\TenancyAuditEvent;
use App\Shared\Application\Audit\AuditMetadataRedactor;

final readonly class DatabaseTenancyAuditRecorder implements TenancyAuditRecorder
{
    public function __construct(private AuditMetadataRedactor $redactor) {}

    public function record(TenancyAuditData $data): string
    {
        $event = TenancyAuditEvent::query()->create([
            'event_type' => $data->eventType,
            'outcome' => $data->outcome,
            'actor_type' => $data->actorType,
            'actor_id' => $data->actorId,
            'target_tenant_id' => $data->targetTenantId,
            'correlation_id' => $data->correlationId,
            'reason' => $data->reason,
            'metadata' => $this->redactor->redact($data->metadata),
            'occurred_at' => now(),
        ]);

        return (string) $event->getKey();
    }
}
