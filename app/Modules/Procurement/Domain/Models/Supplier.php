<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Domain\Models;

use App\Modules\Procurement\Domain\Enums\SupplierStatus;
use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 * @property string $code
 * @property SupplierStatus $status
 */
final class Supplier extends Model
{
    use HasLowercaseUlids;

    protected $table = 'procurement_suppliers';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['status' => SupplierStatus::class];
    }
}
