<?php

declare(strict_types=1);

namespace App\Modules\Kitchen\Application\Actions;

use App\Modules\Kitchen\Application\Data\KitchenRoutingRuleInput;
use App\Modules\Kitchen\Application\Exceptions\KitchenException;
use App\Modules\Kitchen\Domain\Enums\KitchenStatus;
use App\Modules\Kitchen\Domain\Models\KitchenRoutingRule;
use App\Modules\Kitchen\Domain\Models\KitchenStation;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;

final readonly class CreateKitchenRoutingRule
{
    public function __construct(private TenantPermissionGuard $permissions) {}

    public function handle(TenantRequestContext $context, KitchenRoutingRuleInput $input): KitchenRoutingRule
    {
        $this->permissions->authorizeManageCatalog($context);

        $station = KitchenStation::query()
            ->where('tenant_id', $context->tenantId)
            ->where('outlet_id', $input->outletId)
            ->where('status', KitchenStatus::Active)
            ->whereKey($input->stationId)
            ->first();

        if (! $station instanceof KitchenStation) {
            throw KitchenException::stationNotFound();
        }

        return KitchenRoutingRule::query()->updateOrCreate(
            [
                'tenant_id' => $context->tenantId,
                'outlet_id' => $input->outletId,
                'rule_type' => $input->ruleType,
                'catalog_reference_id' => $input->catalogReferenceId,
            ],
            [
                'station_id' => $station->id,
                'priority' => $input->priority,
                'status' => KitchenStatus::Active,
            ],
        );
    }
}
