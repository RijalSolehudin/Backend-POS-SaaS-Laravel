<?php

declare(strict_types=1);

namespace App\Modules\Sync\Domain\Enums;

enum PerformanceBaselineStatus: string
{
    case Passed = 'passed';
    case Failed = 'failed';
}
