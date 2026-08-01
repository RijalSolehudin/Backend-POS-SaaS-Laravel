<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Domain\Models;

use App\Modules\Tenancy\Domain\Enums\PosDeviceStatus;
use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $installation_id
 * @property string $tenant_id
 * @property string $outlet_id
 * @property string $name
 * @property string $client_type
 * @property string $platform
 * @property string|null $app_version
 * @property PosDeviceStatus $status
 * @property string $registered_by
 * @property CarbonImmutable|null $last_seen_at
 * @property CarbonImmutable|null $revoked_at
 * @property string|null $revoked_by
 * @property string|null $revoked_reason
 */
final class PosDevice extends Model
{
    use HasLowercaseUlids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => PosDeviceStatus::class,
            'last_seen_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
        ];
    }
}
