<?php

declare(strict_types=1);

namespace App\Modules\OrderingChannel\Domain\Models;

use App\Modules\OrderingChannel\Domain\Enums\QrSessionStatus;
use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $outlet_id
 * @property string|null $table_id
 * @property string $context_type
 * @property QrSessionStatus $status
 * @property CarbonImmutable $expires_at
 */
final class OrderingQrSession extends Model
{
    use HasLowercaseUlids;

    protected $table = 'ordering_qr_sessions';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => QrSessionStatus::class,
            'expires_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
        ];
    }
}
