<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Actions;

use App\Modules\Tenancy\Application\Contracts\TenancyAuditRecorder;
use App\Modules\Tenancy\Application\Data\TenancyAuditData;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Exceptions\TenancyException;
use App\Modules\Tenancy\Application\Services\OutletInput;
use App\Modules\Tenancy\Application\Services\TenantOwnerGuard;
use App\Modules\Tenancy\Domain\Models\Outlet;
use App\Shared\Application\Context\ActorContext;
use Illuminate\Support\Facades\DB;

final readonly class UpdateOutlet
{
    public function __construct(
        private TenantOwnerGuard $ownerGuard,
        private OutletInput $input,
        private TenancyAuditRecorder $audit,
    ) {}

    public function handle(
        TenantRequestContext $context,
        string $outletId,
        string $name,
        string $code,
        ActorContext $actor,
    ): Outlet {
        $this->ownerGuard->authorize($context);
        $input = $this->input->validate($name, $code);

        return DB::transaction(function () use ($context, $outletId, $input, $actor): Outlet {
            $outlet = Outlet::query()
                ->where('tenant_id', $context->tenantId)
                ->whereKey($outletId)
                ->lockForUpdate()
                ->first();

            if (! $outlet instanceof Outlet) {
                throw TenancyException::outletNotFound();
            }

            if (Outlet::query()
                ->where('tenant_id', $context->tenantId)
                ->where('code', $input['code'])
                ->whereKeyNot($outletId)
                ->exists()) {
                throw TenancyException::outletCodeUnavailable();
            }

            $outlet->forceFill($input)->save();
            $this->audit->record(new TenancyAuditData(
                eventType: 'outlet.updated',
                outcome: 'success',
                actorType: $actor->actorType,
                actorId: $actor->actorId,
                correlationId: $actor->correlationId,
                targetTenantId: $context->tenantId,
                metadata: ['outlet_id' => $outletId],
            ));

            return $outlet;
        });
    }
}
