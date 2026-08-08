<?php
declare(strict_types=1);
namespace App\Modules\Dining\Domain\Models;

use Illuminate\Database\Eloquent\Model;

final class DiningFloor extends Model
{
    protected $table = 'dining_floors';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
        ];
    }
}
