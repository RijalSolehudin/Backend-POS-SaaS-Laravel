<?php

declare(strict_types=1);

namespace App\Modules\Sync\Domain\Models;

use App\Modules\Sync\Domain\Enums\PerformanceBaselineStatus;
use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $baseline_type
 * @property int $target_p95_ms
 * @property int $measured_p95_ms
 * @property PerformanceBaselineStatus $status
 * @property array<string, mixed>|null $metadata
 * @property CarbonImmutable $measured_at
 */
final class PerformanceBaseline extends Model
{
    use HasLowercaseUlids;

    protected $table = 'performance_baselines';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'target_p95_ms' => 'integer',
            'measured_p95_ms' => 'integer',
            'status' => PerformanceBaselineStatus::class,
            'metadata' => 'array',
            'measured_at' => 'immutable_datetime',
        ];
    }
}
