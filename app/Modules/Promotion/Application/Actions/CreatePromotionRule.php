<?php

declare(strict_types=1);

namespace App\Modules\Promotion\Application\Actions;

use App\Modules\Promotion\Application\Data\PromotionRuleInput;
use App\Modules\Promotion\Domain\Enums\PromotionStatus;
use App\Modules\Promotion\Domain\Models\PromotionRule;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;

final readonly class CreatePromotionRule
{
    public function __construct(private TenantPermissionGuard $permissions) {}

    public function handle(TenantRequestContext $context, PromotionRuleInput $input): PromotionRule
    {
        $this->permissions->authorizeManageCatalog($context);

        return PromotionRule::query()->create([
            'tenant_id' => $context->tenantId,
            'outlet_id' => $input->outletId,
            'name' => trim($input->name),
            'code' => mb_strtoupper(trim($input->code)),
            'discount_type' => $input->discountType,
            'discount_value' => $input->discountValue,
            'status' => PromotionStatus::Active,
        ]);
    }
}
