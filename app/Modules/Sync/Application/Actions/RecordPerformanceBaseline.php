<?php

declare(strict_types=1);

namespace App\Modules\Sync\Application\Actions;

use App\Modules\Sync\Application\Exceptions\SyncException;
use App\Modules\Sync\Domain\Enums\PerformanceBaselineStatus;
use App\Modules\Sync\Domain\Models\PerformanceBaseline;
use Carbon\CarbonImmutable;

final readonly class RecordPerformanceBaseline
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function handle(
        string $baselineType,
        int $targetP95Ms,
        int $measuredP95Ms,
        ?array $metadata = null,
        bool $failOnBreach = false,
    ): PerformanceBaseline {
        $baseline = PerformanceBaseline::query()->create([
            'baseline_type' => $baselineType,
            'target_p95_ms' => $targetP95Ms,
            'measured_p95_ms' => $measuredP95Ms,
            'status' => $measuredP95Ms <= $targetP95Ms ? PerformanceBaselineStatus::Passed : PerformanceBaselineStatus::Failed,
            'metadata' => $metadata,
            'measured_at' => CarbonImmutable::now(),
        ]);

        if ($failOnBreach && $baseline->status === PerformanceBaselineStatus::Failed) {
            throw SyncException::performanceBaselineFailed();
        }

        return $baseline;
    }
}
