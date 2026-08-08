<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Domain\Enums;

enum AnalyticsExportStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';
}
