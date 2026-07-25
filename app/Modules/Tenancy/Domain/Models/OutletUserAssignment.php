<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Domain\Models;

use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $tenant_id
 * @property string $outlet_id
 * @property string $user_id
 */
final class OutletUserAssignment extends Model
{
    use HasLowercaseUlids;

    protected $guarded = [];
}
