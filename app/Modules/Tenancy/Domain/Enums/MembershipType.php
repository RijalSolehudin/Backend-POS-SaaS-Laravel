<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Domain\Enums;

enum MembershipType: string
{
    case Owner = 'owner';
    case Member = 'member';
}
