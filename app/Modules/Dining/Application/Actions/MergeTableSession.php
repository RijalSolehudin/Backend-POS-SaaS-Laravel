<?php

declare(strict_types=1);

namespace App\Modules\Dining\Application\Actions;

use App\Modules\Dining\Application\Exceptions\DiningException;
use App\Modules\Dining\Domain\Enums\TableSessionStatus;
use App\Modules\Dining\Domain\Models\DiningTableSession;
use App\Modules\Dining\Domain\Models\DiningTableSessionOrder;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class MergeTableSession
{
    public function __construct(private TenantPermissionGuard $permissions) {}

    public function handle(TenantRequestContext $context, string $sourceSessionId, string $targetSessionId): DiningTableSession
    {
        if ($sourceSessionId === $targetSessionId) {
            throw DiningException::tableSessionInvalidState();
        }

        return DB::transaction(function () use ($context, $sourceSessionId, $targetSessionId): DiningTableSession {
            $source = $this->openSession($context, $sourceSessionId);
            $target = $this->openSession($context, $targetSessionId);
            $this->permissions->authorizeOperatePos($context, $source->outlet_id);

            if ($source->outlet_id !== $target->outlet_id) {
                throw DiningException::tableSessionInvalidState();
            }

            DiningTableSessionOrder::query()
                ->where('tenant_id', $context->tenantId)
                ->where('table_session_id', $source->id)
                ->update(['table_session_id' => $target->id]);

            $source->forceFill([
                'status' => TableSessionStatus::Merged,
                'target_session_id' => $target->id,
                'open_table_key' => null,
                'merged_at' => CarbonImmutable::now(),
            ])->save();

            return $source;
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
