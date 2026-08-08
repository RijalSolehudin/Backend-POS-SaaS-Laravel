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
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class CloseTableSession
{
    public function __construct(
        private TenantPermissionGuard $permissions,
        private DiningOrderReference $orders,
    ) {}

    public function handle(TenantRequestContext $context, string $sessionId): DiningTableSession
    {
        return DB::transaction(function () use ($context, $sessionId): DiningTableSession {
            $session = $this->openSession($context, $sessionId);
            $this->permissions->authorizeOperatePos($context, $session->outlet_id);

            $links = DiningTableSessionOrder::query()
                ->where('tenant_id', $context->tenantId)
                ->where('table_session_id', $session->id)
                ->get();

            foreach ($links as $link) {
                $order = $this->orders->order($context->tenantId, $session->outlet_id, $link->order_id);

                if ($order === null || ! $order->isTerminal()) {
                    throw DiningException::tableSessionInvalidState();
                }
            }

            $session->forceFill([
                'status' => TableSessionStatus::Closed,
                'open_table_key' => null,
                'closed_by' => $context->userId,
                'closed_at' => CarbonImmutable::now(),
            ])->save();

            return $session;
        });
    }

    private function openSession(TenantRequestContext $context, string $sessionId): DiningTableSession
    {
        $session = DiningTableSession::query()
            ->where('tenant_id', $context->tenantId)
            ->whereKey($sessionId)
            ->lockForUpdate()
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
