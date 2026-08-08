<?php

declare(strict_types=1);

namespace App\Modules\Reservation\Application\Actions;

use App\Modules\Reservation\Application\Exceptions\ReservationException;
use App\Modules\Reservation\Domain\Enums\ReservationStatus;
use App\Modules\Reservation\Domain\Models\Reservation;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;

final readonly class ChangeReservationStatus
{
    public function __construct(private TenantPermissionGuard $permissions) {}

    public function handle(TenantRequestContext $context, string $reservationId, ReservationStatus $status): Reservation
    {
        $reservation = Reservation::query()
            ->where('tenant_id', $context->tenantId)
            ->whereKey($reservationId)
            ->first();

        if (! $reservation instanceof Reservation) {
            throw ReservationException::notFound();
        }

        $this->permissions->authorizeOperatePos($context, $reservation->outlet_id);

        if (! $this->canTransition($reservation->status, $status)) {
            throw ReservationException::invalidState();
        }

        $reservation->forceFill(['status' => $status])->save();

        return $reservation;
    }

    private function canTransition(ReservationStatus $current, ReservationStatus $next): bool
    {
        return match ($current) {
            ReservationStatus::Pending => in_array($next, [ReservationStatus::Confirmed, ReservationStatus::Cancelled, ReservationStatus::NoShow], true),
            ReservationStatus::Confirmed => in_array($next, [ReservationStatus::Seated, ReservationStatus::Cancelled, ReservationStatus::NoShow], true),
            ReservationStatus::Seated, ReservationStatus::Cancelled, ReservationStatus::NoShow => false,
        };
    }
}
