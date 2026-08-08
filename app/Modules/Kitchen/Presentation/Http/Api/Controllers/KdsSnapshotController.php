<?php

declare(strict_types=1);

namespace App\Modules\Kitchen\Presentation\Http\Api\Controllers;

use App\Modules\Kitchen\Application\Actions\GetKdsSnapshot;
use App\Modules\Tenancy\Application\Actions\ResolvePosOutletApiContext;
use App\Modules\Tenancy\Application\Data\PosOutletContext;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Domain\Enums\MembershipType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Laravel\Sanctum\Contracts\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class KdsSnapshotController extends Controller
{
    public function __invoke(
        string $outlet,
        Request $request,
        ResolvePosOutletApiContext $context,
        GetKdsSnapshot $snapshot,
    ): JsonResponse {
        $posContext = $this->context($outlet, $request, $context);
        $stationId = $request->query('station_id');

        return response()->json([
            'data' => $snapshot->handle(
                new TenantRequestContext($posContext->tenantId, $posContext->userId, MembershipType::Member),
                $posContext->outletId,
                is_string($stationId) && $stationId !== '' ? $stationId : null,
            ),
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
}
