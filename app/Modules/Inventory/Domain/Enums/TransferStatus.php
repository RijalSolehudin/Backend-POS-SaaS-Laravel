<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Domain\Enums;

enum TransferStatus: string
{
    case Draft = 'draft';
    case Requested = 'requested';
    case Approved = 'approved';
    case Dispatched = 'dispatched';
    case Received = 'received';
    case Cancelled = 'cancelled';
}
