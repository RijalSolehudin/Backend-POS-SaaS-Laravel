<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Services;

use App\Modules\Inventory\Domain\Models\InventoryIdempotencyRecord;
use Carbon\CarbonImmutable;

final readonly class InventoryIdempotencyStore
{
    public function findForUpdate(
        string $tenantId,
        string $outletId,
        string $userId,
        string $action,
        string $idempotencyKey,
    ): ?InventoryIdempotencyRecord {
        return InventoryIdempotencyRecord::query()
            ->where('tenant_id', $tenantId)
            ->where('outlet_id', $outletId)
            ->where('user_id', $userId)
            ->where('action', $action)
            ->where('idempotency_key', $idempotencyKey)
            ->lockForUpdate()
            ->first();
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
    ): InventoryIdempotencyRecord {
        return InventoryIdempotencyRecord::query()->create([
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
}
