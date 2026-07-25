<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

final class TenantSessionStatusController extends Controller
{
    public function __invoke(): Response
    {
        return response()->noContent();
    }
}
