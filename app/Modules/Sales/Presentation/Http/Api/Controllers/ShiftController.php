<?php

declare(strict_types=1);

namespace App\Modules\Sales\Presentation\Http\Api\Controllers;

use App\Modules\Sales\Application\Actions\CloseShift;
use App\Modules\Sales\Application\Actions\GetCurrentShift;
use App\Modules\Sales\Application\Actions\OpenShift;
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

        $closed = $closeShift->handle(
            $this->context($outlet, $request, $context),
            $shift,
            (int) $validated['closing_cash_minor'],
        );

        return response()->json(['data' => $this->shiftData($closed)]);
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
}
