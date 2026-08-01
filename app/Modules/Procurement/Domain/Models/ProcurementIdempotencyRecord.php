<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Domain\Models;

use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $tenant_id
 * @property string $outlet_id
 * @property string $user_id
 * @property string $action
 * @property string $idempotency_key
 * @property string $request_hash
 * @property string|null $resource_type
 * @property string|null $resource_id
 */
final class ProcurementIdempotencyRecord extends Model
{
    use HasLowercaseUlids;

    protected $table = 'procurement_idempotency_records';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'response_status' => 'integer',
            'response_body' => 'array',
            'expires_at' => 'immutable_datetime',
        ];
    }
}
