<?php

declare(strict_types=1);

namespace App\Modules\Kitchen\Domain\Models;

use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $outlet_id
 * @property string $ticket_id
 * @property string $order_item_id
 * @property string $product_id
 * @property string|null $variant_id
 * @property string $product_name
 * @property string|null $variant_name
 * @property string $quantity
 */
final class KitchenTicketItem extends Model
{
    use HasLowercaseUlids;

    protected $table = 'kitchen_ticket_items';

    protected $guarded = [];
}
