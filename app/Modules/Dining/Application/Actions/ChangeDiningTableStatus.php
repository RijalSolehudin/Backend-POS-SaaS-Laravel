<?php

declare(strict_types=1);

namespace App\Modules\Dining\Application\Actions;

use App\Modules\Dining\Application\Exceptions\DiningException;
use App\Modules\Dining\Domain\Enums\TableStatus;
use App\Modules\Dining\Domain\Models\DiningTable;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;

final readonly class ChangeDiningTableStatus
{
    public function __construct(private TenantPermissionGuard $permissions) {}

    public function handle(TenantRequestContext $context, string $tableId, TableStatus $status): DiningTable
    {
        $this->permissions->authorizeManageOutlets($context);
        $table = DiningTable::query()
            ->where('tenant_id', $context->tenantId)
            ->whereKey($tableId)
            ->first();

        if (! $table instanceof DiningTable) {
            throw DiningException::tableNotFound();
        }

        $table->forceFill(['status' => $status])->save();

        return $table;
    }
}
