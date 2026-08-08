<?php

declare(strict_types=1);

namespace App\Modules\Sync\Domain\Enums;

enum SyncConflictStatus: string
{
    case Open = 'open';
    case Resolved = 'resolved';
    case Dismissed = 'dismissed';
}
