<?php

declare(strict_types=1);

namespace App\Modules\PaymentsGateway\Domain\Models;

use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $provider
 * @property string $provider_event_id
 * @property string $event_type
 * @property array<string, mixed>|null $payload
 */
final class PaymentGatewayWebhookEvent extends Model
{
    use HasLowercaseUlids;

    protected $table = 'payment_gateway_webhook_events';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'immutable_datetime',
        ];
    }
}
