<?php

declare(strict_types=1);

namespace App\Modules\Sales\Domain\Enums;

enum RefundStatus: string
{
    case Recorded = 'recorded';
}
