<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Presentation\Http\Api\Controllers;

use App\Modules\Tenancy\Application\Actions\ResolvePosOutletApiContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Laravel\Sanctum\Contracts\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class PosOutletContextController extends Controller
{
    public function __invoke(string $outlet, Request $request, ResolvePosOutletApiContext $context): JsonResponse
    {
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

        $resolved = $context->handle((string) $user->getAuthIdentifier(), $deviceId, $outlet);

        return response()->json([
            'data' => [
                'tenant_id' => $resolved->tenantId,
                'outlet_id' => $resolved->outletId,
                'device_id' => $resolved->deviceId,
                'user_id' => $resolved->userId,
            ],
        ]);
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
