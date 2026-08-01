<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Application\Actions;

use App\Modules\Inventory\Application\Actions\RecordStockMovement;
use App\Modules\Inventory\Application\Data\StockMovementInput;
use App\Modules\Inventory\Application\Services\DecimalQuantity;
use App\Modules\Inventory\Domain\Enums\StockMovementType;
use App\Modules\Procurement\Application\Data\PurchaseReturnInput;
use App\Modules\Procurement\Application\Exceptions\ProcurementException;
use App\Modules\Procurement\Application\Services\ProcurementIdempotencyStore;
use App\Modules\Procurement\Domain\Enums\GoodsReceiptStatus;
use App\Modules\Procurement\Domain\Enums\PurchaseReturnStatus;
use App\Modules\Procurement\Domain\Models\GoodsReceipt;
use App\Modules\Procurement\Domain\Models\GoodsReceiptLine;
use App\Modules\Procurement\Domain\Models\ProcurementIdempotencyRecord;
use App\Modules\Procurement\Domain\Models\PurchaseOrder;
use App\Modules\Procurement\Domain\Models\PurchaseReturn;
use App\Modules\Procurement\Domain\Models\PurchaseReturnLine;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;
use Illuminate\Support\Facades\DB;

final readonly class RecordPurchaseReturn
{
    public function __construct(
        private TenantPermissionGuard $permissions,
        private ProcurementIdempotencyStore $idempotency,
        private RecordStockMovement $movements,
        private DecimalQuantity $quantity,
    ) {}

    public function handle(TenantRequestContext $context, PurchaseReturnInput $input, string $idempotencyKey): PurchaseReturn
    {
        if (trim($idempotencyKey) === '') {
            throw ProcurementException::idempotencyKeyRequired();
        }

        $reason = trim($input->reason);

        if ($reason === '' || $input->lines === []) {
            throw ProcurementException::reasonRequired();
        }

        $this->permissions->authorizeManageCatalog($context);
        $requestHash = hash('sha256', json_encode([
            'goods_receipt_id' => $input->goodsReceiptId,
            'reason' => $reason,
            'lines' => array_map(fn ($line): array => [
                'goods_receipt_line_id' => $line->goodsReceiptLineId,
                'quantity' => $line->quantity,
            ], $input->lines),
        ], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($context, $input, $idempotencyKey, $reason, $requestHash): PurchaseReturn {
            $receipt = $this->receiptForUpdate($context, $input->goodsReceiptId);
            $po = PurchaseOrder::query()->where('tenant_id', $context->tenantId)->whereKey($receipt->purchase_order_id)->firstOrFail();
            $record = $this->idempotency->findForUpdate($context->tenantId, $receipt->outlet_id, $context->userId, 'procurement.purchase_returns.record', $idempotencyKey);

            if ($record instanceof ProcurementIdempotencyRecord) {
                if ($record->request_hash !== $requestHash || $record->resource_id === null) {
                    throw ProcurementException::idempotencyConflict();
                }

                $return = PurchaseReturn::query()->where('tenant_id', $context->tenantId)->whereKey($record->resource_id)->first();

                if (! $return instanceof PurchaseReturn) {
                    throw ProcurementException::idempotencyConflict();
                }

                return $return;
            }

            if ($receipt->status !== GoodsReceiptStatus::Recorded) {
                throw ProcurementException::poInvalidState();
            }

            $return = PurchaseReturn::query()->create([
                'tenant_id' => $context->tenantId,
                'outlet_id' => $receipt->outlet_id,
                'goods_receipt_id' => $receipt->id,
                'return_number' => $this->returnNumber(),
                'status' => PurchaseReturnStatus::Recorded,
                'returned_by_user_id' => $context->userId,
                'reason' => $reason,
                'returned_at' => now(),
            ]);

            foreach ($input->lines as $index => $lineInput) {
                $line = $this->receiptLineForUpdate($context, $receipt, $lineInput->goodsReceiptLineId);
                $returnQuantity = $this->quantity->normalize($lineInput->quantity);
                $returnScaled = $this->quantity->toScaled($returnQuantity);
                $remainingScaled = $this->quantity->toScaled((string) $line->quantity) - $this->quantity->toScaled((string) $line->returned_quantity);

                if ($returnScaled <= 0 || $returnScaled > $remainingScaled) {
                    throw ProcurementException::returnQuantityInvalid();
                }

                $this->movements->handle(new StockMovementInput(
                    tenantId: $context->tenantId,
                    outletId: $receipt->outlet_id,
                    itemId: $line->inventory_item_id,
                    unitId: $line->unit_id,
                    actorUserId: $context->userId,
                    movementType: StockMovementType::PurchaseReturn,
                    sourceType: 'procurement_purchase_return',
                    sourceId: $return->id,
                    quantity: '-'.$returnQuantity,
                    unitCostMinor: null,
                    totalCostMinor: null,
                    currency: $po->currency,
                    reason: $reason,
                    idempotencyKey: $this->movementKey($idempotencyKey, $line->id, $index),
                ));

                PurchaseReturnLine::query()->create([
                    'tenant_id' => $context->tenantId,
                    'purchase_return_id' => $return->id,
                    'goods_receipt_line_id' => $line->id,
                    'inventory_item_id' => $line->inventory_item_id,
                    'unit_id' => $line->unit_id,
                    'quantity' => $returnQuantity,
                ]);

                $line->forceFill([
                    'returned_quantity' => $this->quantity->format($this->quantity->toScaled((string) $line->returned_quantity) + $returnScaled),
                ])->save();
            }

            $this->idempotency->create($context->tenantId, $receipt->outlet_id, $context->userId, 'procurement.purchase_returns.record', $idempotencyKey, $requestHash, 'purchase_return', $return->id);

            return $return->refresh();
        });
    }

    private function receiptForUpdate(TenantRequestContext $context, string $goodsReceiptId): GoodsReceipt
    {
        $receipt = GoodsReceipt::query()->where('tenant_id', $context->tenantId)->whereKey($goodsReceiptId)->lockForUpdate()->first();

        if (! $receipt instanceof GoodsReceipt) {
            throw ProcurementException::poNotFound();
        }

        return $receipt;
    }

    private function receiptLineForUpdate(TenantRequestContext $context, GoodsReceipt $receipt, string $lineId): GoodsReceiptLine
    {
        $line = GoodsReceiptLine::query()
            ->where('tenant_id', $context->tenantId)
            ->where('goods_receipt_id', $receipt->id)
            ->whereKey($lineId)
            ->lockForUpdate()
            ->first();

        if (! $line instanceof GoodsReceiptLine) {
            throw ProcurementException::returnQuantityInvalid();
        }

        return $line;
    }

    private function returnNumber(): string
    {
        return 'PR-'.now()->format('YmdHis').'-'.strtolower(str()->random(6));
    }

    private function movementKey(string $idempotencyKey, string $lineId, int $index): string
    {
        return 'pr:'.hash('xxh3', $idempotencyKey.'|'.$lineId.'|'.$index);
    }
}
