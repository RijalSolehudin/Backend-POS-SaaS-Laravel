<?php

declare(strict_types=1);

namespace App\Modules\Sales\Application\Actions;

use App\Modules\Sales\Application\Exceptions\OrderException;
use App\Modules\Sales\Domain\Models\Receipt;
use App\Modules\Tenancy\Application\Data\PosOutletContext;

final readonly class GetOrderReceipt
{
    public function handle(PosOutletContext $context, string $orderId): Receipt
    {
        $receipt = Receipt::query()
            ->where('tenant_id', $context->tenantId)
            ->where('outlet_id', $context->outletId)
            ->where('order_id', $orderId)
            ->first();

        if (! $receipt instanceof Receipt) {
            throw OrderException::receiptNotFound();
        }

        return $receipt;
    }
}
