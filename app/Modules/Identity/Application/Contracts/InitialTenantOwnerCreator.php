<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Contracts;

use App\Modules\Identity\Application\Data\InitialTenantOwnerData;
use App\Modules\Identity\Application\Data\InitialTenantOwnerResult;

interface InitialTenantOwnerCreator
{
    public function handle(InitialTenantOwnerData $data): InitialTenantOwnerResult;
}
