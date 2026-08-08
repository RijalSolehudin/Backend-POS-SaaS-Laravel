<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Domain\Models;

use App\Modules\Reporting\Domain\Enums\AnalyticsExportStatus;
use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

final class AnalyticsExport extends Model
{
    use HasLowercaseUlids;

    protected $table = 'analytics_exports';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'result' => 'array',
            'status' => AnalyticsExportStatus::class,
            'completed_at' => 'immutable_datetime',
        ];
    }
}
