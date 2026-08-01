<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Http\Api\Controllers;

use App\Modules\Catalog\Application\Actions\ListAvailableOutletCatalog;
use App\Modules\Tenancy\Application\Actions\ResolvePosOutletApiContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Laravel\Sanctum\Contracts\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class OutletCatalogController extends Controller
{
    public function __invoke(
        string $outlet,
        Request $request,
        ResolvePosOutletApiContext $context,
        ListAvailableOutletCatalog $catalog,
    ): JsonResponse {
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
            'data' => array_map(
                fn ($product): array => [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'category' => [
                        'id' => $product->categoryId,
                        'name' => $product->categoryName,
                        'parent' => $product->parentCategoryId === null ? null : [
                            'id' => $product->parentCategoryId,
                            'name' => $product->parentCategoryName,
                        ],
                    ],
                    'price_minor' => $product->priceMinor,
                    'currency' => $product->currency,
                ],
                $catalog->handle($resolved),
            ),
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
