<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Models;

use App\Modules\Identity\Domain\Enums\PredefinedRole;
use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

final class UserRoleAssignment extends Model
{
    use HasLowercaseUlids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'role' => PredefinedRole::class,
        ];
    }
}
