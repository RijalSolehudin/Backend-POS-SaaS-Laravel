<?php

declare(strict_types=1);

namespace App\Modules\Sync\Application\Actions;

use App\Modules\Sync\Application\Exceptions\SyncException;
use App\Modules\Sync\Domain\Enums\SyncConflictStatus;
use App\Modules\Sync\Domain\Models\SyncConflict;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;
use Carbon\CarbonImmutable;

final readonly class ResolveSyncConflict
{
    public function __construct(private TenantPermissionGuard $permissions) {}

    public function handle(TenantRequestContext $context, string $conflictId, string $reason, bool $dismiss = false): SyncConflict
    {
        $conflict = SyncConflict::query()
            ->where('tenant_id', $context->tenantId)
            ->whereKey($conflictId)
            ->first();

        if (! $conflict instanceof SyncConflict) {
            throw SyncException::conflictRequiresReview();
        }

        $this->permissions->authorizeOperatePos($context, $conflict->outlet_id);

        if ($conflict->status !== SyncConflictStatus::Open) {
            throw SyncException::conflictRequiresReview();
        }

        $conflict->forceFill([
            'status' => $dismiss ? SyncConflictStatus::Dismissed : SyncConflictStatus::Resolved,
            'resolved_by' => $context->userId,
            'resolution_reason' => trim($reason),
            'resolved_at' => CarbonImmutable::now(),
        ])->save();

        return $conflict;
    }
}
