<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Application\Actions;

use App\Modules\Inventory\Application\Actions\RecordStockMovement;
use App\Modules\Inventory\Application\Data\StockMovementInput;
use App\Modules\Inventory\Application\Services\DecimalQuantity;
use App\Modules\Inventory\Domain\Enums\StockMovementType;
use App\Modules\Procurement\Application\Data\GoodsReceiptInput;
use App\Modules\Procurement\Application\Exceptions\ProcurementException;
use App\Modules\Procurement\Application\Services\ProcurementIdempotencyStore;
use App\Modules\Procurement\Domain\Enums\GoodsReceiptStatus;
use App\Modules\Procurement\Domain\Enums\PurchaseOrderStatus;
use App\Modules\Procurement\Domain\Models\GoodsReceipt;
use App\Modules\Procurement\Domain\Models\GoodsReceiptLine;
use App\Modules\Procurement\Domain\Models\ProcurementIdempotencyRecord;
use App\Modules\Procurement\Domain\Models\PurchaseOrder;
use App\Modules\Procurement\Domain\Models\PurchaseOrderLine;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;
use Illuminate\Support\Facades\DB;

final readonly class RecordGoodsReceipt
{
    public function __construct(
        private TenantPermissionGuard $permissions,
        private ProcurementIdempotencyStore $idempotency,
        private RecordStockMovement $movements,
        private DecimalQuantity $quantity,
    ) {}

    public function handle(TenantRequestContext $context, GoodsReceiptInput $input, string $idempotencyKey): GoodsReceipt
    {
        if (trim($idempotencyKey) === '') {
            throw ProcurementException::idempotencyKeyRequired();
        }

        if ($input->lines === []) {
            throw ProcurementException::receiptOverReceived();
        }

        $this->permissions->authorizeManageCatalog($context);
        $requestHash = hash('sha256', json_encode([
            'purchase_order_id' => $input->purchaseOrderId,
            'lines' => array_map(fn ($line): array => [
                'purchase_order_line_id' => $line->purchaseOrderLineId,
                'quantity' => $line->quantity,
            ], $input->lines),
        ], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($context, $input, $idempotencyKey, $requestHash): GoodsReceipt {
            $po = $this->poForUpdate($context, $input->purchaseOrderId);
            $record = $this->idempotency->findForUpdate($context->tenantId, $po->outlet_id, $context->userId, 'procurement.goods_receipts.record', $idempotencyKey);

            if ($record instanceof ProcurementIdempotencyRecord) {
                if ($record->request_hash !== $requestHash || $record->resource_id === null) {
                    throw ProcurementException::idempotencyConflict();
                }

                $receipt = GoodsReceipt::query()->where('tenant_id', $context->tenantId)->whereKey($record->resource_id)->first();

                if (! $receipt instanceof GoodsReceipt) {
                    throw ProcurementException::idempotencyConflict();
                }

                return $receipt;
            }

            if (! in_array($po->status, [PurchaseOrderStatus::Approved, PurchaseOrderStatus::Ordered, PurchaseOrderStatus::PartiallyReceived], true)) {
                throw ProcurementException::poApprovalRequired();
            }

            $receipt = GoodsReceipt::query()->create([
                'tenant_id' => $context->tenantId,
                'outlet_id' => $po->outlet_id,
                'purchase_order_id' => $po->id,
                'receipt_number' => $this->receiptNumber(),
                'status' => GoodsReceiptStatus::Recorded,
                'received_by_user_id' => $context->userId,
                'received_at' => now(),
            ]);

            foreach ($input->lines as $index => $lineInput) {
                $line = $this->poLineForUpdate($context, $po, $lineInput->purchaseOrderLineId);
                $received = $this->quantity->normalize($lineInput->quantity);
                $receivedScaled = $this->quantity->toScaled($received);
                $remainingScaled = $this->quantity->toScaled((string) $line->quantity) - $this->quantity->toScaled((string) $line->received_quantity);

                if ($receivedScaled <= 0 || $receivedScaled > $remainingScaled) {
                    throw ProcurementException::receiptOverReceived();
                }

                $totalCostMinor = intdiv(($receivedScaled * $line->unit_price_minor) + 500, 1000);

                $this->movements->handle(new StockMovementInput(
                    tenantId: $context->tenantId,
                    outletId: $po->outlet_id,
                    itemId: $line->inventory_item_id,
                    unitId: $line->unit_id,
                    actorUserId: $context->userId,
                    movementType: StockMovementType::PurchaseReceipt,
                    sourceType: 'procurement_goods_receipt',
                    sourceId: $receipt->id,
                    quantity: $received,
                    unitCostMinor: $line->unit_price_minor,
                    totalCostMinor: $totalCostMinor,
                    currency: $po->currency,
                    reason: 'Purchase order goods receipt',
                    idempotencyKey: $this->movementKey($idempotencyKey, $line->id, $index),
                ));

                GoodsReceiptLine::query()->create([
                    'tenant_id' => $context->tenantId,
                    'goods_receipt_id' => $receipt->id,
                    'purchase_order_line_id' => $line->id,
                    'inventory_item_id' => $line->inventory_item_id,
                    'unit_id' => $line->unit_id,
                    'quantity' => $received,
                    'returned_quantity' => '0.000',
                    'unit_cost_minor' => $line->unit_price_minor,
                    'total_cost_minor' => $totalCostMinor,
                ]);

                $line->forceFill([
                    'received_quantity' => $this->quantity->format($this->quantity->toScaled((string) $line->received_quantity) + $receivedScaled),
                ])->save();
            }

            $po->forceFill(['status' => $this->receivedStatus($po)])->save();
            $this->idempotency->create($context->tenantId, $po->outlet_id, $context->userId, 'procurement.goods_receipts.record', $idempotencyKey, $requestHash, 'goods_receipt', $receipt->id);

            return $receipt->refresh();
        });
    }

    private function poForUpdate(TenantRequestContext $context, string $purchaseOrderId): PurchaseOrder
    {
        $po = PurchaseOrder::query()->where('tenant_id', $context->tenantId)->whereKey($purchaseOrderId)->lockForUpdate()->first();

        if (! $po instanceof PurchaseOrder) {
            throw ProcurementException::poNotFound();
        }

        return $po;
    }

    private function poLineForUpdate(TenantRequestContext $context, PurchaseOrder $po, string $lineId): PurchaseOrderLine
    {
        $line = PurchaseOrderLine::query()
            ->where('tenant_id', $context->tenantId)
            ->where('purchase_order_id', $po->id)
            ->whereKey($lineId)
            ->lockForUpdate()
            ->first();

        if (! $line instanceof PurchaseOrderLine) {
            throw ProcurementException::supplierItemNotFound();
        }

        return $line;
    }

    private function receivedStatus(PurchaseOrder $po): PurchaseOrderStatus
    {
        $hasOpenLine = PurchaseOrderLine::query()
            ->where('tenant_id', $po->tenant_id)
            ->where('purchase_order_id', $po->id)
            ->whereColumn('received_quantity', '<', 'quantity')
            ->exists();

        return $hasOpenLine ? PurchaseOrderStatus::PartiallyReceived : PurchaseOrderStatus::Received;
    }

    private function receiptNumber(): string
    {
        return 'GR-'.now()->format('YmdHis').'-'.strtolower(str()->random(6));
    }

    private function movementKey(string $idempotencyKey, string $lineId, int $index): string
    {
        return 'gr:'.hash('xxh3', $idempotencyKey.'|'.$lineId.'|'.$index);
    }
}
