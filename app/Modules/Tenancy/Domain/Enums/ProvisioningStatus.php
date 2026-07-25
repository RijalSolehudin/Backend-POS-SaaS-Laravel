<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Domain\Enums;

enum ProvisioningStatus: string
{
    case Processing = 'processing';
    case Succeeded = 'succeeded';
}
