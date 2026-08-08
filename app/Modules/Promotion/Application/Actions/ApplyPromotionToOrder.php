<?php

declare(strict_types=1);

namespace App\Modules\Promotion\Application\Actions;

use App\Modules\Promotion\Application\Exceptions\PromotionException;
use App\Modules\Promotion\Domain\Enums\PromotionDiscountType;
use App\Modules\Promotion\Domain\Enums\PromotionStatus;
use App\Modules\Promotion\Domain\Models\PromotionRule;
use App\Modules\Promotion\Domain\Models\SalesOrderDiscount;
use App\Modules\Sales\Domain\Enums\OrderStatus;
use App\Modules\Sales\Domain\Models\Order;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;
use Illuminate\Support\Facades\DB;

final readonly class ApplyPromotionToOrder
{
    public function __construct(private TenantPermissionGuard $permissions) {}

    public function handle(TenantRequestContext $context, string $outletId, string $orderId, string $promotionId, string $source = 'staff', ?string $reason = null): SalesOrderDiscount
    {
        $this->permissions->authorizeOperatePos($context, $outletId);

        return DB::transaction(function () use ($context, $outletId, $orderId, $promotionId, $source, $reason): SalesOrderDiscount {
            $order = Order::query()
                ->where('tenant_id', $context->tenantId)
                ->where('outlet_id', $outletId)
                ->whereKey($orderId)
                ->lockForUpdate()
                ->first();

            if (! $order instanceof Order || $order->status !== OrderStatus::Draft) {
                throw PromotionException::invalid();
            }

            if (SalesOrderDiscount::query()->where('tenant_id', $context->tenantId)->where('sales_order_id', $orderId)->exists()) {
                throw PromotionException::invalid();
            }

            $promotion = PromotionRule::query()
                ->where('tenant_id', $context->tenantId)
                ->where(function ($query) use ($outletId): void {
                    $query->whereNull('outlet_id')->orWhere('outlet_id', $outletId);
                })
                ->where('status', PromotionStatus::Active)
                ->whereKey($promotionId)
                ->first();

            if (! $promotion instanceof PromotionRule) {
                throw PromotionException::notFound();
            }

            $amount = $this->discountAmount($promotion, $order->subtotal_minor);

            $discount = SalesOrderDiscount::query()->create([
                'tenant_id' => $context->tenantId,
                'outlet_id' => $outletId,
                'sales_order_id' => $order->id,
                'promotion_rule_id' => $promotion->id,
                'promotion_name' => $promotion->name,
                'promotion_type' => $promotion->discount_type->value,
                'promotion_value' => $promotion->discount_value,
                'discount_amount_minor' => $amount,
                'source' => $source,
                'reason' => $reason,
            ]);

            $order->forceFill([
                'discount_minor' => $amount,
                'total_minor' => max(0, $order->subtotal_minor - $amount),
            ])->save();

            return $discount;
        });
    }

    private function discountAmount(PromotionRule $promotion, int $subtotalMinor): int
    {
        if ($promotion->discount_type === PromotionDiscountType::Fixed) {
            return min($promotion->discount_value, $subtotalMinor);
        }

        return min($subtotalMinor, (int) floor($subtotalMinor * $promotion->discount_value / 10_000));
    }
}
