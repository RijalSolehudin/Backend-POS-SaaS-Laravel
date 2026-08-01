<?php

declare(strict_types=1);

namespace App\Modules\Sales\Domain\Models;

use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $outlet_id
 * @property string $user_id
 * @property string $action
 * @property string $idempotency_key
 * @property string $request_hash
 * @property string|null $resource_type
 * @property string|null $resource_id
 * @property int|null $response_status
 * @property array<string, mixed>|null $response_body
 * @property CarbonImmutable $expires_at
 */
final class IdempotencyRecord extends Model
{
    use HasLowercaseUlids;

    protected $table = 'sales_idempotency_records';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'response_body' => 'array',
            'expires_at' => 'immutable_datetime',
        ];
    }
}
