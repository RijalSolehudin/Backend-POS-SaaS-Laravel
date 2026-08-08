<?php

declare(strict_types=1);

namespace App\Modules\Sync\Application\Actions;

use App\Modules\Sales\Application\Actions\AddOrderItem;
use App\Modules\Sales\Application\Actions\CompleteOrderWithPayment;
use App\Modules\Sales\Application\Actions\CreateDraftOrder;
use App\Modules\Sales\Application\Data\OrderItemSelection;
use App\Modules\Sales\Domain\Enums\PaymentMethod;
use App\Modules\Sync\Application\Data\SyncMutationInput;
use App\Modules\Sync\Application\Data\SyncMutationResult;
use App\Modules\Sync\Application\Exceptions\SyncException;
use App\Modules\Sync\Domain\Enums\OfflineOrderStatus;
use App\Modules\Sync\Domain\Enums\SyncRecordStatus;
use App\Modules\Sync\Domain\Models\OfflineOrderDraft;
use App\Modules\Sync\Domain\Models\OfflineOrderEvent;
use App\Modules\Sync\Domain\Models\SyncDeviceState;
use App\Modules\Sync\Domain\Models\SyncInboxRecord;
use App\Modules\Tenancy\Application\Data\PosOutletContext;
use App\Modules\Tenancy\Domain\Enums\PosDeviceStatus;
use App\Modules\Tenancy\Domain\Models\PosDevice;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class ProcessSyncMutation
{
    /**
     * @var list<string>
     */
    private const ALLOWED_ACTIONS = [
        'offline_order.create_draft',
        'offline_order.add_item',
        'offline_order.update_item',
        'offline_order.remove_item',
        'offline_order.complete_cash',
        'offline_order.complete_manual',
    ];

    public function __construct(
        private CreateDraftOrder $createDraftOrder,
        private AddOrderItem $addOrderItem,
        private CompleteOrderWithPayment $completeOrder,
        private GetOfflineCatalogSnapshot $catalogSnapshot,
        private RecordSyncConflict $conflicts,
        private RecordSyncOutbox $outbox,
    ) {}

    public function handle(PosOutletContext $context, SyncMutationInput $input): SyncMutationResult
    {
        $this->assertContextMatches($context, $input);

        return DB::transaction(function () use ($context, $input): SyncMutationResult {
            $state = $this->lockedDeviceState($context);
            $this->assertDeviceCanSync($context, $state);

            $existing = $this->existingInboxRecord($input);

            if ($existing instanceof SyncInboxRecord) {
                return $this->existingRecordResult($input, $existing);
            }

            if (! in_array($input->action, self::ALLOWED_ACTIONS, true)) {
                return $this->rejectMutation($input, 'SYNC_OPERATION_NOT_ALLOWED_OFFLINE');
            }

            if ($input->sequenceNumber <= $state->last_accepted_sequence) {
                return $this->recordConflict($input, null, 'sequence_conflict', [
                    'last_accepted_sequence' => $state->last_accepted_sequence,
                    'incoming_sequence' => $input->sequenceNumber,
                ]);
            }

            $record = $this->createInboxRecord($input, SyncRecordStatus::Accepted);
            $result = $this->applyAcceptedMutation($context, $input, $record);

            $record->forceFill([
                'status' => SyncRecordStatus::from($result->status),
                'resource_type' => $result->resourceType,
                'resource_id' => $result->resourceId,
                'response' => $result->response,
            ])->save();

            if ($record->status === SyncRecordStatus::Accepted) {
                $state->forceFill([
                    'last_accepted_sequence' => max($state->last_accepted_sequence, $input->sequenceNumber),
                    'last_synced_at' => CarbonImmutable::now(),
                ])->save();
            }

            return $result;
        });
    }

    private function assertContextMatches(PosOutletContext $context, SyncMutationInput $input): void
    {
        if (
            $context->tenantId !== $input->tenantId
            || $context->outletId !== $input->outletId
            || $context->deviceId !== $input->deviceId
        ) {
            throw SyncException::deviceRevoked();
        }
    }

    private function lockedDeviceState(PosOutletContext $context): SyncDeviceState
    {
        $state = SyncDeviceState::query()
            ->where('tenant_id', $context->tenantId)
            ->where('outlet_id', $context->outletId)
            ->where('device_id', $context->deviceId)
            ->lockForUpdate()
            ->first();

        if ($state instanceof SyncDeviceState) {
            return $state;
        }

        return SyncDeviceState::query()->create([
            'tenant_id' => $context->tenantId,
            'outlet_id' => $context->outletId,
            'device_id' => $context->deviceId,
            'last_accepted_sequence' => 0,
        ]);
    }

    private function assertDeviceCanSync(PosOutletContext $context, SyncDeviceState $state): void
    {
        $device = PosDevice::query()
            ->where('tenant_id', $context->tenantId)
            ->where('outlet_id', $context->outletId)
            ->whereKey($context->deviceId)
            ->lockForUpdate()
            ->first();

        if (! $device instanceof PosDevice || $device->status === PosDeviceStatus::Revoked || $state->revoked_at !== null) {
            throw SyncException::deviceRevoked();
        }
    }

    private function existingInboxRecord(SyncMutationInput $input): ?SyncInboxRecord
    {
        return SyncInboxRecord::query()
            ->where('tenant_id', $input->tenantId)
            ->where('outlet_id', $input->outletId)
            ->where('device_id', $input->deviceId)
            ->where('action', $input->action)
            ->where('client_record_id', $input->clientRecordId)
            ->where('sequence_number', $input->sequenceNumber)
            ->lockForUpdate()
            ->first();
    }

    private function existingRecordResult(SyncMutationInput $input, SyncInboxRecord $record): SyncMutationResult
    {
        if ($record->payload_hash !== $input->payloadHash) {
            return $this->recordConflict($input, $record, 'payload_hash_conflict', [
                'existing_payload_hash' => $record->payload_hash,
                'incoming_payload_hash' => $input->payloadHash,
            ], false);
        }

        return new SyncMutationResult(
            status: SyncRecordStatus::Duplicate->value,
            resourceType: $record->resource_type,
            resourceId: $record->resource_id,
            response: [
                'duplicate_of' => $record->id,
                ...($record->response ?? []),
            ],
        );
    }

    private function rejectMutation(SyncMutationInput $input, string $errorCode): SyncMutationResult
    {
        $record = $this->createInboxRecord($input, SyncRecordStatus::Rejected);
        $response = ['error_code' => $errorCode];

        $record->forceFill(['response' => $response])->save();

        return new SyncMutationResult(SyncRecordStatus::Rejected->value, null, null, $response);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function recordConflict(
        SyncMutationInput $input,
        ?SyncInboxRecord $record,
        string $conflictType,
        array $payload,
        bool $markRecordConflict = true,
    ): SyncMutationResult {
        $record ??= $this->createInboxRecord($input, SyncRecordStatus::Conflict);

        if ($markRecordConflict) {
            $record->forceFill([
                'status' => SyncRecordStatus::Conflict,
                'response' => ['error_code' => 'SYNC_CONFLICT_REQUIRES_REVIEW'],
            ])->save();
        }

        $conflict = $this->conflicts->handle(
            tenantId: $input->tenantId,
            outletId: $input->outletId,
            conflictType: $conflictType,
            deviceId: $input->deviceId,
            inboxRecordId: $record->id,
            payload: [
                'client_record_id' => $input->clientRecordId,
                'action' => $input->action,
                ...$payload,
            ],
        );

        return new SyncMutationResult(
            status: SyncRecordStatus::Conflict->value,
            resourceType: 'sync_conflict',
            resourceId: $conflict->id,
            response: [
                'conflict_id' => $conflict->id,
                'error_code' => 'SYNC_CONFLICT_REQUIRES_REVIEW',
            ],
        );
    }

    private function createInboxRecord(SyncMutationInput $input, SyncRecordStatus $status): SyncInboxRecord
    {
        return SyncInboxRecord::query()->create([
            'tenant_id' => $input->tenantId,
            'outlet_id' => $input->outletId,
            'device_id' => $input->deviceId,
            'client_record_id' => $input->clientRecordId,
            'action' => $input->action,
            'sequence_number' => $input->sequenceNumber,
            'idempotency_key' => $input->idempotencyKey,
            'payload_hash' => $input->payloadHash,
            'status' => $status,
            'payload' => $input->payload,
        ]);
    }

    private function applyAcceptedMutation(PosOutletContext $context, SyncMutationInput $input, SyncInboxRecord $record): SyncMutationResult
    {
        return match ($input->action) {
            'offline_order.create_draft' => $this->createOfflineDraft($input),
            'offline_order.add_item',
            'offline_order.update_item',
            'offline_order.remove_item' => $this->appendOfflineOrderEvent($input),
            'offline_order.complete_cash',
            'offline_order.complete_manual' => $this->completeOfflineOrder($context, $input, $record),
            default => $this->rejectMutation($input, 'SYNC_OPERATION_NOT_ALLOWED_OFFLINE'),
        };
    }

    private function createOfflineDraft(SyncMutationInput $input): SyncMutationResult
    {
        $clientOrderId = $this->payloadString($input->payload, 'client_order_id');

        $draft = OfflineOrderDraft::query()->updateOrCreate(
            [
                'tenant_id' => $input->tenantId,
                'outlet_id' => $input->outletId,
                'device_id' => $input->deviceId,
                'client_order_id' => $clientOrderId,
            ],
            [
                'status' => OfflineOrderStatus::LocalDraft,
                'draft_payload' => $input->payload,
            ],
        );

        $this->outbox->handle($input->tenantId, $input->outletId, 'offline_order.draft_synced', 'offline_order_draft', $draft->id);

        return new SyncMutationResult(SyncRecordStatus::Accepted->value, 'offline_order_draft', $draft->id, [
            'offline_order_draft_id' => $draft->id,
            'status' => $draft->status->value,
        ]);
    }

    private function appendOfflineOrderEvent(SyncMutationInput $input): SyncMutationResult
    {
        $draft = $this->offlineDraft($input);

        if (! in_array($draft->status, [OfflineOrderStatus::LocalDraft, OfflineOrderStatus::Queued], true)) {
            throw SyncException::offlineOrderInvalidState();
        }

        OfflineOrderEvent::query()->updateOrCreate(
            [
                'offline_order_draft_id' => $draft->id,
                'event_type' => $input->action,
                'sequence_number' => $input->sequenceNumber,
            ],
            [
                'tenant_id' => $input->tenantId,
                'outlet_id' => $input->outletId,
                'payload' => $input->payload,
            ],
        );

        $draft->forceFill([
            'status' => OfflineOrderStatus::Queued,
            'draft_payload' => $input->payload,
        ])->save();

        $this->outbox->handle($input->tenantId, $input->outletId, 'offline_order.event_synced', 'offline_order_draft', $draft->id, [
            'action' => $input->action,
            'sequence_number' => $input->sequenceNumber,
        ]);

        return new SyncMutationResult(SyncRecordStatus::Accepted->value, 'offline_order_draft', $draft->id, [
            'offline_order_draft_id' => $draft->id,
            'status' => $draft->status->value,
        ]);
    }

    private function completeOfflineOrder(PosOutletContext $context, SyncMutationInput $input, SyncInboxRecord $record): SyncMutationResult
    {
        $draft = $this->offlineDraft($input);

        if ($draft->sales_order_id !== null || $draft->status === OfflineOrderStatus::Accepted) {
            return new SyncMutationResult(SyncRecordStatus::Accepted->value, 'sales_order', $draft->sales_order_id, [
                'order_id' => $draft->sales_order_id,
                'offline_order_draft_id' => $draft->id,
                'status' => OfflineOrderStatus::Accepted->value,
            ]);
        }

        if ($this->hasCatalogConflict($context, $input)) {
            $draft->forceFill(['status' => OfflineOrderStatus::Conflict])->save();

            return $this->recordConflict($input, $record, 'stale_catalog_or_stock', [
                'client_order_id' => $draft->client_order_id,
                'catalog_version' => $input->payload['catalog_version'] ?? null,
            ]);
        }

        $method = $input->action === 'offline_order.complete_cash'
            ? PaymentMethod::Cash
            : PaymentMethod::ManualNonCash;

        if ($this->offlineGatewayRequested($input->payload, $method)) {
            $draft->forceFill(['status' => OfflineOrderStatus::Rejected])->save();

            return new SyncMutationResult(SyncRecordStatus::Rejected->value, 'offline_order_draft', $draft->id, [
                'offline_order_draft_id' => $draft->id,
                'error_code' => 'SYNC_OPERATION_NOT_ALLOWED_OFFLINE',
            ]);
        }

        $order = $this->createDraftOrder->handle($context, $input->idempotencyKey.'-create');

        foreach ($this->payloadItems($input->payload) as $item) {
            $order = $this->addOrderItem->handle($context, $order->id, new OrderItemSelection(
                productId: $item['product_id'],
                quantity: $item['quantity'],
                variantId: $item['variant_id'],
                modifierOptionIds: $item['modifiers'],
            ));
        }

        $completed = $this->completeOrder->handle(
            $context,
            $order->id,
            $method,
            $this->payloadInt($input->payload, 'amount_minor', $order->total_minor),
            $this->payloadString($input->payload, 'currency', $order->currency),
            $input->idempotencyKey.'-complete',
        );

        $draft->forceFill([
            'sales_order_id' => $completed->id,
            'status' => OfflineOrderStatus::Accepted,
            'draft_payload' => $input->payload,
        ])->save();

        $this->outbox->handle($context->tenantId, $context->outletId, 'offline_order.accepted', 'sales_order', $completed->id, [
            'offline_order_draft_id' => $draft->id,
            'order_number' => $completed->order_number,
            'total_minor' => $completed->total_minor,
            'currency' => $completed->currency,
        ]);

        return new SyncMutationResult(SyncRecordStatus::Accepted->value, 'sales_order', $completed->id, [
            'order_id' => $completed->id,
            'order_number' => $completed->order_number,
            'offline_order_draft_id' => $draft->id,
            'status' => OfflineOrderStatus::Accepted->value,
        ]);
    }

    private function offlineDraft(SyncMutationInput $input): OfflineOrderDraft
    {
        $clientOrderId = $this->payloadString($input->payload, 'client_order_id');
        $draft = OfflineOrderDraft::query()
            ->where('tenant_id', $input->tenantId)
            ->where('outlet_id', $input->outletId)
            ->where('device_id', $input->deviceId)
            ->where('client_order_id', $clientOrderId)
            ->lockForUpdate()
            ->first();

        if (! $draft instanceof OfflineOrderDraft) {
            throw SyncException::offlineOrderNotFound();
        }

        return $draft;
    }

    private function hasCatalogConflict(PosOutletContext $context, SyncMutationInput $input): bool
    {
        if (($input->payload['insufficient_stock'] ?? false) === true || ($input->payload['stock_available'] ?? true) === false) {
            return true;
        }

        $catalogVersion = $input->payload['catalog_version'] ?? null;

        if (! is_string($catalogVersion) || $catalogVersion === '') {
            return false;
        }

        $snapshot = $this->catalogSnapshot->handle($context);

        return $snapshot['version'] !== $catalogVersion;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{product_id: string, quantity: string, variant_id: string|null, modifiers: list<string>}>
     */
    private function payloadItems(array $payload): array
    {
        $items = $payload['items'] ?? [];

        if (! is_array($items) || $items === []) {
            throw SyncException::offlineOrderInvalidState();
        }

        $resolved = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                throw SyncException::offlineOrderInvalidState();
            }

            $productId = $item['product_id'] ?? null;
            $quantity = $item['quantity'] ?? '1.000';
            $variantId = $item['variant_id'] ?? null;
            $modifiers = $item['modifiers'] ?? [];

            if (! is_string($productId) || $productId === '' || (! is_string($quantity) && ! is_int($quantity) && ! is_float($quantity))) {
                throw SyncException::offlineOrderInvalidState();
            }

            if ($variantId !== null && ! is_string($variantId)) {
                throw SyncException::offlineOrderInvalidState();
            }

            if (! is_array($modifiers)) {
                throw SyncException::offlineOrderInvalidState();
            }

            $resolved[] = [
                'product_id' => $productId,
                'quantity' => is_string($quantity) ? $quantity : number_format((float) $quantity, 3, '.', ''),
                'variant_id' => $variantId,
                'modifiers' => array_values(array_filter($modifiers, static fn (mixed $modifier): bool => is_string($modifier))),
            ];
        }

        return $resolved;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function offlineGatewayRequested(array $payload, PaymentMethod $expectedMethod): bool
    {
        $method = $payload['payment_method'] ?? $expectedMethod->value;

        return (is_string($method) && $method !== $expectedMethod->value)
            || ($payload['gateway'] ?? false) === true
            || is_string($payload['gateway_intent_id'] ?? null)
            || is_string($payload['payment_provider'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function payloadString(array $payload, string $key, ?string $default = null): string
    {
        $value = $payload[$key] ?? $default;

        if (! is_string($value) || $value === '') {
            throw SyncException::offlineOrderInvalidState();
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function payloadInt(array $payload, string $key, int $default): int
    {
        $value = $payload[$key] ?? $default;

        if (! is_int($value)) {
            throw SyncException::offlineOrderInvalidState();
        }

        return $value;
    }
}
