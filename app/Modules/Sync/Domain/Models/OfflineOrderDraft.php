<?php

declare(strict_types=1);

namespace App\Modules\Sync\Domain\Models;

use App\Modules\Sync\Domain\Enums\OfflineOrderStatus;
use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $outlet_id
 * @property string $device_id
 * @property string $client_order_id
 * @property string|null $sales_order_id
 * @property OfflineOrderStatus $status
 * @property array<string, mixed>|null $draft_payload
 */
final class OfflineOrderDraft extends Model
{
    use HasLowercaseUlids;

    protected $table = 'offline_order_drafts';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => OfflineOrderStatus::class,
            'draft_payload' => 'array',
        ];
    }
}
