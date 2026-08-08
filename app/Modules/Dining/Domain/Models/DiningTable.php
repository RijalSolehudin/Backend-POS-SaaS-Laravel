<?php

declare(strict_types=1);
namespace App\Modules\Dining\Domain\Models;

use App\Modules\Dining\Domain\Enums\TableStatus;
use Illuminate\Database\Eloquent\Model;
use App\Shared\Domain\Concerns\HasLowercaseUlids;

final class DiningTable extends Model
{
    use HasLowercaseUlids;
    
    protected $table = 'dining_tables';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'seats' => 'integer',
            'display_order' => 'integer',
            'status' => TableStatus::class,

        ];
    }
}