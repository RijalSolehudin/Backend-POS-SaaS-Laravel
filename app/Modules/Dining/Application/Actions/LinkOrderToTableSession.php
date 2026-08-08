<?php

declare(strict_types=1);

namespace App\Modules\Dining\Application\Actions;

use App\Modules\Dining\Application\Exceptions\DiningException;
use App\Modules\Dining\Domain\Enums\TableSessionStatus;
use App\Modules\Dining\Domain\Models\DiningTableSession;
use App\Modules\Dining\Domain\Models\DiningTableSessionOrder;
use App\Modules\Sales\Application\Contracts\DiningOrderReference;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;

final readonly class LinkOrderToTableSession
{
    public function __construct(
        private TenantPermissionGuard $permissions,
        private DiningOrderReference $orders,
    ) {}

    public function handle(TenantRequestContext $context, string $sessionId, string $orderId): DiningTableSessionOrder
    {
        $session = $this->openSession($context, $sessionId);
        $this->permissions->authorizeOperatePos($context, $session->outlet_id);

        $order = $this->orders->order($context->tenantId, $session->outlet_id, $orderId);

        if ($order === null) {
            throw DiningException::orderNotFound();
        }

        $existing = DiningTableSessionOrder::query()
            ->where('tenant_id', $context->tenantId)
            ->where('order_id', $orderId)
            ->first();

        if ($existing instanceof DiningTableSessionOrder) {
            return $existing;
        }

        return DiningTableSessionOrder::query()->create([
            'tenant_id' => $context->tenantId,
            'outlet_id' => $session->outlet_id,
            'table_session_id' => $session->id,
            'order_id' => $orderId,
        ]);
    }

    private function openSession(TenantRequestContext $context, string $sessionId): DiningTableSession
    {
        $session = DiningTableSession::query()
            ->where('tenant_id', $context->tenantId)
            ->whereKey($sessionId)
            ->first();

        if (! $session instanceof DiningTableSession) {
            throw DiningException::tableSessionNotFound();
        }

        if ($session->status !== TableSessionStatus::Open) {
            throw DiningException::tableSessionInvalidState();
        }

        return $session;
    }
}
