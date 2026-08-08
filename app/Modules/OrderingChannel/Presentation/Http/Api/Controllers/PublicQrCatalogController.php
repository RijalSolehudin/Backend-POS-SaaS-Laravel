<?php

declare(strict_types=1);

namespace App\Modules\OrderingChannel\Presentation\Http\Api\Controllers;

use App\Modules\OrderingChannel\Application\Actions\PublicQrCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class PublicQrCatalogController extends Controller
{
    public function __invoke(string $token, PublicQrCatalog $catalog): JsonResponse
    {
        return response()->json(['data' => $catalog->handle($token)]);
    }
}
