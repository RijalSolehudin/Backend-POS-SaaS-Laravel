<?php

declare(strict_types=1);

namespace App\Modules\Dining\Domain\Models;

use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $outlet_id
 * @property string $table_session_id
 * @property string $order_id
 */
final class DiningTableSessionOrder extends Model
{
    use HasLowercaseUlids;

    protected $table = 'dining_table_session_orders';

    protected $guarded = [];
}
