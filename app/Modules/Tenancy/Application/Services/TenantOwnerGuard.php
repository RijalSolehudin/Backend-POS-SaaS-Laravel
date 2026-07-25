<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Services;

use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Exceptions\TenancyException;

final class TenantOwnerGuard
{
    public function authorize(TenantRequestContext $context): void
    {
        if (! $context->isOwner()) {
            throw TenancyException::forbidden();
        }
    }
}
