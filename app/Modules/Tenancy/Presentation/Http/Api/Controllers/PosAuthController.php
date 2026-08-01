<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Presentation\Http\Api\Controllers;

use App\Modules\Tenancy\Application\Actions\IssuePosToken;
use App\Modules\Tenancy\Application\Actions\RevokeCurrentPosToken;
use App\Modules\Tenancy\Application\Data\PosTokenRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Laravel\Sanctum\Contracts\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;

final class PosAuthController extends Controller
{
    public function login(Request $request, IssuePosToken $tokens): JsonResponse
    {
        /** @var array{email: string, password: string, installation_id: string, outlet_id: string} $validated */
        $validated = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'installation_id' => ['required', 'string', 'size:26'],
            'outlet_id' => ['required', 'string', 'size:26'],
        ]);

        $session = $tokens->handle(new PosTokenRequest(
            email: $validated['email'],
            password: $validated['password'],
            installationId: $validated['installation_id'],
            outletId: $validated['outlet_id'],
        ));

        return response()->json([
            'data' => [
                'access_token' => $session->token,
                'token_type' => 'Bearer',
                'expires_at' => $session->expiresAt->toJSON(),
                'tenant_id' => $session->tenantId,
                'outlet_id' => $session->outletId,
                'device_id' => $session->deviceId,
                'user_id' => $session->userId,
                'must_change_password' => $session->mustChangePassword,
            ],
        ]);
    }

    public function logout(Request $request, RevokeCurrentPosToken $tokens): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof HasApiTokens) {
            return response()->json(['data' => ['revoked' => true]]);
        }

        $token = $this->bearerToken($request, (string) $user->getAuthIdentifier());

        if ($token instanceof PersonalAccessToken) {
            $tokens->handle($token);
        }

        return response()->json(['data' => ['revoked' => true]]);
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
