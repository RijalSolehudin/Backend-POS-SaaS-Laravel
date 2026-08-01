<?php

declare(strict_types=1);

namespace App\Modules\Sales\Presentation\Http\Api\Controllers;

use App\Modules\Sales\Application\Actions\AddOrderItem;
use App\Modules\Sales\Application\Actions\CancelDraftOrder;
use App\Modules\Sales\Application\Actions\CompleteOrderWithPayment;
use App\Modules\Sales\Application\Actions\CreateDraftOrder;
use App\Modules\Sales\Application\Actions\GetDraftOrder;
use App\Modules\Sales\Application\Actions\GetOrderReceipt;
use App\Modules\Sales\Application\Actions\RecordFullRefund;
use App\Modules\Sales\Application\Actions\RemoveOrderItem;
use App\Modules\Sales\Application\Actions\UpdateOrderItem;
use App\Modules\Sales\Application\Actions\VoidCompletedOrder;
use App\Modules\Sales\Application\Data\OrderItemSelection;
use App\Modules\Sales\Application\Exceptions\OrderException;
use App\Modules\Sales\Domain\Enums\PaymentMethod;
use App\Modules\Sales\Domain\Models\Order;
use App\Modules\Sales\Domain\Models\OrderItem;
use App\Modules\Sales\Domain\Models\Payment;
use App\Modules\Sales\Domain\Models\Receipt;
use App\Modules\Sales\Domain\Models\Refund;
use App\Modules\Tenancy\Application\Actions\ResolvePosOutletApiContext;
use App\Modules\Tenancy\Application\Data\PosOutletContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Laravel\Sanctum\Contracts\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class OrderController extends Controller
{
    public function store(
        string $outlet,
        Request $request,
        ResolvePosOutletApiContext $context,
        CreateDraftOrder $create,
    ): JsonResponse {
        $idempotencyKey = $request->header('Idempotency-Key');

        if (! is_string($idempotencyKey)) {
            throw OrderException::idempotencyKeyRequired();
        }

        $order = $create->handle($this->context($outlet, $request, $context), $idempotencyKey);

        return response()->json(['data' => $this->orderData($order)], 201);
    }

    public function show(
        string $outlet,
        string $order,
        Request $request,
        ResolvePosOutletApiContext $context,
        GetDraftOrder $get,
    ): JsonResponse {
        return response()->json([
            'data' => $this->orderData($get->handle($this->context($outlet, $request, $context), $order)),
        ]);
    }

    public function addItem(
        string $outlet,
        string $order,
        Request $request,
        ResolvePosOutletApiContext $context,
        AddOrderItem $add,
    ): JsonResponse {
        /** @var array{product_id: string, quantity: string, variant_id?: string|null, modifiers?: list<string>} $validated */
        $validated = $request->validate([
            'product_id' => ['required', 'string', 'size:26'],
            'variant_id' => ['nullable', 'string', 'size:26'],
            'modifiers' => ['nullable', 'array'],
            'modifiers.*' => ['required', 'string', 'size:26'],
            'quantity' => ['required', 'numeric', 'gt:0', 'regex:/^\d+(\.\d{1,3})?$/'],
        ]);

        $updated = $add->handle(
            $this->context($outlet, $request, $context),
            $order,
            new OrderItemSelection(
                productId: $validated['product_id'],
                quantity: $validated['quantity'],
                variantId: $validated['variant_id'] ?? null,
                modifierOptionIds: $validated['modifiers'] ?? [],
            ),
        );

        return response()->json(['data' => $this->orderData($updated)]);
    }

    public function updateItem(
        string $outlet,
        string $order,
        string $item,
        Request $request,
        ResolvePosOutletApiContext $context,
        UpdateOrderItem $update,
    ): JsonResponse {
        /** @var array{quantity: string} $validated */
        $validated = $request->validate([
            'quantity' => ['required', 'numeric', 'gt:0', 'regex:/^\d+(\.\d{1,3})?$/'],
        ]);

        $updated = $update->handle(
            $this->context($outlet, $request, $context),
            $order,
            $item,
            $validated['quantity'],
        );

        return response()->json(['data' => $this->orderData($updated)]);
    }

    public function removeItem(
        string $outlet,
        string $order,
        string $item,
        Request $request,
        ResolvePosOutletApiContext $context,
        RemoveOrderItem $remove,
    ): JsonResponse {
        $updated = $remove->handle($this->context($outlet, $request, $context), $order, $item);

        return response()->json(['data' => $this->orderData($updated)]);
    }

    public function complete(
        string $outlet,
        string $order,
        Request $request,
        ResolvePosOutletApiContext $context,
        CompleteOrderWithPayment $complete,
    ): JsonResponse {
        $idempotencyKey = $request->header('Idempotency-Key');

        if (! is_string($idempotencyKey)) {
            throw OrderException::idempotencyKeyRequired();
        }

        /** @var array{method: string, amount_minor: int, currency: string} $validated */
        $validated = $request->validate([
            'method' => ['required', 'string', 'in:cash,manual_non_cash'],
            'amount_minor' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
        ]);

        $updated = $complete->handle(
            $this->context($outlet, $request, $context),
            $order,
            PaymentMethod::from($validated['method']),
            $validated['amount_minor'],
            $validated['currency'],
            $idempotencyKey,
        );

        return response()->json(['data' => $this->orderData($updated)]);
    }

    public function cancel(
        string $outlet,
        string $order,
        Request $request,
        ResolvePosOutletApiContext $context,
        CancelDraftOrder $cancel,
    ): JsonResponse {
        $idempotencyKey = $request->header('Idempotency-Key');

        if (! is_string($idempotencyKey)) {
            throw OrderException::idempotencyKeyRequired();
        }

        /** @var array{reason: string} $validated */
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $updated = $cancel->handle(
            $this->context($outlet, $request, $context),
            $order,
            $validated['reason'],
            $idempotencyKey,
        );

        return response()->json(['data' => $this->orderData($updated)]);
    }

    public function voidOrder(
        string $outlet,
        string $order,
        Request $request,
        ResolvePosOutletApiContext $context,
        VoidCompletedOrder $void,
    ): JsonResponse {
        $idempotencyKey = $request->header('Idempotency-Key');

        if (! is_string($idempotencyKey)) {
            throw OrderException::idempotencyKeyRequired();
        }

        /** @var array{reason: string, approval_id: string} $validated */
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
            'approval_id' => ['required', 'string', 'size:26'],
        ]);

        $posContext = $this->context($outlet, $request, $context);
        $updated = $void->handle(
            $posContext->tenantId,
            $order,
            $posContext->userId,
            $validated['reason'],
            $idempotencyKey,
            $validated['approval_id'],
        );

        return response()->json(['data' => $this->orderData($updated)]);
    }

    public function refund(
        string $outlet,
        string $order,
        Request $request,
        ResolvePosOutletApiContext $context,
        RecordFullRefund $refund,
    ): JsonResponse {
        $idempotencyKey = $request->header('Idempotency-Key');

        if (! is_string($idempotencyKey)) {
            throw OrderException::idempotencyKeyRequired();
        }

        /** @var array{amount_minor: int, currency: string, reason: string, approval_id: string} $validated */
        $validated = $request->validate([
            'amount_minor' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'reason' => ['required', 'string', 'max:500'],
            'approval_id' => ['required', 'string', 'size:26'],
        ]);

        $created = $refund->handle(
            $this->context($outlet, $request, $context),
            $order,
            $validated['amount_minor'],
            $validated['currency'],
            $validated['reason'],
            $idempotencyKey,
            $validated['approval_id'],
        );

        return response()->json(['data' => $this->refundData($created)], 201);
    }

    public function receipt(
        string $outlet,
        string $order,
        Request $request,
        ResolvePosOutletApiContext $context,
        GetOrderReceipt $receipt,
    ): JsonResponse {
        return response()->json([
            'data' => $this->receiptData($receipt->handle($this->context($outlet, $request, $context), $order)),
        ]);
    }

    private function context(
        string $outlet,
        Request $request,
        ResolvePosOutletApiContext $context,
    ): PosOutletContext {
        $user = $request->user();

        if (! $user instanceof HasApiTokens) {
            throw new UnauthorizedHttpException('Bearer', 'A valid POS access token is required.');
        }

        $token = $this->bearerToken($request, (string) $user->getAuthIdentifier());

        if (! $token instanceof PersonalAccessToken) {
            throw new UnauthorizedHttpException('Bearer', 'A valid POS device token is required.');
        }

        $deviceId = $token->getAttribute('pos_device_id');

        if (! is_string($deviceId) || $deviceId === '') {
            throw new UnauthorizedHttpException('Bearer', 'A valid POS device token is required.');
        }

        return $context->handle((string) $user->getAuthIdentifier(), $deviceId, $outlet);
    }

    private function bearerToken(Request $request, string $userId): ?PersonalAccessToken
    {
        $plainToken = $request->bearerToken();

        if (! is_string($plainToken) || $plainToken === '') {
            return null;
        }

        $token = PersonalAccessToken::findToken($plainToken);

        if (! $token instanceof PersonalAccessToken || $token->getAttribute('tokenable_id') !== $userId) {
            return null;
        }

        return $token;
    }

    /**
     * @return array<string, mixed>
     */
    private function orderData(Order $order): array
    {
        $order->loadMissing(['items', 'payments']);

        return [
            'id' => $order->id,
            'tenant_id' => $order->tenant_id,
            'outlet_id' => $order->outlet_id,
            'shift_id' => $order->shift_id,
            'user_id' => $order->user_id,
            'order_number' => $order->order_number,
            'status' => $order->status->value,
            'subtotal_minor' => $order->subtotal_minor,
            'discount_minor' => $order->discount_minor,
            'service_charge_minor' => $order->service_charge_minor,
            'tax_minor' => $order->tax_minor,
            'total_minor' => $order->total_minor,
            'currency' => $order->currency,
            'completed_at' => $order->completed_at?->toJSON(),
            'cancelled_at' => $order->cancelled_at?->toJSON(),
            'cancelled_by' => $order->cancelled_by,
            'cancel_reason' => $order->cancel_reason,
            'voided_at' => $order->voided_at?->toJSON(),
            'voided_by' => $order->voided_by,
            'void_reason' => $order->void_reason,
            'items' => $order->items
                ->map(fn (OrderItem $item): array => [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'product_sku' => $item->product_sku,
                    'variant_sku' => $item->variant_sku,
                    'product_name' => $item->product_name,
                    'variant_name' => $item->variant_name,
                    'product_category_id' => $item->product_category_id,
                    'product_category_name' => $item->product_category_name,
                    'quantity' => $item->quantity,
                    'unit_price_minor' => $item->unit_price_minor,
                    'modifier_total_minor' => $item->modifier_total_minor,
                    'modifiers' => $item->modifier_snapshot ?? [],
                    'line_subtotal_minor' => $item->line_subtotal_minor,
                    'currency' => $item->currency,
                ])
                ->values()
                ->all(),
            'payments' => $order->payments
                ->map(fn (Payment $payment): array => [
                    'id' => $payment->id,
                    'method' => $payment->method->value,
                    'status' => $payment->status->value,
                    'amount_minor' => $payment->amount_minor,
                    'currency' => $payment->currency,
                    'recorded_at' => $payment->recorded_at->toJSON(),
                    'voided_at' => $payment->voided_at?->toJSON(),
                    'voided_by' => $payment->voided_by,
                    'void_reason' => $payment->void_reason,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function receiptData(Receipt $receipt): array
    {
        return [
            'id' => $receipt->id,
            'tenant_id' => $receipt->tenant_id,
            'outlet_id' => $receipt->outlet_id,
            'order_id' => $receipt->order_id,
            'payment_id' => $receipt->payment_id,
            'receipt_number' => $receipt->receipt_number,
            'issued_at' => $receipt->issued_at->toJSON(),
            'snapshot' => $receipt->snapshot,
        ];
    }

    /**
     * @return array<string, int|string>
     */
    private function refundData(Refund $refund): array
    {
        return [
            'id' => $refund->id,
            'tenant_id' => $refund->tenant_id,
            'outlet_id' => $refund->outlet_id,
            'shift_id' => $refund->shift_id,
            'order_id' => $refund->order_id,
            'payment_id' => $refund->payment_id,
            'approval_id' => $refund->approval_id,
            'refunded_by' => $refund->refunded_by,
            'method' => $refund->method->value,
            'status' => $refund->status->value,
            'amount_minor' => $refund->amount_minor,
            'currency' => $refund->currency,
            'reason' => $refund->reason,
            'recorded_at' => $refund->recorded_at->toJSON(),
        ];
    }
}
