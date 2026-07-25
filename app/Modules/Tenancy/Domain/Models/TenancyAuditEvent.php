<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Domain\Models;

use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

final class TenancyAuditEvent extends Model
{
    use HasLowercaseUlids;

    public const UPDATED_AT = null;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'occurred_at' => 'immutable_datetime',
        ];
    }
}
