<?php

declare(strict_types=1);

namespace App\Modules\OrderingChannel\Domain\Models;

use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $outlet_id
 * @property string $qr_session_id
 * @property string|null $customer_name
 * @property string|null $customer_phone
 */
final class OrderingCustomerCart extends Model
{
    use HasLowercaseUlids;

    protected $table = 'ordering_customer_carts';

    protected $guarded = [];
}
