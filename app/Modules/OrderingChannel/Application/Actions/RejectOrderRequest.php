<?php

declare(strict_types=1);

namespace App\Modules\OrderingChannel\Application\Actions;

use App\Modules\OrderingChannel\Application\Exceptions\OrderingChannelException;
use App\Modules\OrderingChannel\Domain\Enums\OrderRequestStatus;
use App\Modules\OrderingChannel\Domain\Models\OrderingOrderRequest;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;
use Carbon\CarbonImmutable;

final readonly class RejectOrderRequest
{
    public function __construct(private TenantPermissionGuard $permissions) {}

    public function handle(TenantRequestContext $context, string $requestId, string $reason): OrderingOrderRequest
    {
        $request = OrderingOrderRequest::query()
            ->where('tenant_id', $context->tenantId)
            ->whereKey($requestId)
            ->first();

        if (! $request instanceof OrderingOrderRequest) {
            throw OrderingChannelException::orderRequestNotFound();
        }

        $this->permissions->authorizeOperatePos($context, $request->outlet_id);

        if ($request->status !== OrderRequestStatus::Pending) {
            throw OrderingChannelException::orderRequestInvalidState();
        }

        $request->forceFill([
            'status' => OrderRequestStatus::Rejected,
            'rejected_by' => $context->userId,
            'rejected_at' => CarbonImmutable::now(),
            'rejection_reason' => trim($reason),
        ])->save();

        return $request;
    }
}
