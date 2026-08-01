<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Services;

use App\Modules\Identity\Application\Services\PredefinedTenantRolePolicy;

final readonly class TenantRoleCatalog
{
    public function __construct(private PredefinedTenantRolePolicy $policy) {}

    /**
     * @return list<array{value: string, label: string}>
     */
    public function options(): array
    {
        return array_map(
            fn (string $role): array => [
                'value' => $role,
                'label' => match ($role) {
                    'tenant_owner' => 'Tenant Owner',
                    'outlet_manager' => 'Outlet Manager',
                    'cashier' => 'Cashier',
                    default => $role,
                },
            ],
            $this->policy->roles(),
        );
    }
}
