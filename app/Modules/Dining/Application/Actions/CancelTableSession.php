<?php

declare(strict_types=1);

namespace App\Modules\Dining\Application\Actions;

use App\Modules\Dining\Application\Exceptions\DiningException;
use App\Modules\Dining\Domain\Enums\TableSessionStatus;
use App\Modules\Dining\Domain\Models\DiningTableSession;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;
use Carbon\CarbonImmutable;

final readonly class CancelTableSession
{
    public function __construct(private TenantPermissionGuard $permissions) {}

    public function handle(TenantRequestContext $context, string $sessionId, string $reason): DiningTableSession
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

        $this->permissions->authorizeOperatePos($context, $session->outlet_id);

        $session->forceFill([
            'status' => TableSessionStatus::Cancelled,
            'open_table_key' => null,
            'cancelled_by' => $context->userId,
            'cancelled_at' => CarbonImmutable::now(),
            'cancel_reason' => trim($reason),
        ])->save();

        return $session;
    }
}
