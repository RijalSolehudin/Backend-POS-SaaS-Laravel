<?php

declare(strict_types=1);

namespace App\Modules\Kitchen\Domain\Enums;

enum PrintJobStatus: string
{
    case Queued = 'queued';
    case Sent = 'sent';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}
