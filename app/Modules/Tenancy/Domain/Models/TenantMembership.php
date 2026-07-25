<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Domain\Models;

use App\Modules\Tenancy\Domain\Enums\MembershipType;
use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

final class TenantMembership extends Model
{
    use HasLowercaseUlids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'membership_type' => MembershipType::class,
        ];
    }
}
