<?php

declare(strict_types=1);

namespace App\Modules\Sync\Domain\Enums;

enum SyncRecordStatus: string
{
    case Accepted = 'accepted';
    case Duplicate = 'duplicate';
    case Rejected = 'rejected';
    case Conflict = 'conflict';
}
