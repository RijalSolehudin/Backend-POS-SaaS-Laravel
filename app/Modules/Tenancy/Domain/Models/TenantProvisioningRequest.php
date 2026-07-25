<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Domain\Models;

use App\Modules\Tenancy\Domain\Enums\ProvisioningStatus;
use App\Shared\Domain\Concerns\HasLowercaseUlids;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $idempotency_key
 * @property string $input_hash
 * @property ProvisioningStatus $status
 * @property string $correlation_id
 * @property string|null $tenant_id
 * @property string|null $outlet_id
 * @property string|null $owner_user_id
 * @property string|null $membership_id
 * @property string|null $role_assignment_id
 * @property DateTimeImmutable|null $completed_at
 */
final class TenantProvisioningRequest extends Model
{
    use HasLowercaseUlids;

    protected $guarded = [];

    protected $hidden = [
        'input_hash',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProvisioningStatus::class,
            'completed_at' => 'immutable_datetime',
        ];
    }
}
