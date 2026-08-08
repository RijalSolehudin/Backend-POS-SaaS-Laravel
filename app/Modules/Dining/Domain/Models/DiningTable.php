<?php

declare(strict_types=1);

namespace App\Modules\Dining\Domain\Models;

use App\Modules\Dining\Domain\Enums\TableStatus;
use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $outlet_id
 * @property string $floor_id
 * @property string $name
 * @property string $code
 * @property int $capacity
 * @property int $display_order
 * @property TableStatus $status
 */
final class DiningTable extends Model
{
    use HasLowercaseUlids;

    protected $table = 'dining_tables';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'display_order' => 'integer',
            'status' => TableStatus::class,
        ];
    }
}
