<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Presentation\Http\Web\Controllers;

use App\Modules\Tenancy\Domain\Models\Tenant;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class TenantHomeController extends Controller
{
    public function __invoke(string $tenant): View
    {
        return view('tenancy::tenant.home', [
            'tenant' => Tenant::query()->findOrFail($tenant),
        ]);
    }
}
