<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Application\Contracts;

use App\Modules\PlatformIdentity\Application\Data\SecurityAuditData;

interface SecurityAuditRecorder
{
    public function record(SecurityAuditData $data): string;
}
