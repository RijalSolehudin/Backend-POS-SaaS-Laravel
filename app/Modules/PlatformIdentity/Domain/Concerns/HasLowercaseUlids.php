<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Domain\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Support\Str;

trait HasLowercaseUlids
{
    use HasUlids;

    public function newUniqueId(): string
    {
        return strtolower((string) Str::ulid());
    }
}
