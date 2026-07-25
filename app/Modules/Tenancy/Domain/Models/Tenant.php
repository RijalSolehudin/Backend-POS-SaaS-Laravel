<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Domain\Models;

use App\Modules\Tenancy\Domain\Enums\TenantStatus;
use App\Shared\Domain\Concerns\HasLowercaseUlids;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $name
 * @property string $code
 * @property string $currency
 * @property string $timezone
 * @property TenantStatus $status
 * @property DateTimeImmutable|null $disabled_at
 * @property string|null $disabled_reason
 */
final class Tenant extends Model
{
    use HasLowercaseUlids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => TenantStatus::class,
            'disabled_at' => 'immutable_datetime',
        ];
    }
}
