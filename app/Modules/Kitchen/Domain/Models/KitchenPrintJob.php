<?php

declare(strict_types=1);

namespace App\Modules\Kitchen\Domain\Models;

use App\Modules\Kitchen\Domain\Enums\PrintJobStatus;
use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $outlet_id
 * @property string $ticket_id
 * @property string|null $source_print_job_id
 * @property PrintJobStatus $status
 * @property string $job_type
 * @property string|null $reason
 * @property string|null $error_message
 * @property string $requested_by
 * @property CarbonImmutable|null $sent_at
 */
final class KitchenPrintJob extends Model
{
    use HasLowercaseUlids;

    protected $table = 'kitchen_print_jobs';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => PrintJobStatus::class,
            'sent_at' => 'immutable_datetime',
            'payload' => 'array',
        ];
    }
}
