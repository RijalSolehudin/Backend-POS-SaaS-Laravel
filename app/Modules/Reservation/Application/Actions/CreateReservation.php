<?php

declare(strict_types=1);

namespace App\Modules\Reservation\Application\Actions;

use App\Modules\Reservation\Application\Data\ReservationInput;
use App\Modules\Reservation\Domain\Enums\ReservationStatus;
use App\Modules\Reservation\Domain\Models\Reservation;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;

final readonly class CreateReservation
{
    public function __construct(private TenantPermissionGuard $permissions) {}

    public function handle(TenantRequestContext $context, ReservationInput $input): Reservation
    {
        $this->permissions->authorizeOperatePos($context, $input->outletId);

        return Reservation::query()->create([
            'tenant_id' => $context->tenantId,
            'outlet_id' => $input->outletId,
            'table_id' => $input->tableId,
            'customer_name' => $input->customerName === null ? null : trim($input->customerName),
            'customer_phone' => $input->customerPhone === null ? null : trim($input->customerPhone),
            'party_size' => $input->partySize,
            'reserved_at' => $input->reservedAt,
            'status' => ReservationStatus::Pending,
            'notes' => $input->notes,
        ]);
    }
}
