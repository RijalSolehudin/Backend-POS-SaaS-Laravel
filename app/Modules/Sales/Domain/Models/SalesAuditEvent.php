<?php

declare(strict_types=1);

namespace App\Modules\Sales\Domain\Models;

use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string|null $outlet_id
 * @property string|null $actor_user_id
 * @property string $event_type
 * @property string|null $target_type
 * @property string|null $target_id
 * @property string|null $outcome
 * @property string|null $reason
 * @property string $correlation_id
 * @property array<string, mixed>|null $metadata
 * @property CarbonImmutable $occurred_at
 */
final class SalesAuditEvent extends Model
{
    use HasLowercaseUlids;

    public const UPDATED_AT = null;

    protected $table = 'sales_audit_events';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'occurred_at' => 'immutable_datetime',
        ];
    }
}
