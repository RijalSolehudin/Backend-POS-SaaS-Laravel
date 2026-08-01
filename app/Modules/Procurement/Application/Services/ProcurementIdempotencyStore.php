<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Application\Services;

use App\Modules\Procurement\Domain\Models\ProcurementIdempotencyRecord;

final class ProcurementIdempotencyStore
{
    public function findForUpdate(
        string $tenantId,
        string $outletId,
        string $userId,
        string $action,
        string $idempotencyKey,
    ): ?ProcurementIdempotencyRecord {
        return ProcurementIdempotencyRecord::query()
            ->where('tenant_id', $tenantId)
            ->where('outlet_id', $outletId)
            ->where('user_id', $userId)
            ->where('action', $action)
            ->where('idempotency_key', $idempotencyKey)
            ->lockForUpdate()
            ->first();
    }

    /**
     * @param  array<string, mixed>  $responseBody
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
        int $responseStatus = 200,
        array $responseBody = [],
    ): ProcurementIdempotencyRecord {
        return ProcurementIdempotencyRecord::query()->create([
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
            'expires_at' => now()->addDay(),
        ]);
    }
}
