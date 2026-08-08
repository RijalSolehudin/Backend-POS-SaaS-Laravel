<?php

declare(strict_types=1);

namespace App\Modules\Dining\Application\Actions;

use App\Modules\Dining\Application\Exceptions\DiningException;
use App\Modules\Dining\Domain\Enums\TableSessionStatus;
use App\Modules\Dining\Domain\Enums\TableStatus;
use App\Modules\Dining\Domain\Models\DiningTable;
use App\Modules\Dining\Domain\Models\DiningTableSession;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class TransferTableSession
{
    public function __construct(private TenantPermissionGuard $permissions) {}

    public function handle(TenantRequestContext $context, string $sessionId, string $targetTableId): DiningTableSession
    {
        return DB::transaction(function () use ($context, $sessionId, $targetTableId): DiningTableSession {
            $session = $this->openSession($context, $sessionId);
            $this->permissions->authorizeOperatePos($context, $session->outlet_id);
            $target = DiningTable::query()
                ->where('tenant_id', $context->tenantId)
                ->where('outlet_id', $session->outlet_id)
                ->where('status', TableStatus::Active)
                ->whereKey($targetTableId)
                ->first();

            if (! $target instanceof DiningTable) {
                throw DiningException::tableNotFound();
            }

            if ($target->id !== $session->table_id && $this->openSessionExists($context->tenantId, $session->outlet_id, $target->id)) {
                throw DiningException::tableOccupied();
            }

            $session->forceFill([
                'previous_table_id' => $session->table_id,
                'table_id' => $target->id,
                'open_table_key' => $this->openTableKey($context->tenantId, $session->outlet_id, $target->id),
                'transferred_at' => CarbonImmutable::now(),
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

    private function openSessionExists(string $tenantId, string $outletId, string $tableId): bool
    {
        return DiningTableSession::query()
            ->where('open_table_key', $this->openTableKey($tenantId, $outletId, $tableId))
            ->exists();
    }

    private function openTableKey(string $tenantId, string $outletId, string $tableId): string
    {
        return "{$tenantId}:{$outletId}:{$tableId}";
    }
}
