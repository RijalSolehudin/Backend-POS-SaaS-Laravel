<?php

declare(strict_types=1);

namespace App\Modules\Sales\Presentation\Http\Api\Controllers;

use App\Modules\Sales\Application\Actions\CloseShift;
use App\Modules\Sales\Application\Actions\GetCurrentShift;
use App\Modules\Sales\Application\Actions\OpenShift;
use App\Modules\Sales\Application\Actions\RecordCashMovement;
use App\Modules\Sales\Application\Actions\SummarizeShift;
use App\Modules\Sales\Application\Data\ShiftSummary;
use App\Modules\Sales\Application\Exceptions\CashMovementException;
use App\Modules\Sales\Application\Exceptions\ShiftException;
use App\Modules\Sales\Domain\Enums\CashMovementType;
use App\Modules\Sales\Domain\Models\CashMovement;
use App\Modules\Sales\Domain\Models\Shift;
use App\Modules\Tenancy\Application\Actions\ResolvePosOutletApiContext;
use App\Modules\Tenancy\Application\Data\PosOutletContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Laravel\Sanctum\Contracts\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class ShiftController extends Controller
{
    public function current(
        string $outlet,
        Request $request,
        ResolvePosOutletApiContext $context,
        GetCurrentShift $currentShift,
    ): JsonResponse {
        $shift = $currentShift->handle($this->context($outlet, $request, $context));

        return response()->json([
            'data' => $shift instanceof Shift ? $this->shiftData($shift) : null,
        ]);
    }

    public function open(
        string $outlet,
        Request $request,
        ResolvePosOutletApiContext $context,
        OpenShift $openShift,
    ): JsonResponse {
        /** @var array{opening_cash_minor: int} $validated */
        $validated = $request->validate([
            'opening_cash_minor' => ['required', 'integer', 'min:0'],
        ]);

        $shift = $openShift->handle(
            $this->context($outlet, $request, $context),
            (int) $validated['opening_cash_minor'],
        );

        return response()->json(['data' => $this->shiftData($shift)], 201);
    }

    public function close(
        string $outlet,
        string $shift,
        Request $request,
        ResolvePosOutletApiContext $context,
        CloseShift $closeShift,
    ): JsonResponse {
        /** @var array{closing_cash_minor: int} $validated */
        $validated = $request->validate([
            'closing_cash_minor' => ['required', 'integer', 'min:0'],
        ]);
        $idempotencyKey = $request->header('Idempotency-Key');

        if (! is_string($idempotencyKey)) {
            throw ShiftException::idempotencyKeyRequired();
        }

        $closed = $closeShift->handle(
            $this->context($outlet, $request, $context),
            $shift,
            (int) $validated['closing_cash_minor'],
            $idempotencyKey,
        );

        return response()->json(['data' => $this->shiftData($closed)]);
    }

    public function cashMovement(
        string $outlet,
        string $shift,
        Request $request,
        ResolvePosOutletApiContext $context,
        RecordCashMovement $record,
    ): JsonResponse {
        $idempotencyKey = $request->header('Idempotency-Key');

        if (! is_string($idempotencyKey)) {
            throw CashMovementException::idempotencyKeyRequired();
        }

        /** @var array{type: string, amount_minor: int, currency: string, reason: string, approval_id?: string|null} $validated */
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:cash_in,cash_out'],
            'amount_minor' => ['required', 'integer', 'min:1'],
            'currency' => ['required', 'string', 'size:3'],
            'reason' => ['required', 'string', 'max:500'],
            'approval_id' => ['nullable', 'string', 'size:26'],
        ]);

        $movement = $record->handle(
            $this->context($outlet, $request, $context),
            $shift,
            CashMovementType::from($validated['type']),
            $validated['amount_minor'],
            $validated['currency'],
            $validated['reason'],
            $idempotencyKey,
            $validated['approval_id'] ?? null,
        );

        return response()->json(['data' => $this->cashMovementData($movement)], 201);
    }

    public function summary(
        string $outlet,
        string $shift,
        Request $request,
        ResolvePosOutletApiContext $context,
        SummarizeShift $summarizeShift,
    ): JsonResponse {
        return response()->json([
            'data' => $this->summaryData($summarizeShift->handle($this->context($outlet, $request, $context), $shift)),
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
     * @return array<string, int|string|null>
     */
    private function shiftData(Shift $shift): array
    {
        return [
            'id' => $shift->id,
            'tenant_id' => $shift->tenant_id,
            'outlet_id' => $shift->outlet_id,
            'user_id' => $shift->user_id,
            'status' => $shift->status->value,
            'opened_at' => $shift->opened_at->toJSON(),
            'closed_at' => $shift->closed_at?->toJSON(),
            'opening_cash_minor' => $shift->opening_cash_minor,
            'closing_cash_minor' => $shift->closing_cash_minor,
            'expected_cash_minor' => $shift->expected_cash_minor,
            'gross_sales_minor' => $shift->gross_sales_minor,
            'currency' => $shift->currency,
        ];
    }

    /**
     * @return array<string, int|string|null>
     */
    private function summaryData(ShiftSummary $summary): array
    {
        return [
            'tenant_id' => $summary->tenantId,
            'outlet_id' => $summary->outletId,
            'shift_id' => $summary->shiftId,
            'user_id' => $summary->userId,
            'status' => $summary->status,
            'opened_at' => $summary->openedAt->toJSON(),
            'closed_at' => $summary->closedAt?->toJSON(),
            'opening_cash_minor' => $summary->openingCashMinor,
            'closing_cash_minor' => $summary->closingCashMinor,
            'expected_cash_minor' => $summary->expectedCashMinor,
            'cash_variance_minor' => $summary->cashVarianceMinor,
            'completed_orders_count' => $summary->completedOrdersCount,
            'gross_sales_minor' => $summary->grossSalesMinor,
            'refunds_minor' => $summary->refundsMinor,
            'net_sales_minor' => $summary->netSalesMinor,
            'recorded_payments_minor' => $summary->recordedPaymentsMinor,
            'cash_payments_minor' => $summary->cashPaymentsMinor,
            'manual_non_cash_payments_minor' => $summary->manualNonCashPaymentsMinor,
            'cash_in_minor' => $summary->cashInMinor,
            'cash_out_minor' => $summary->cashOutMinor,
            'currency' => $summary->currency,
        ];
    }

    /**
     * @return array<string, int|string|null>
     */
    private function cashMovementData(CashMovement $movement): array
    {
        return [
            'id' => $movement->id,
            'tenant_id' => $movement->tenant_id,
            'outlet_id' => $movement->outlet_id,
            'shift_id' => $movement->shift_id,
            'user_id' => $movement->user_id,
            'approval_id' => $movement->approval_id,
            'type' => $movement->type->value,
            'amount_minor' => $movement->amount_minor,
            'currency' => $movement->currency,
            'reason' => $movement->reason,
            'recorded_at' => $movement->recorded_at->toJSON(),
        ];
    }
}
