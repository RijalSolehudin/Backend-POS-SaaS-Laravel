<?php

declare(strict_types=1);

namespace App\Modules\PaymentsGateway\Domain\Models;

use App\Modules\PaymentsGateway\Domain\Enums\PaymentIntentStatus;
use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $outlet_id
 * @property string $sales_order_id
 * @property string $user_id
 * @property string $provider
 * @property string $provider_intent_id
 * @property PaymentIntentStatus $status
 * @property int $amount_minor
 * @property string $currency
 * @property array<string, mixed>|null $provider_payload
 */
final class PaymentGatewayIntent extends Model
{
    use HasLowercaseUlids;

    protected $table = 'payment_gateway_intents';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'status' => PaymentIntentStatus::class,
            'provider_payload' => 'array',
            'expires_at' => 'immutable_datetime',
            'paid_at' => 'immutable_datetime',
        ];
    }
}
