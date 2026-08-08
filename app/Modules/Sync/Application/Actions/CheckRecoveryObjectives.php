<?php

declare(strict_types=1);

namespace App\Modules\Sync\Application\Actions;

use App\Modules\Sync\Application\Exceptions\SyncException;

final readonly class CheckRecoveryObjectives
{
    public function __construct(private RecordPerformanceBaseline $baselines) {}

    /**
     * @param  array<string, mixed>  $metadata
     * @return array{rpo_passed: bool, rto_passed: bool}
     */
    public function handle(
        int $targetRpoSeconds,
        int $measuredRpoSeconds,
        int $targetRtoSeconds,
        int $measuredRtoSeconds,
        array $metadata = [],
        bool $failOnBreach = false,
    ): array {
        $rpo = $this->baselines->handle('sync_rpo_seconds', $targetRpoSeconds, $measuredRpoSeconds, $metadata);
        $rto = $this->baselines->handle('sync_rto_seconds', $targetRtoSeconds, $measuredRtoSeconds, $metadata);
        $passed = $measuredRpoSeconds <= $targetRpoSeconds && $measuredRtoSeconds <= $targetRtoSeconds;

        if ($failOnBreach && ! $passed) {
            throw SyncException::recoveryObjectiveFailed();
        }

        return [
            'rpo_passed' => $rpo->measured_p95_ms <= $rpo->target_p95_ms,
            'rto_passed' => $rto->measured_p95_ms <= $rto->target_p95_ms,
        ];
    }
}
