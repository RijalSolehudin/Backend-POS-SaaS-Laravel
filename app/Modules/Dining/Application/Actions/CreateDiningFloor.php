<?php

declare(strict_types=1);

namespace App\Modules\Dining\Application\Actions;

use App\Modules\Dining\Application\Data\DiningFloorInput;
use App\Modules\Dining\Application\Exceptions\DiningException;
use App\Modules\Dining\Domain\Enums\TableStatus;
use App\Modules\Dining\Domain\Models\DiningFloor;
use App\Modules\Tenancy\Application\Contracts\TenantCatalogReference;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;

final readonly class CreateDiningFloor
{
    public function __construct(
        private TenantPermissionGuard $permissions,
        private TenantCatalogReference $tenancy,
    ) {}

    public function handle(TenantRequestContext $context, DiningFloorInput $input): DiningFloor
    {
        $this->permissions->authorizeManageOutlets($context);
        $this->ensureOutletExists($context, $input->outletId);
        $this->ensureCodeAvailable($context, $input->outletId, $input->code);

        return DiningFloor::query()->create([
            'tenant_id' => $context->tenantId,
            'outlet_id' => $input->outletId,
            'name' => trim($input->name),
            'code' => $this->normalizeCode($input->code),
            'display_order' => $input->displayOrder,
            'status' => TableStatus::Active,
        ]);
    }

    private function ensureOutletExists(TenantRequestContext $context, string $outletId): void
    {
        if (! $this->tenancy->activeOutletExists($context->tenantId, $outletId)) {
            throw DiningException::outletNotFound();
        }
    }

    private function ensureCodeAvailable(TenantRequestContext $context, string $outletId, string $code): void
    {
        $exists = DiningFloor::query()
            ->where('tenant_id', $context->tenantId)
            ->where('outlet_id', $outletId)
            ->where('code', $this->normalizeCode($code))
            ->exists();

        if ($exists) {
            throw DiningException::floorCodeUnavailable();
        }
    }

    private function normalizeCode(string $code): string
    {
        return mb_strtoupper(trim($code));
    }
}
