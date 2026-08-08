<?php

declare(strict_types=1);

namespace App\Modules\Sync\Domain\Enums;

enum OfflineOrderStatus: string
{
    case LocalDraft = 'local_draft';
    case Queued = 'queued';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Conflict = 'conflict';
}
