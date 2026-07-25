<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Actions;

use App\Modules\Tenancy\Application\Contracts\TenancyAuditRecorder;
use App\Modules\Tenancy\Application\Data\TenancyAuditData;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Exceptions\TenancyException;
use App\Modules\Tenancy\Application\Services\OutletInput;
use App\Modules\Tenancy\Application\Services\TenantOwnerGuard;
use App\Modules\Tenancy\Domain\Enums\OutletStatus;
use App\Modules\Tenancy\Domain\Models\Outlet;
use App\Shared\Application\Context\ActorContext;
use Illuminate\Support\Facades\DB;

final readonly class CreateOutlet
{
    public function __construct(
        private TenantOwnerGuard $ownerGuard,
        private OutletInput $input,
        private TenancyAuditRecorder $audit,
    ) {}

    public function handle(
        TenantRequestContext $context,
        string $name,
        string $code,
        ActorContext $actor,
    ): Outlet {
        $this->ownerGuard->authorize($context);
        $input = $this->input->validate($name, $code);

        return DB::transaction(function () use ($context, $input, $actor): Outlet {
            if (Outlet::query()->where('tenant_id', $context->tenantId)->where('code', $input['code'])->exists()) {
                throw TenancyException::outletCodeUnavailable();
            }

            $outlet = Outlet::query()->create([
                'tenant_id' => $context->tenantId,
                'name' => $input['name'],
                'code' => $input['code'],
                'status' => OutletStatus::Active,
            ]);

            $this->audit->record(new TenancyAuditData(
                eventType: 'outlet.created',
                outcome: 'success',
                actorType: $actor->actorType,
                actorId: $actor->actorId,
                correlationId: $actor->correlationId,
                targetTenantId: $context->tenantId,
                metadata: ['outlet_id' => (string) $outlet->getKey()],
            ));

            return $outlet;
        });
    }
}
