<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Models;

use App\Modules\Identity\Domain\Enums\UserStatus;
use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property string $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property UserStatus $status
 * @property bool $must_change_password
 */
#[UseFactory(UserFactory::class)]
final class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasLowercaseUlids;
    use Notifiable;

    protected $guarded = [];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'status' => UserStatus::class,
            'email_verified_at' => 'immutable_datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'password_changed_at' => 'immutable_datetime',
        ];
    }
}
