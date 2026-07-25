<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Domain\Models;

use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

final class PlatformSecurityAuditEvent extends Model
{
    use HasLowercaseUlids;

    public const UPDATED_AT = null;

    protected $table = 'platform_security_audit_events';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'occurred_at' => 'immutable_datetime',
        ];
    }
}
