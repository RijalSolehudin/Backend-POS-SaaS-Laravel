<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Domain\Models;

use App\Modules\Tenancy\Domain\Enums\OutletStatus;
use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

final class Outlet extends Model
{
    use HasLowercaseUlids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => OutletStatus::class,
        ];
    }
}
