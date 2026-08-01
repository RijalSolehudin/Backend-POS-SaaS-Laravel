<?php

declare(strict_types=1);

namespace App\Modules\Sales\Application\Services;

use App\Modules\Sales\Domain\Models\IdempotencyRecord;
use App\Modules\Tenancy\Application\Data\PosOutletContext;
use Carbon\CarbonImmutable;

final readonly class IdempotencyStore
{
    public function findForUpdate(
        string $tenantId,
        string $outletId,
        string $userId,
        string $action,
        string $idempotencyKey,
    ): ?IdempotencyRecord {
        return IdempotencyRecord::query()
            ->where('tenant_id', $tenantId)
            ->where('outlet_id', $outletId)
            ->where('user_id', $userId)
            ->where('action', $action)
            ->where('idempotency_key', $idempotencyKey)
            ->lockForUpdate()
            ->first();
    }

    public function findForContext(PosOutletContext $context, string $action, string $idempotencyKey): ?IdempotencyRecord
    {
        return $this->findForUpdate(
            tenantId: $context->tenantId,
            outletId: $context->outletId,
            userId: $context->userId,
            action: $action,
            idempotencyKey: $idempotencyKey,
        );
    }

    /**
     * @param  array<string, mixed>|null  $responseBody
     */
    public function create(
        string $tenantId,
        string $outletId,
        string $userId,
        string $action,
        string $idempotencyKey,
        string $requestHash,
        string $resourceType,
        string $resourceId,
        int $responseStatus,
        ?array $responseBody,
        ?CarbonImmutable $expiresAt = null,
    ): IdempotencyRecord {
        return IdempotencyRecord::query()->create([
            'tenant_id' => $tenantId,
            'outlet_id' => $outletId,
            'user_id' => $userId,
            'action' => $action,
            'idempotency_key' => $idempotencyKey,
            'request_hash' => $requestHash,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'response_status' => $responseStatus,
            'response_body' => $responseBody,
            'expires_at' => $expiresAt ?? now()->addDay(),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $responseBody
     */
    public function createForContext(
        PosOutletContext $context,
        string $action,
        string $idempotencyKey,
        string $requestHash,
        string $resourceType,
        string $resourceId,
        int $responseStatus,
        ?array $responseBody,
        ?CarbonImmutable $expiresAt = null,
    ): IdempotencyRecord {
        return $this->create(
            tenantId: $context->tenantId,
            outletId: $context->outletId,
            userId: $context->userId,
            action: $action,
            idempotencyKey: $idempotencyKey,
            requestHash: $requestHash,
            resourceType: $resourceType,
            resourceId: $resourceId,
            responseStatus: $responseStatus,
            responseBody: $responseBody,
            expiresAt: $expiresAt,
        );
    }
}
