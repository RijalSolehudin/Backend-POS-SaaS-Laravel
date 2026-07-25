<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Contracts;

use App\Modules\Tenancy\Application\Data\TenancyAuditData;

interface TenancyAuditRecorder
{
    public function record(TenancyAuditData $data): string;
}
