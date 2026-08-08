<?php

declare(strict_types=1);

namespace App\Modules\OrderingChannel\Application\Actions;

use App\Modules\OrderingChannel\Domain\Models\OrderingCustomerCart;

final readonly class CreateCustomerCart
{
    public function __construct(private ResolveQrSession $sessions) {}

    public function handle(string $token, ?string $customerName = null, ?string $customerPhone = null): OrderingCustomerCart
    {
        $session = $this->sessions->handle($token);

        return OrderingCustomerCart::query()->create([
            'tenant_id' => $session->tenant_id,
            'outlet_id' => $session->outlet_id,
            'qr_session_id' => $session->id,
            'customer_name' => $customerName === null ? null : trim($customerName),
            'customer_phone' => $customerPhone === null ? null : trim($customerPhone),
        ]);
    }
}
