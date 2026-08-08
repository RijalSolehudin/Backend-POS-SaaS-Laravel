<?php

declare(strict_types=1);

namespace App\Modules\Kitchen\Domain\Models;

use App\Modules\Kitchen\Domain\Enums\KitchenStatus;
use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $outlet_id
 * @property string $name
 * @property string $code
 * @property bool $is_fallback
 * @property KitchenStatus $status
 */
final class KitchenStation extends Model
{
    use HasLowercaseUlids;

    protected $table = 'kitchen_stations';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_fallback' => 'boolean',
            'status' => KitchenStatus::class,
        ];
    }
}
