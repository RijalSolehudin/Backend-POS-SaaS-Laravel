<?php

declare(strict_types=1);

use App\Modules\Identity\Domain\Models\User;
use App\Modules\Tenancy\Domain\Enums\MembershipType;
use App\Modules\Tenancy\Domain\Models\OutletUserAssignment;
use App\Modules\Tenancy\Domain\Models\TenantMembership;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('tenant.{tenantId}.outlet.{outletId}.kds', function (User $user, string $tenantId, string $outletId): bool {
    $membership = TenantMembership::query()
        ->where('tenant_id', $tenantId)
        ->where('user_id', $user->id)
        ->first();

    if (! $membership instanceof TenantMembership) {
        return false;
    }

    if ($membership->membership_type === MembershipType::Owner) {
        return true;
    }

    return OutletUserAssignment::query()
        ->where('tenant_id', $tenantId)
        ->where('outlet_id', $outletId)
        ->where('user_id', $user->id)
        ->exists();
}, ['guards' => ['web', 'sanctum']]);
