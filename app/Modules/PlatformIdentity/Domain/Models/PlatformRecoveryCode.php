<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Domain\Models;

use App\Modules\PlatformIdentity\Domain\Concerns\HasLowercaseUlids;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $platform_user_id
 * @property string $code_hash
 * @property CarbonImmutable|null $used_at
 */
final class PlatformRecoveryCode extends Model
{
    use HasLowercaseUlids;

    public const UPDATED_AT = null;

    protected $table = 'platform_recovery_codes';

    protected $guarded = [];

    protected $hidden = ['code_hash'];

    protected function casts(): array
    {
        return [
            'used_at' => 'immutable_datetime',
        ];
    }
}
