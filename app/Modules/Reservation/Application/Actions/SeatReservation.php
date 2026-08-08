<?php

declare(strict_types=1);

namespace App\Modules\Reservation\Application\Actions;

use App\Modules\Dining\Domain\Enums\TableSessionStatus;
use App\Modules\Dining\Domain\Models\DiningTableSession;
use App\Modules\Reservation\Application\Exceptions\ReservationException;
use App\Modules\Reservation\Domain\Enums\ReservationStatus;
use App\Modules\Reservation\Domain\Models\Reservation;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;
use Carbon\CarbonImmutable;

final readonly class SeatReservation
{
    public function __construct(private TenantPermissionGuard $permissions) {}

    public function handle(TenantRequestContext $context, string $reservationId, string $tableSessionId): Reservation
    {
        $reservation = Reservation::query()
            ->where('tenant_id', $context->tenantId)
            ->whereKey($reservationId)
            ->first();

        if (! $reservation instanceof Reservation) {
            throw ReservationException::notFound();
        }

        $session = DiningTableSession::query()
            ->where('tenant_id', $context->tenantId)
            ->where('outlet_id', $reservation->outlet_id)
            ->where('status', TableSessionStatus::Open)
            ->whereKey($tableSessionId)
            ->first();

        if (! $session instanceof DiningTableSession || ! in_array($reservation->status, [ReservationStatus::Pending, ReservationStatus::Confirmed], true)) {
            throw ReservationException::invalidState();
        }

        $this->permissions->authorizeOperatePos($context, $reservation->outlet_id);

        $reservation->forceFill([
            'status' => ReservationStatus::Seated,
            'table_session_id' => $session->id,
            'table_id' => $session->table_id,
            'seated_at' => CarbonImmutable::now(),
        ])->save();

        return $reservation;
    }
}
