<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Domain\Models;

use App\Modules\PlatformIdentity\Domain\Enums\PlatformUserStatus;
use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property string $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property PlatformUserStatus $status
 * @property string|null $totp_secret
 * @property int|null $totp_last_used_step
 */
final class PlatformUser extends Authenticatable
{
    use HasLowercaseUlids;
    use Notifiable;

    protected $table = 'platform_users';

    protected $guarded = [];

    protected $hidden = [
        'password',
        'totp_secret',
        'totp_last_used_step',
    ];

    protected function casts(): array
    {
        return [
            'status' => PlatformUserStatus::class,
            'password' => 'hashed',
            'totp_secret' => 'encrypted',
            'totp_confirmed_at' => 'immutable_datetime',
            'password_changed_at' => 'immutable_datetime',
            'last_login_at' => 'immutable_datetime',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === PlatformUserStatus::Active;
    }

    public function requiresMfaSetup(): bool
    {
        return $this->status === PlatformUserStatus::PendingMfaSetup;
    }
}
