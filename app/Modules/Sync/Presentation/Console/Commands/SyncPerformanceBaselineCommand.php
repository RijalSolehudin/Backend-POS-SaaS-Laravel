<?php

declare(strict_types=1);

namespace App\Modules\Sync\Presentation\Console\Commands;

use App\Modules\Sync\Application\Actions\RecordPerformanceBaseline;
use App\Modules\Sync\Application\Exceptions\SyncException;
use Illuminate\Console\Command;

final class SyncPerformanceBaselineCommand extends Command
{
    protected $signature = 'sync:performance-baseline
        {baseline_type : Baseline name such as catalog_snapshot_p95}
        {measured_p95_ms : Measured p95 in milliseconds}
        {--target= : Override target p95 in milliseconds}
        {--fail-on-breach : Exit non-zero when measured p95 breaches target}
        {--json : Output result as JSON}';

    protected $description = 'Record offline sync performance baseline evidence.';

    public function handle(RecordPerformanceBaseline $baseline): int
    {
        $type = (string) $this->argument('baseline_type');
        $target = $this->option('target');
        $targetP95 = is_string($target) && $target !== ''
            ? (int) $target
            : (int) config("sync.targets.{$type}", 1000);
        $measured = (int) $this->argument('measured_p95_ms');

        try {
            $record = $baseline->handle($type, $targetP95, $measured, [
                'source' => 'sync:performance-baseline',
            ], (bool) $this->option('fail-on-breach'));
        } catch (SyncException $exception) {
            if ($this->option('json')) {
                $this->line((string) json_encode(['error_code' => $exception->errorCode()], JSON_THROW_ON_ERROR));
            } else {
                $this->error($exception->getMessage());
            }

            return self::FAILURE;
        }

        $payload = [
            'id' => $record->id,
            'baseline_type' => $record->baseline_type,
            'target_p95_ms' => $record->target_p95_ms,
            'measured_p95_ms' => $record->measured_p95_ms,
            'status' => $record->status->value,
        ];

        if ($this->option('json')) {
            $this->line((string) json_encode($payload, JSON_THROW_ON_ERROR));
        } else {
            $this->info(sprintf('%s baseline %s (%d/%d ms).', $record->baseline_type, $record->status->value, $record->measured_p95_ms, $record->target_p95_ms));
        }

        return $record->status->value === 'passed' ? self::SUCCESS : self::FAILURE;
    }
}
